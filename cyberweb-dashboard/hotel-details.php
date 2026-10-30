<?php
require_once __DIR__ . '/auth/require_login.php';
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/hotel_data.php';
require_once __DIR__ . '/api/gallery_upload.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$userId = (int)$_SESSION['user_id'];
$projectId = filter_var($_POST['project_id'] ?? $_GET['project_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$projectId) { http_response_code(400); exit('Invalid project. Open My Projects and try again.'); }
try {
    hotel_schema($pdo);
    $project = hotel_project($pdo, (int)$projectId, $userId);
    if (!$project) { http_response_code(404); exit('Project not found.'); }
    $saved = hotel_record($pdo, (int)$projectId, $userId);
    $savedFaqs = hotel_faqs($pdo, (int)$projectId, $userId);
    $gallery = hotel_gallery($pdo, (int)$projectId, $userId);
    $galleryStatus = hotel_gallery_availability($gallery, (int)$projectId);
    $missingPhotoIds = array_fill_keys(array_map('intval', array_column($galleryStatus['missing'], 'id')), true);
} catch (PDOException $e) {
    error_log('Hotel details setup: ' . $e->getMessage());
    http_response_code(500);
    exit('Hotel details tables are not ready. Import database/hotel_details.sql into cyberweb using phpMyAdmin.');
}
$data = $saved ?: [
    'hotel_name'=>$project['property_name'] ?: $project['name'],
    'phone'=>'', 'address'=>'', 'amenities'=>'', 'things_to_do'=>''
];
$faqInputs = [];
for ($position = 1; $position <= 3; $position++) {
    $faqInputs[$position] = $savedFaqs[$position] ?? ['question'=>'', 'answer'=>''];
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf()) {
        if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $error = 'Upload PHP post_max_size limit se bada hai. XAMPP php.ini mein post_max_size=128M aur upload_max_filesize=25M set karke Apache restart karo.';
        } else { http_response_code(403); exit('Session expired. Refresh and try again.'); }
    }
    if ($error === '') {
    foreach (['hotel_name','phone','address','amenities','things_to_do'] as $field) {
        $data[$field] = trim((string)($_POST[$field] ?? ''));
    }
    $submittedQuestions = is_array($_POST['faq_question'] ?? null) ? $_POST['faq_question'] : [];
    $submittedAnswers = is_array($_POST['faq_answer'] ?? null) ? $_POST['faq_answer'] : [];
    $invalidFaq = false;
    for ($position = 1; $position <= 3; $position++) {
        $question = $submittedQuestions[$position] ?? '';
        $answer = $submittedAnswers[$position] ?? '';
        if (!is_string($question) || !is_string($answer)) $invalidFaq = true;
        $question = is_string($question) ? trim($question) : '';
        $answer = is_string($answer) ? trim($answer) : '';
        $faqInputs[$position] = ['question'=>$question, 'answer'=>$answer];
        if (($question === '') !== ($answer === '') || mb_strlen($question) > 250 || mb_strlen($answer) > 1500) {
            $invalidFaq = true;
        }
    }
    $deleteIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['delete_photo_ids'] ?? [])), fn($id) => $id > 0)));
    if ($invalidFaq) {
        $error = 'Har FAQ mein Question aur Answer dono fill karo (ya dono blank chhodo). Question max 250 aur Answer max 1500 characters ka ho.';
    } elseif ($data['hotel_name'] === '' || mb_strlen($data['hotel_name']) > 150 ||
        $data['phone'] === '' || mb_strlen($data['phone']) > 40 ||
        $data['address'] === '' || mb_strlen($data['address']) > 1500 ||
        mb_strlen($data['amenities']) > 5000 || mb_strlen($data['things_to_do']) > 5000 ||
        count($deleteIds) > 30) {
        $error = 'Hotel name, phone and address zaroori hain. Details ki length bhi check karein.';
    } else {
        $writtenFiles = [];
        $filesToRemove = [];
        $directPhotosSelected = isset($_FILES['gallery_images']) && hotel_direct_photos_selected($_FILES['gallery_images']);
        $zipUpload = isset($_FILES['photo_gallery_zip']) && is_array($_FILES['photo_gallery_zip'])
            ? $_FILES['photo_gallery_zip'] : [];
        $zipSelected = hotel_zip_selected($zipUpload);
        $zipSkipped = false;
        $photosAdded = 0;
        try {
            $pdo->beginTransaction();
            $save = $pdo->prepare('INSERT INTO hotel_details (project_id,user_id,hotel_name,phone,address,amenities,things_to_do)
                VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),hotel_name=VALUES(hotel_name),phone=VALUES(phone),address=VALUES(address),amenities=VALUES(amenities),things_to_do=VALUES(things_to_do)');
            $save->execute([(int)$projectId,$userId,$data['hotel_name'],$data['phone'],$data['address'],$data['amenities'],$data['things_to_do']]);
            // Replace only this project's FAQ rows within the same transaction.
            // Blank pairs remove a previous FAQ; other projects remain unchanged.
            $pdo->prepare('DELETE FROM hotel_faqs WHERE project_id=? AND user_id=?')->execute([(int)$projectId,$userId]);
            $saveFaq = $pdo->prepare('INSERT INTO hotel_faqs (project_id,user_id,faq_position,question,answer) VALUES (?,?,?,?,?)');
            foreach ($faqInputs as $position => $faq) {
                if ($faq['question'] !== '') {
                    $saveFaq->execute([(int)$projectId,$userId,$position,$faq['question'],$faq['answer']]);
                }
            }
            $selectPhoto = $pdo->prepare('SELECT file_name FROM gallery_images WHERE id=? AND project_id=? AND user_id=?');
            $deletePhoto = $pdo->prepare('DELETE FROM gallery_images WHERE id=? AND project_id=? AND user_id=?');
            foreach ($deleteIds as $id) {
                $selectPhoto->execute([$id,(int)$projectId,$userId]);
                $fileName = $selectPhoto->fetchColumn();
                if (!$fileName) continue;
                $deletePhoto->execute([$id,(int)$projectId,$userId]);
                $filesToRemove[] = __DIR__ . '/storage/gallery/' . (int)$projectId . '/' . basename((string)$fileName);
            }
            if (isset($_FILES['gallery_images'])) {
                $photosAdded += hotel_import_gallery_files($pdo, $_FILES['gallery_images'], (int)$projectId, $userId, $writtenFiles);
            }
            if ($zipSelected) {
                if ($directPhotosSelected && !class_exists('ZipArchive')) {
                    // Never roll back valid JPG/PNG uploads because an optional
                    // ZIP was also selected on a machine without PHP ZIP support.
                    $zipSkipped = true;
                } else {
                    $photosAdded += hotel_import_gallery_zip($pdo, $zipUpload, (int)$projectId, $userId, $writtenFiles);
                }
            }
            $pdo->commit();
            foreach ($filesToRemove as $path) if (is_file($path)) @unlink($path);
            $target = in_array($project['template'], ['Harbor','Willow','Urban','Minimal'], true)
                ? 'preview-site.php?project_id=' . (int)$projectId . '&notice=updated'
                : 'templates.php?project_id=' . (int)$projectId;
            if ($zipSkipped) $target .= '&gallery_notice=zip_skipped';
            if ($photosAdded > 0) $target .= '&gallery_added=' . $photosAdded;
            header('Location: ' . $target, true, 303);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            foreach ($writtenFiles as $path) if (is_file($path)) @unlink($path);
            error_log('Hotel details save: ' . $e->getMessage());
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Could not save hotel details. Check the MySQL and PHP error logs.';
        }
    }
    }
}
$editing = $saved !== null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $editing ? 'Edit Hotel Details' : 'Add Hotel Details' ?> | CYBERWEB</title>
  <link rel="stylesheet" href="css/hotel-details.css?v=20261028-6">
