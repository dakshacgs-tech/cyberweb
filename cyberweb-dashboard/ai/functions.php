
<?php

function ai_require_project(
    PDO $pdo,
    int $projectId,
    int $userId
): array {
    $stmt = $pdo->prepare(
        "SELECT *
         FROM projects
         WHERE id = ?
         AND user_id = ?
         LIMIT 1"
    );

    $stmt->execute([$projectId, $userId]);

    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        http_response_code(404);
        exit('Project not found.');
    }

    return $project;
}

/**
 * Collect saved hotel information.
 */
function ai_snapshot(
    PDO $pdo,
    int $projectId,
    int $userId
): array {
    $project = ai_require_project(
        $pdo,
        $projectId,
        $userId
    );

    $stmt = $pdo->prepare(
        "SELECT
            hotel_name,
            phone,
            address,
            amenities,
            things_to_do
         FROM hotel_details
         WHERE project_id = ?
         AND user_id = ?"
    );

    $stmt->execute([$projectId, $userId]);

    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hotel) {
        throw new RuntimeException(
            'Save Hotel Details before requesting designs.'
        );
    }

    // Fetch hotel FAQs
    $stmt = $pdo->prepare(
        "SELECT faq_position, question, answer
         FROM hotel_faqs
         WHERE project_id = ?
         AND user_id = ?
         ORDER BY faq_position"
    );

    $stmt->execute([$projectId, $userId]);

    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch gallery image references
    $stmt = $pdo->prepare(
        "SELECT id, file_name
         FROM gallery_images
         WHERE project_id = ?
         AND user_id = ?
         ORDER BY id"
    );

    $stmt->execute([$projectId, $userId]);

    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'project' => [
            'id' => $projectId,
            'name' => $project['name'],
            'team_member' =>
                $project['team_member'] ?? ''
        ],
        'hotel' => $hotel,
        'faqs' => $faqs,
        'gallery' => $images,
        'brand_colors' => null,
        'logo_asset' => null,
        'notice' =>
            'Logo and verified brand colors are not stored in the current database schema.'
    ];
}

/**
 * Create a new AI generation request.
 */
function ai_create_request(
    PDO $pdo,
    int $projectId,
    int $userId
): string {
    $input = ai_snapshot(
        $pdo,
        $projectId,
        $userId
    );

    $prompt = file_get_contents(
        __DIR__ . '/system_prompt.txt'
    );

    if ($prompt === false) {
        throw new RuntimeException(
            'System prompt not available.'
        );
    }

    $requestId = bin2hex(random_bytes(16));

    $stmt = $pdo->prepare(
        "INSERT INTO ai_generation_jobs
         (
            request_id,
            project_id,
            user_id,
            input_json,
            system_prompt
         )
         VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $requestId,
        $projectId,
        $userId,
        json_encode(
            $input,
            JSON_UNESCAPED_UNICODE |
            JSON_THROW_ON_ERROR
        ),
        $prompt
    ]);

    return $requestId;
}

/**
 * Manual ZIP import fallback.
 */
function ai_import_zip(
    PDO $pdo,
    int $jobId,
    int $projectId,
    int $userId,
    int $number,
    string $style,
    array $upload
): void {
    if (
        $number < 1 ||
        $number > 4 ||
        trim($style) === '' ||
        mb_strlen($style) > 90
    ) {
        throw new RuntimeException(
            'Invalid design information.'
        );
    }

    if (
        ($upload['error'] ?? UPLOAD_ERR_NO_FILE)
            !== UPLOAD_ERR_OK ||
        !is_uploaded_file($upload['tmp_name'] ?? '')
    ) {
        throw new RuntimeException(
            'Please upload a valid ZIP file.'
        );
    }

    $size = (int)($upload['size'] ?? 0);

    if (
        $size < 100 ||
        $size > 25 * 1024 * 1024
    ) {
        throw new RuntimeException(
            'ZIP size must be between 100 bytes and 25 MB.'
        );
    }

    $zip = new ZipArchive();

    if ($zip->open($upload['tmp_name']) !== true) {
        throw new RuntimeException(
            'Invalid ZIP archive.'
        );
    }

    $hasIndex = false;

    try {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (
                !is_string($name) ||
                str_contains($name, '..') ||
                str_starts_with($name, '/') ||
                str_contains($name, '\\')
            ) {
                throw new RuntimeException(
                    'Unsafe archive path.'
                );
            }

            if ($name === 'index.html') {
                $hasIndex = true;
            }
        }

    } finally {
        $zip->close();
    }

    if (!$hasIndex) {
        throw new RuntimeException(
            'ZIP must contain root index.html.'
        );
    }

    $folder = __DIR__ . '/../storage/ai_zips';

    if (
        !is_dir($folder) &&
        !mkdir($folder, 0700, true)
    ) {
        throw new RuntimeException(
            'Archive storage unavailable.'
        );
    }

    $name = bin2hex(random_bytes(20)) . '.zip';
    $path = $folder . '/' . $name;

    if (!move_uploaded_file(
        $upload['tmp_name'],
        $path
    )) {
        throw new RuntimeException(
            'Failed to store ZIP.'
        );
    }

    $hash = hash_file('sha256', $path);

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO ai_generated_designs
             (
                job_id,
                project_id,
                user_id,
                design_number,
                style_name,
                archive_name,
                sha256
             )
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $jobId,
            $projectId,
            $userId,
            $number,
            $style,
            $name,
            $hash
        ]);

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM ai_generated_designs
             WHERE job_id = ?
             AND project_id = ?
             AND user_id = ?"
        );

        $countStmt->execute([
            $jobId,
            $projectId,
            $userId
        ]);

        if ((int)$countStmt->fetchColumn() === 4) {
            $update = $pdo->prepare(
                "UPDATE ai_generation_jobs
                 SET status = 'completed',
                     completed_at = NOW()
                 WHERE id = ?
                 AND project_id = ?
                 AND user_id = ?"
            );

            $update->execute([
                $jobId,
                $projectId,
                $userId
            ]);
        }

    } catch (Throwable $e) {
        @unlink($path);
        throw $e;
    }
}

/**
 * Save selected design to database.
 */
function ai_select(
    PDO $pdo,
    int $projectId,
    int $userId,
    int $designId
): void {
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            "SELECT id, job_id
             FROM ai_generated_designs
             WHERE id = ?
             AND project_id = ?
             AND user_id = ?
             FOR UPDATE"
        );

        $stmt->execute([
            $designId,
            $projectId,
            $userId
        ]);

        $design = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$design) {
            throw new RuntimeException(
                'Design not found.'
            );
        }

        $check = $pdo->prepare(
            "SELECT id
             FROM ai_generation_jobs
             WHERE id = ?
             AND status = 'completed'
             AND project_id = ?
             AND user_id = ?"
        );

        $check->execute([
            (int)$design['job_id'],
            $projectId,
            $userId
        ]);

        if (!$check->fetchColumn()) {
            throw new RuntimeException(
                'All four ZIP designs must be imported first.'
            );
        }

        // Clear old selection.
        $clear = $pdo->prepare(
            "UPDATE ai_generated_designs
             SET selected_at = NULL
             WHERE project_id = ?
             AND user_id = ?"
        );

        $clear->execute([
            $projectId,
            $userId
        ]);

        // Save new selection.
        $select = $pdo->prepare(
            "UPDATE ai_generated_designs
             SET selected_at = NOW()
             WHERE id = ?
             AND project_id = ?
             AND user_id = ?"
        );

        $select->execute([
            $designId,
            $projectId,
            $userId
        ]);

        $pdo->commit();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
