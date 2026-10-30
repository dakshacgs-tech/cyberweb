<?php
require_once __DIR__ . '/auth/require_login.php';
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/hotel_data.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$projectId = filter_var($_GET['project_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$projectId) { http_response_code(400); exit('Invalid project ID.'); }
$userId = (int)$_SESSION['user_id'];
try {
    hotel_schema($pdo);
    $project = hotel_project($pdo, (int)$projectId, $userId);
    if (!$project) { http_response_code(404); exit('Project not found.'); }
    $hotel = hotel_record($pdo, (int)$projectId, $userId);
    if (!$hotel) { header('Location: hotel-details.php?project_id=' . (int)$projectId . '&notice=details_first', true, 303); exit; }
    if (!in_array($project['template'], ['Harbor','Willow','Urban','Minimal'], true)) {
        header('Location: templates.php?project_id=' . (int)$projectId, true, 303); exit;
    }
    $galleryStatus = hotel_gallery_availability(hotel_gallery($pdo, (int)$projectId, $userId), (int)$projectId);
    $gallery = $galleryStatus['available'];
    $missingPhotos = count($galleryStatus['missing']);
    $faqs = hotel_faqs($pdo, (int)$projectId, $userId);
    $allTemplateSettings = cyberweb_template_settings($pdo, (int)$projectId, $userId);
} catch (PDOException $e) {
    error_log('Site preview database: ' . $e->getMessage());
    http_response_code(500);
    exit('Site preview could not load. Check MySQL and import database/hotel_details.sql if needed.');
}
$theme = strtolower($project['template']);
$currentTemplateSettings = $allTemplateSettings[$project['template']] ?? [];
$siteBrand = trim((string)($currentTemplateSettings['brand'] ?? '')) ?: (string)$hotel['hotel_name'];
$siteHeadline = trim((string)($currentTemplateSettings['headline'] ?? '')) ?: $siteBrand;
$amenities = hotel_lines((string)$hotel['amenities']);
$things = hotel_lines((string)$hotel['things_to_do']);
$firstPhoto = $gallery ? 'gallery-image.php?project_id=' . (int)$projectId . '&image_id=' . (int)$gallery[0]['id'] . '&v=20261028-5' : '';
$phoneLink = preg_replace('/[^+0-9]/', '', (string)$hotel['phone']);
$notices = ['selected'=>'Template saved! This is your final editable website preview.','updated'=>'Hotel details updated. Preview refreshed with your changes.'];
$notice = $notices[(string)($_GET['notice'] ?? '')] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= hotel_escape($siteBrand) ?> — <?= hotel_escape($project['template']) ?> Preview</title>
  <link rel="stylesheet" href="css/site-preview.css?v=20261028-6">
</head>
<body class="theme-<?= hotel_escape($theme) ?>">
  <div class="site-editor-bar"><div class="site-editor-status"><span class="site-live-dot"></span><strong>CYBERWEB · Private Preview</strong><small>Team: <?= hotel_escape($project['team_member'] ?: 'Unassigned') ?> · This website is not published yet.</small></div><div class="site-editor-actions"><span class="site-template-label"><?= hotel_escape($project['template']) ?> design</span><a href="hotel-details.php?project_id=<?= (int)$projectId ?>">✎ Edit Hotel Details</a><a href="templates.php?project_id=<?= (int)$projectId ?>">◈ Change Design</a><a href="projects.php">My Projects</a></div></div>
  <?php if ($notice !== ''): ?><div class="site-notice" role="status"><?= hotel_escape($notice) ?></div><?php endif; ?>
  <?php $justAdded = filter_var($_GET['gallery_added'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>30]]); if ($justAdded): ?><div class="site-notice" role="status">✓ <?= (int)$justAdded ?> new gallery photos successfully saved.</div><?php endif; ?>
  <?php if (($_GET['gallery_notice'] ?? '') === 'zip_skipped'): ?><div class="site-photo-warning" role="status">JPG/PNG photos save ho gayi hain. Optional ZIP skip hui kyunki PHP ZIP extension disabled hai. ZIP photos add karni hain to XAMPP mein extension enable karo.</div><?php endif; ?>
  <?php if ($missingPhotos): ?><div class="site-photo-warning" role="alert"><?= $missingPhotos ?> uploaded photo file(s) missing hain. Dashboard update karte waqt <code>storage/gallery</code> folder preserve karo, ya <a href="hotel-details.php?project_id=<?= (int)$projectId ?>">Edit Hotel Details</a> mein missing records remove karke images dobara upload karo.</div><?php endif; ?>
  <div class="site-photo-warning" id="sitePhotoLoadError" role="alert" hidden>Saved photos load nahi ho pa rahi hain. <a href="hotel-details.php?project_id=<?= (int)$projectId ?>">Hotel Details</a> mein gallery verify karo aur zaroorat ho to re-upload karo.</div>
  <header class="hotel-navigation"><a class="hotel-wordmark" href="#top"><?= hotel_escape($siteBrand) ?><span>✦</span></a><nav aria-label="Hotel sections"><a href="#about">About</a><a href="#amenities">Amenities</a><a href="#experiences">Things To Do</a><a href="#gallery">Gallery</a><?php if ($faqs): ?><a href="#faqs">FAQs</a><?php endif; ?></nav><a class="hotel-contact-pill" href="#contact">Contact Us ↗</a></header>
  <main>
    <section class="hotel-hero" id="top">
      <?php if ($firstPhoto !== ''): ?><img class="hotel-hero-image" data-gallery-image src="<?= hotel_escape($firstPhoto) ?>" alt="<?= hotel_escape($hotel['hotel_name']) ?> photo" loading="eager"><?php endif; ?>
      <div class="hotel-hero-content"><p class="hotel-eyebrow">✧ &nbsp; WELCOME TO <?= hotel_escape(mb_strtoupper($siteBrand)) ?></p><h1><?= hotel_escape($siteHeadline) ?></h1><p class="hotel-subtitle">Discover a stay to remember.</p><a class="hotel-primary-button" href="#about">Discover Your Stay <span>↗</span></a></div>
      <div class="hotel-hero-footer"><span>YOUR SPACE · YOUR STORY</span><span>SCROLL TO EXPLORE ↓</span></div>
    </section>
    <section class="hotel-section hotel-about" id="about"><div class="hotel-section-kicker">01 / OUR STORY</div><div><h2>A lovely place to <em>stay.</em></h2><p>Welcome to <?= hotel_escape($hotel['hotel_name']) ?>. We're ready to help you enjoy your visit.</p><div class="hotel-address"><span>⌖</span><p><?= nl2br(hotel_escape($hotel['address'])) ?></p></div></div></section>
    <section class="hotel-section hotel-facilities" id="amenities"><div class="hotel-section-heading"><p class="hotel-section-kicker">02 / EVERYTHING YOU NEED</p><h2>Thoughtful <em>amenities.</em></h2><p>Comfort in every detail.</p></div><div class="hotel-feature-grid">
      <?php if ($amenities): foreach ($amenities as $i => $amenity): ?><article class="hotel-feature"><div class="hotel-feature-icon"><?= ['✦','☼','◇','❋','✧','⌁'][$i % 6] ?></div><h3><?= hotel_escape($amenity) ?></h3><p>Available at our hotel</p></article><?php endforeach; else: ?><p class="hotel-empty">Amenities will appear here once you add them in the Hotel Details editor.</p><?php endif; ?>
    </div></section>
    <section class="hotel-section hotel-experiences" id="experiences"><div class="hotel-section-heading"><p class="hotel-section-kicker">03 / DISCOVER MORE</p><h2>Things to <em>do.</em></h2><p>Explore experiences in and around the hotel.</p></div><div class="hotel-experience-list">
      <?php if ($things): foreach ($things as $i => $thing): ?><div class="hotel-experience"><span><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span><strong><?= hotel_escape($thing) ?></strong><span aria-hidden="true">↗</span></div><?php endforeach; else: ?><p class="hotel-empty">Things To Do will appear here once you add them in the Hotel Details editor.</p><?php endif; ?>
    </div></section>
    <section class="hotel-section hotel-gallery-section" id="gallery"><div class="hotel-section-heading"><p class="hotel-section-kicker">04 / MOMENTS AT OUR HOTEL</p><h2>Photo <em>gallery.</em></h2><p>A glimpse of <?= hotel_escape($hotel['hotel_name']) ?>. <strong><?= count($gallery) ?> saved photos</strong></p></div><div class="hotel-gallery-inline-error" id="siteGalleryInlineError" role="alert" hidden>Photo load nahi ho pa rahi. <a href="hotel-details.php?project_id=<?= (int)$projectId ?>">Edit Hotel Details</a> par saved images verify karo. <span id="siteGalleryErrorReason"></span></div><div class="hotel-gallery-grid">
      <?php if ($gallery): foreach ($gallery as $i => $photo): ?><figure class="hotel-gallery-photo"><img data-gallery-image src="gallery-image.php?project_id=<?= (int)$projectId ?>&amp;image_id=<?= (int)$photo['id'] ?>&amp;v=20261028-5" alt="<?= hotel_escape($hotel['hotel_name']) ?> gallery image <?= $i + 1 ?>" loading="lazy"></figure><?php endforeach; else: ?><div class="hotel-gallery-placeholder">Gallery mein abhi photos nahi hain. <a href="hotel-details.php?project_id=<?= (int)$projectId ?>">Edit Hotel Details</a> mein 10–12 JPG/PNG photos ya ZIP upload karke Save karo.</div><?php endif; ?>
    </div></section>
    <?php if ($faqs): ?>
    <section class="hotel-section hotel-faq-section" id="faqs" aria-labelledby="hotelFaqHeading"><div class="hotel-section-heading"><p class="hotel-section-kicker">05 / GOOD TO KNOW</p><h2 id="hotelFaqHeading">Frequently asked <em>questions.</em></h2><p>Helpful answers before your stay.</p></div>
      <div class="hotel-faq-list">
        <?php foreach ($faqs as $position => $faq): ?>
        <details class="hotel-faq"><summary><span class="hotel-faq-number"><?= str_pad((string)$position, 2, '0', STR_PAD_LEFT) ?></span><span class="hotel-faq-question"><?= hotel_escape($faq['question']) ?></span><span class="hotel-faq-toggle" aria-hidden="true">+</span></summary><div class="hotel-faq-answer"><?= nl2br(hotel_escape($faq['answer'])) ?></div></details>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
    <section class="hotel-contact" id="contact"><div><span>LET'S CONNECT</span><h2>We'd love to <em>hear from you.</em></h2><p><?= nl2br(hotel_escape($hotel['address'])) ?></p></div><a href="tel:<?= hotel_escape($phoneLink) ?>">Call <?= hotel_escape($hotel['phone']) ?> <span>↗</span></a></section>
  </main>
  <footer class="hotel-footer"><strong><?= hotel_escape($siteBrand) ?></strong><span>Preview design: <?= hotel_escape($project['template']) ?> · Not published</span><a href="hotel-details.php?project_id=<?= (int)$projectId ?>">Edit content ↗</a></footer>
  <script src="js/gallery-display.js?v=20261028-5" defer></script>
</body>
</html>