</head>
<body>
  <header class="hd-header"><a href="index.php" class="hd-brand"><span>✦</span> CYBERWEB <small>WEBSITE AUTOMATION</small></a><nav><a href="projects.php">My Projects</a><a href="templates.php">Templates</a></nav></header>
  <main class="hd-container">
    <div class="hd-progress" aria-label="Website setup steps">
      <div class="hd-step done"><span>✓</span><div><small>STEP 01</small><strong>Create Project</strong></div></div>
      <div class="hd-line"></div>
      <div class="hd-step current"><span>2</span><div><small>STEP 02</small><strong>Hotel Details</strong></div></div>
      <div class="hd-line"></div>
      <div class="hd-step <?= in_array($project['template'],['Harbor','Willow','Urban','Minimal'],true) ? 'done' : '' ?>"><span><?= in_array($project['template'],['Harbor','Willow','Urban','Minimal'],true) ? '✓' : '3' ?></span><div><small>STEP 03</small><strong>Select Template</strong></div></div>
    </div>
    <div class="hd-title-row"><div><p class="hd-overline">✧ &nbsp; YOUR HOTEL CONTENT</p><h1><?= $editing ? 'Edit hotel ' : 'Tell us about your ' ?><em><?= $editing ? 'details.' : 'hotel.' ?></em></h1><p>Hotel ki information aur photos add karo. Ye details tumhari selected website design mein dikhenge.</p></div><a class="hd-back" href="projects.php">← My Projects</a></div>
    <div class="hd-project-label"><div class="hd-project-icon">✦</div><div><span>WORKING ON PROJECT</span><strong><?= hotel_escape($project['name']) ?></strong></div><span class="hd-saved">Project saved ✓</span></div>
    <?php if (($_GET['notice'] ?? '') === 'details_first'): ?><div class="hd-info">Pehle hotel details save karo, phir 4 website designs select kar sakogi.</div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="hd-error" role="alert"><?= hotel_escape($error) ?></div><?php endif; ?>
    <div class="hd-content">
      <form method="post" enctype="multipart/form-data" class="hd-form" id="hotelDetailsForm">
        <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
        <input type="hidden" name="csrf_token" value="<?= hotel_escape(csrf_token()) ?>">
        <section class="hd-panel"><div class="hd-panel-head"><span class="hd-index">01</span><div><h2>Hotel Information</h2><p>Basic details jo website par show hongi.</p></div></div>
          <div class="hd-grid"><label>Hotel Name <span>*</span><input name="hotel_name" maxlength="150" required value="<?= hotel_escape($data['hotel_name']) ?>" placeholder="e.g. Days Inn Mumbai"></label><label>Phone No <span>*</span><input name="phone" type="tel" maxlength="40" required value="<?= hotel_escape($data['phone']) ?>" placeholder="e.g. +91 98765 43210"></label></div>
          <label>Address <span>*</span><textarea name="address" maxlength="1500" rows="3" required placeholder="Hotel ka full address, city aur PIN code"><?= hotel_escape($data['address']) ?></textarea></label>
        </section>
        <section class="hd-panel"><div class="hd-panel-head"><span class="hd-index">02</span><div><h2>Amenities</h2><p>Hotel mein kya facilities available hain?</p></div></div>
          <label>Amenities <small>(har line par ek facility)</small><textarea name="amenities" maxlength="5000" rows="5" placeholder="Free Wi-Fi&#10;Parking&#10;Swimming Pool&#10;Breakfast&#10;Air Conditioning"><?= hotel_escape($data['amenities']) ?></textarea></label>
        </section>
        <section class="hd-panel"><div class="hd-panel-head"><span class="hd-index">03</span><div><h2>Things To Do</h2><p>Hotel ke aas-paas ki activities aur attractions.</p></div></div>
          <label>Things To Do <small>(har line par ek activity)</small><textarea name="things_to_do" maxlength="5000" rows="5" placeholder="Gateway of India sightseeing&#10;Local food tour&#10;Beach walk&#10;Shopping markets"><?= hotel_escape($data['things_to_do']) ?></textarea></label>
        </section>
        <section class="hd-panel"><div class="hd-panel-head"><span class="hd-index">04</span><div><h2>Photo Gallery</h2><p>Ek saath 10–12 JPG/PNG/WebP photos select karo, ya photos ki ZIP attach karo.</p></div></div>
          <div class="hd-info">Normal JPG/PNG photos ke liye <strong>Select Photos</strong> use karo. Ek-ek karke ya 10–12 photos ek saath select kar sakti ho; har nayi selection pehle wali mein add hogi. ZIP upload optional hai.</div>
          <div class="hd-upload-choices">
            <label class="hd-upload" for="gallery_images"><span class="hd-upload-icon">▧</span><strong>Select Photos (JPG / PNG / WebP)</strong><small>Ek baar mein 12 images tak select kar sakti ho · Har image max 8 MB</small><input id="gallery_images" name="gallery_images[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple></label>
            <label class="hd-upload" for="photo_gallery_zip"><span class="hd-upload-icon">⇧</span><strong>Optional: Attach Photo Gallery ZIP</strong><small>Sirf .zip file · XAMPP mein ZIP extension enabled honi chahiye</small><input id="photo_gallery_zip" name="photo_gallery_zip" type="file" accept=".zip,application/zip"></label>
          </div>
          <p class="hd-gallery-guidance">Dono options use kar sakti ho. Gallery maximum 30 photos rakhegi. Nayi photos <strong>Save</strong> karne ke baad permanent gallery mein dikhengi.</p>
          <p class="hd-file-name" id="zipFileName">No ZIP selected. ZIP ke andar ki photos Save ke baad preview hongi.</p>
          <div class="hd-upload-feedback" id="galleryUploadFeedback" role="status" aria-live="polite"></div>
          <div class="hd-selected-grid" id="galleryNewPreview" aria-label="Selected new gallery image previews" data-existing-count="<?= count($gallery) ?>" hidden></div>
          <?php if ($missingPhotoIds): ?><div class="hd-missing-warning" role="alert"><strong><?= count($missingPhotoIds) ?> image file(s) missing!</strong> Purani gallery files shayad dashboard folder replace karte waqt delete ho gayi hain. Neeche missing photos ka <strong>Remove</strong> checkbox select karo, fresh images upload karo, phir Save karo. Agar backup hai, purana <code>storage/gallery</code> folder restore kar sakti ho.</div><?php endif; ?>
          <?php if ($gallery): ?><div class="hd-gallery-existing"><h3>Uploaded Photos <span><?= count($galleryStatus['available']) ?> available / <?= count($gallery) ?> saved</span></h3><p>Jo photos remove karni hain, unke niche checkbox tick karo.</p><div class="hd-gallery-grid"><?php foreach ($gallery as $photo): ?><label class="hd-gallery-item"><?php if (isset($missingPhotoIds[(int)$photo['id']])): ?><div class="hd-missing-tile">⚠<small>Photo file missing</small></div><?php else: ?><img src="gallery-image.php?project_id=<?= (int)$projectId ?>&amp;image_id=<?= (int)$photo['id'] ?>&amp;v=20261028-5" alt="Saved hotel gallery photo" loading="lazy"><?php endif; ?><span><input type="checkbox" name="delete_photo_ids[]" value="<?= (int)$photo['id'] ?>"> <?= isset($missingPhotoIds[(int)$photo['id']]) ? 'Remove missing' : 'Remove' ?></span></label><?php endforeach; ?></div></div><?php endif; ?>
        </section>
        <section class="hd-panel" id="hotelFaqs"><div class="hd-panel-head"><span class="hd-index">05</span><div><h2>Frequently Asked Questions</h2><p>Har website ke liye maximum 3 FAQs add karo. Question aur Answer dono fill karo.</p></div></div>
          <p class="hd-faq-guidance">FAQs optional hain. Jo question-answer pair blank hoga, woh website par nahi dikhega. Saved FAQs template badalne par bhi isi project mein rahenge.</p>
          <div class="hd-faq-list">
            <?php for ($position = 1; $position <= 3; $position++): ?>
              <div class="hd-faq-card"><div class="hd-faq-card-title"><span>FAQ <?= $position ?></span><small>Question + Answer</small></div>
                <label>Question <?= $position ?><input name="faq_question[<?= $position ?>]" maxlength="250" value="<?= hotel_escape($faqInputs[$position]['question']) ?>" placeholder="<?= $position === 1 ? 'e.g. What time is check-in and check-out?' : ($position === 2 ? 'e.g. Is breakfast included?' : 'e.g. Is parking available?') ?>"></label>
                <label>Answer <?= $position ?><textarea name="faq_answer[<?= $position ?>]" maxlength="1500" rows="3" placeholder="Is question ka answer likho..."><?= hotel_escape($faqInputs[$position]['answer']) ?></textarea></label>
              </div>
            <?php endfor; ?>
          </div>
        </section>
        <div class="hd-actions"><span>Details save hone ke baad website ka design choose karna hai.</span><button type="submit"><?= in_array($project['template'],['Harbor','Willow','Urban','Minimal'],true) ? 'Save Changes & View Website' : 'Save & Continue to Templates' ?> <span aria-hidden="true">→</span></button></div>
      </form>
      <aside class="hd-aside"><div class="hd-aside-tag">✦ &nbsp; NEXT STEP</div><h3>Your hotel, your style.</h3><p>Details save karne ke baad tum 4 beautiful website designs preview kar sakogi.</p><div class="hd-preview-orb">H</div><ul><li>✓ Hotel information</li><li>✓ Amenities &amp; activities</li><li>✓ 12 Photos or Gallery ZIP</li><li>✓ Up to 3 FAQs</li><li>→ Choose final design</li><li>→ Edit details any time</li></ul><div class="hd-tip"><strong>Photo Gallery</strong><code>JPG · PNG · WebP · ZIP</code><span>10–12 images ek saath select karo. ZIP bhi attach kar sakti ho.</span></div></aside>
    </div>
  </main>
  <script src="js/gallery-upload.js?v=20261028-3"></script>
</body>
</html>
