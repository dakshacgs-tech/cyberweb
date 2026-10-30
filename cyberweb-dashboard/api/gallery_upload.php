<?php
// A ZIP can contain image files only. Never extract original ZIP paths or filenames.
function hotel_save_gallery_bytes(PDO $pdo, string $bytes, int $projectId, int $userId, array &$writtenFiles): void {
    $maxImage = 8 * 1024 * 1024;
    if ($bytes === '' || strlen($bytes) > $maxImage) throw new RuntimeException('Each photo must be smaller than 8 MB.');
    $info = @getimagesizefromstring($bytes);
    $mime = $info['mime'] ?? '';
    $extension = ['image/jpeg'=>'.jpg', 'image/png'=>'.png', 'image/webp'=>'.webp'][$mime] ?? null;
    if ($extension === null) throw new RuntimeException('Invalid image. Only JPG, PNG and WebP photos are accepted.');
    if (($info[0] ?? 0) > 8000 || ($info[1] ?? 0) > 8000 || ($info[0] ?? 0) * ($info[1] ?? 0) > 25000000) {
        throw new RuntimeException('A photo has extremely large dimensions. Resize it before uploading.');
    }
    $folder = dirname(__DIR__) . '/storage/gallery/' . $projectId;
    if (!is_dir($folder) && !mkdir($folder, 0770, true) && !is_dir($folder)) {
        throw new RuntimeException('Cannot create gallery storage directory. Check XAMPP folder permissions.');
    }
    $fileName = bin2hex(random_bytes(12)) . $extension;
    $path = $folder . '/' . $fileName;
    if (file_put_contents($path, $bytes, LOCK_EX) !== strlen($bytes)) {
        if (is_file($path)) @unlink($path);
        throw new RuntimeException('Could not store a gallery photo. Check XAMPP folder permissions.');
    }
    $writtenFiles[] = $path;
    $stmt = $pdo->prepare('INSERT INTO gallery_images (project_id,user_id,file_name) VALUES (?,?,?)');
    $stmt->execute([$projectId, $userId, $fileName]);
}

function hotel_import_gallery_files(PDO $pdo, array $files, int $projectId, int $userId, array &$writtenFiles): int {
    $tmpNames = $files['tmp_name'] ?? [];
    if (!is_array($tmpNames)) $tmpNames = [$tmpNames];
    $errors = $files['error'] ?? [];
    $sizes = $files['size'] ?? [];
    if (!is_array($errors)) $errors = [$errors];
    if (!is_array($sizes)) $sizes = [$sizes];
    $selected = [];
    foreach ($tmpNames as $index => $path) {
        $error = (int)($errors[$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error !== UPLOAD_ERR_OK) throw new RuntimeException('Photo upload failed. Check upload_max_filesize and post_max_size in php.ini.');
        $selected[] = [$path, (int)($sizes[$index] ?? 0)];
    }
    if (count($selected) > 12) throw new RuntimeException('Please select a maximum of 12 photos at one time.');
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM gallery_images WHERE project_id=? AND user_id=?');
    $countStmt->execute([$projectId,$userId]);
    if ((int)$countStmt->fetchColumn() + count($selected) > 30) {
        throw new RuntimeException('Your gallery can have a maximum of 30 photos. Remove old photos or select fewer files.');
    }
    foreach ($selected as [$path, $size]) {
        if (!is_string($path) || !is_uploaded_file($path)) throw new RuntimeException('Invalid uploaded photo.');
        if ($size <= 0 || $size > 8 * 1024 * 1024 || filesize($path) > 8 * 1024 * 1024) {
            throw new RuntimeException('Each photo must be smaller than 8 MB.');
        }
        $bytes = file_get_contents($path);
        if ($bytes === false) throw new RuntimeException('Could not read an uploaded photo.');
        hotel_save_gallery_bytes($pdo, $bytes, $projectId, $userId, $writtenFiles);
    }
    return count($selected);
}

function hotel_direct_photos_selected(array $files): bool {
    $names = $files['name'] ?? [];
    if (!is_array($names)) $names = [$names];
    foreach ($names as $name) if (trim((string)$name) !== '') return true;
    return false;
}

function hotel_zip_selected(array $upload): bool {
    // Browsers still send a photo_gallery_zip entry when no ZIP was chosen.
    return trim((string)($upload['name'] ?? '')) !== ''
        && (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function hotel_import_gallery_zip(PDO $pdo, array $upload, int $projectId, int $userId, array &$writtenFiles): int {
    if (!hotel_zip_selected($upload)) return 0;
    // Check the selected file before checking ZipArchive, so a JPG chosen via
    // the wrong input never produces a misleading PHP-extension error.
    if (!preg_match('/\.zip$/i', (string)$upload['name'])) {
        throw new RuntimeException('JPG/PNG image ko "Select Photos" box se upload karo. ZIP wale box mein sirf .zip file select karo.');
    }
    if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Photo ZIP upload failed. Check PHP upload_max_filesize and post_max_size settings.');
    }
    if (!isset($upload['tmp_name']) || !is_uploaded_file((string)$upload['tmp_name'])) {
        throw new RuntimeException('Invalid photo ZIP upload.');
    }
    $maxZip = 20 * 1024 * 1024;
    $maxImage = 8 * 1024 * 1024;
    if ((int)($upload['size'] ?? 0) > $maxZip) {
        throw new RuntimeException('Photo ZIP must be smaller than 20 MB.');
    }
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ZIP extension is not enabled in XAMPP. Enable extension=zip in php.ini.');
    }
    $zip = new ZipArchive();
    if ($zip->open((string)$upload['tmp_name']) !== true) {
        throw new RuntimeException('Please attach a valid .zip file containing JPG, PNG or WebP photos.');
    }
    try {
        if ($zip->numFiles > 150) throw new RuntimeException('ZIP contains too many files (maximum 150 entries).');
        $existingStmt = $pdo->prepare('SELECT COUNT(*) FROM gallery_images WHERE project_id=? AND user_id=?');
        $existingStmt->execute([$projectId, $userId]);
        $remaining = 30 - (int)$existingStmt->fetchColumn();
        $imported = 0;
        $uncompressedTotal = 0;
        for ($i=0; $i<$zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat) continue;
            $name = (string)$stat['name'];
            if (str_ends_with($name, '/')) continue;
            if (str_starts_with($name, '__MACOSX/') || str_starts_with(basename($name), '._')) continue;
            if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp'], true)) continue;
            if ($imported >= $remaining) throw new RuntimeException('Gallery limit is 30 photos. Remove old photos before adding more.');
            if ((int)$stat['size'] > $maxImage) throw new RuntimeException('Each photo must be smaller than 8 MB.');
            $uncompressedTotal += (int)$stat['size'];
            if ($uncompressedTotal > 100 * 1024 * 1024) throw new RuntimeException('ZIP expands to more than 100 MB. Please use smaller photos.');
            $bytes = $zip->getFromIndex($i, $maxImage + 1);
            if ($bytes === false || strlen($bytes) > $maxImage) throw new RuntimeException('Could not safely read a photo from the ZIP.');
            hotel_save_gallery_bytes($pdo, $bytes, $projectId, $userId, $writtenFiles);
            $imported++;
        }
        if ($imported === 0) throw new RuntimeException('No JPG, PNG or WebP images found inside the ZIP.');
        return $imported;
    } finally {
        $zip->close();
    }
}
