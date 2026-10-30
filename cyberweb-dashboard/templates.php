<?php
require_once __DIR__ . '/auth/require_login.php';
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/hotel_data.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
function template_escape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$availableTemplates = ['Harbor', 'Willow', 'Urban', 'Minimal'];
$teamMembers = cyberweb_team_members();
$errorMessage = '';
try { cyberweb_team_schema($pdo); }
catch (PDOException $e) { error_log('Template team schema: '.$e->getMessage()); http_response_code(500); exit('Team member tables not ready. Run database/team_members_upgrade.sql in phpMyAdmin.'); }
$projectIdRaw = $_POST['project_id'] ?? $_GET['project_id'] ?? null;
$projectId = $projectIdRaw === null ? null : filter_var($projectIdRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if ($projectIdRaw !== null && $projectId === false) {
    http_response_code(400);
    exit('Invalid project ID. Return to My Projects and try again.');
}
$project = null;
$savedTemplateSettings = [];
if ($projectId !== null) {
    $stmt = $pdo->prepare('SELECT id, name, property_name, location, team_member, template FROM projects WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$projectId, (int)$_SESSION['user_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$project) {
        http_response_code(404);
        exit('Project not found. Return to My Projects and try again.');
    }
    try {
        hotel_schema($pdo);
        $hotelDetails = hotel_record($pdo, (int)$projectId, (int)$_SESSION['user_id']);
    } catch (PDOException $e) {
        error_log('Template hotel check: ' . $e->getMessage());
        http_response_code(500);
        exit('Hotel details database not ready. Import database/hotel_details.sql in phpMyAdmin.');
    }
    if (!$hotelDetails) {
        header('Location: hotel-details.php?project_id=' . (int)$projectId . '&notice=details_first', true, 303);
        exit;
    }
    $photoStatus = hotel_gallery_availability(hotel_gallery($pdo, (int)$projectId, (int)$_SESSION['user_id']), (int)$projectId);
    $hotelPhotos = $photoStatus['available'];
    $missingPhotos = count($photoStatus['missing']);
    $hotelCoverUrl = $hotelPhotos ? 'gallery-image.php?project_id=' . (int)$projectId . '&image_id=' . (int)$hotelPhotos[0]['id'] . '&v=20261028-5' : '';
    $savedTemplateSettings = cyberweb_template_settings($pdo, (int)$projectId, (int)$_SESSION['user_id']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_preview') {
    header('Content-Type: application/json; charset=utf-8');
    if (!valid_csrf()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Session expired. Refresh and try again.']); exit; }
    $chosen = trim((string)($_POST['template'] ?? ''));
    $brand = trim((string)($_POST['brand_name'] ?? ''));
    $headline = trim((string)($_POST['headline'] ?? ''));
    if (!$project || !in_array($chosen,$availableTemplates,true) || mb_strlen($brand)>150 || mb_strlen($headline)>160) {
        http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid project or template text.']); exit;
    }
    try {
        cyberweb_save_template_setting($pdo,(int)$projectId,(int)$_SESSION['user_id'],$chosen,$brand,$headline);
        echo json_encode(['ok'=>true,'message'=>'Preview text saved in MySQL.']);
    } catch (PDOException $e) {
        error_log('Save preview text: '.$e->getMessage());
        http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Could not save preview in database.']);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf()) {
        http_response_code(403);
        exit('Session expired. Refresh the page and try again.');
    }
    $template = trim((string)($_POST['template'] ?? ''));
    $projectName = trim((string)($_POST['name'] ?? ''));
    $hotelName = trim((string)($_POST['property_name'] ?? ''));
    $teamMember = trim((string)($_POST['team_member'] ?? ''));
    $brand = trim((string)($_POST['brand_name'] ?? ''));
    $headline = trim((string)($_POST['headline'] ?? ''));
    if (!in_array($template, $availableTemplates, true)
        || mb_strlen($brand) > 150 || mb_strlen($headline) > 160
        || (!$project && ($projectName === '' || mb_strlen($projectName) > 150
        || mb_strlen($hotelName) > 150 || !in_array($teamMember,$teamMembers,true)))) {
        $errorMessage = 'Please select a template and enter valid project details.';
    } else {
        try {
            if ($project) {
                // Attach the chosen template to the existing project, never create a duplicate.
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('UPDATE projects SET template = ? WHERE id = ? AND user_id = ?');
                $stmt->execute([$template, $projectId, (int)$_SESSION['user_id']]);
                cyberweb_save_template_setting($pdo,(int)$projectId,(int)$_SESSION['user_id'],$template,$brand,$headline);
                $pdo->commit();
                header('Location: preview-site.php?project_id=' . (int)$projectId . '&notice=selected', true, 303);
                exit;
            } else {
                // Browse-first users also follow the project -> hotel details -> templates sequence.
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO projects (user_id, name, property_name, location, team_member, template, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([(int)$_SESSION['user_id'], $projectName, $hotelName, '', $teamMember, '', 'draft']);
                $newProjectId = (int)$pdo->lastInsertId();
                cyberweb_save_template_setting($pdo,$newProjectId,(int)$_SESSION['user_id'],$template,$brand,$headline);
                $pdo->commit();
                header('Location: hotel-details.php?project_id=' . $newProjectId, true, 303);
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Template create error: ' . $e->getMessage());
            $errorMessage = 'Project save nahi hua. Confirm karein ki cyberweb database mein projects table imported hai.';
        }
    }
}
?>
<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <meta name="theme-color" content="#0a1020">

    <meta name="description" content="CYBERWEB  dashboard">

    <title>Templates | CYBERWEB</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link

        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"

        rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/templates.css?v=20261028-7">

    <style>

        /* Dashboard Logout button — uses existing theme colors */

        .dashboard-signin-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 9px;

            min-height: 38px;

            padding: 0 17px;

            border-radius: 10px;

            background: linear-gradient(125deg, #7254ef, #8d6afa);

            color: #fff;

            border: 1px solid transparent;

            font-size: 12px;

            font-weight: 800;

            white-space: nowrap;

            text-decoration: none;

            box-shadow: 0 5px 16px rgba(114, 84, 239, .19);

            transition: transform .25s ease, box-shadow .25s ease, background .25s ease;

        }

        .dashboard-signin-btn:hover {

            color: #fff;

            transform: translateY(-2px);

            box-shadow: 0 10px 24px rgba(114, 84, 239, .30);

            background: linear-gradient(125deg, #6545e0, #7859ef);

        }

        .dashboard-signin-btn:focus-visible {

            outline: 3px solid #a48aff;

            outline-offset: 3px;

        }

        .dashboard-signin-btn svg { width: 17px; height: 17px; flex: none; }

        @media (max-width: 560px) {

            .top-right { gap: 9px; }

            .dashboard-signin-btn { min-height: 35px; padding: 0 12px; font-size: 11px; gap: 6px; }

        }

        @media (prefers-reduced-motion: reduce) {

            .dashboard-signin-btn { transition: none; }

        }

    </style>

</head>

<body>

    <div class="app-shell" id="appShell">

        <aside class="sidebar" id="sidebar" aria-label="Main navigation"><a class="brand" href="index.php"><span

                    class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 40" fill="none">

                        <path d="M9 28V12l11-6 11 6v16l-11 6-11-6Z" stroke="currentColor" stroke-width="2.5" />

                        <path d="m14 23 6-10 6 10M16 20h8" stroke="currentColor" stroke-width="2.5"

                            stroke-linecap="round" />

                    </svg></span><span>CYBERWEB<small>WEBSITE AUTOMATION</small></span></a>

            <div class="workspace-pill"><span class="workspace-avatar">H</span><span><strong>Creative

                        Workspace</strong><small>Standard workspace</small></span><span aria-hidden="true">⌄</span>

            </div>

            <div class="nav-label">WORKSPACE</div>

            <nav class="side-nav">

    <!-- Overview -->
    <a class="nav-link"
       href="index.php">
        <span class="nav-symbol" aria-hidden="true">▦</span>
        Overview
    </a>

    <!-- My Projects -->
    <a class="nav-link" href="projects.php">
        <span class="nav-symbol" aria-hidden="true">▤</span>
        My Projects
    </a>

    <!-- Templates -->
    <a class="nav-link active" aria-current="page" href="templates.php">
        <span class="nav-symbol" aria-hidden="true">◈</span>
        Templates
    </a>

    <!-- Media Library -->
    <a class="nav-link" href="media-manager.html">
        <span class="nav-symbol" aria-hidden="true">▧</span>
        Media Library
    </a>

    <!-- Users -->
    <a class="nav-link" href="users.php">
        <span class="nav-symbol" aria-hidden="true">♙</span>
        Users
        <span class="nav-badge">DB</span>
    </a>

</nav>


            <div class="nav-label second-label">MANAGEMENT</div>

            <nav class="side-nav"><a class="nav-link" href="revisions.html"><span class="nav-symbol">◷</span>Revision

                    History</a><a class="nav-link" href="exports.html"><span class="nav-symbol">⇩</span>Exports</a><a

                    class="nav-link" href="settings.html"><span class="nav-symbol">⚙</span>Settings</a></nav>

            <div class="side-bottom">

                <div class="help-tile"><span class="help-glow"></span><span class="help-icon">✦</span><strong>Make your

                        next idea real.</strong>

                    <p>Build your next hotel website from one workspace.</p><a href="projects.php#new-project">Start a

                        project <span aria-hidden="true">↗</span></a>

                </div>

                <div class="sidebar-foot">© 2026 CYBERWEB </div>

            </div>

        </aside>

        <div class="workspace">

            <header class="topbar">

                <div class="top-left"><button class="mobile-menu icon-btn" id="menuToggle" type="button"

                        aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">☰</button><span

                        class="breadcrumb">Workspace <span aria-hidden="true">/</span> <strong>Templates</strong></span>

                </div>

                <div class="top-right">

                    <span class="top-live"><i></i> Studio online</span>

                    <form action="auth/logout.php" method="post" style="margin:0">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                        <button type="submit" class="dashboard-signin-btn" aria-label="Log out of dashboard">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 17l5-5-5-5M14 12H3M12 3h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6"/></svg>

                            <span>Logout</span>

                        </button>

                    </form>

                    <a class="avatar-button" href="settings.html" aria-label="Account settings">HF</a>

                </div>

            </header>

            <main class="main-content tm-main<?= $project ? ' tm-choice-mode' : '' ?>" id="main">
              <div class="tm-heading">
                <div><div class="tm-eyebrow"><?= $project ? '✦ &nbsp; STEP 03 / SELECT TEMPLATE' : '✦ &nbsp; DESIGN LIBRARY / CURATED COLLECTION' ?></div>
                  <h1><?= $project ? 'Choose your ' : 'Find your next ' ?><span><?= $project ? 'website template.' : 'great design.' ?></span></h1>
                  <p><?= $project ? 'Project aur hotel details save ho gaye! Ab 4 designs preview karke final template choose karo.' : 'Four thoughtfully crafted hotel website directions, ready to make your own.' ?></p></div>
                <a class="tm-myprojects" href="projects.php">My Projects <span aria-hidden="true">↗</span></a>
              </div>
              <?php if ($project): ?>
              <section class="tm-project-step" aria-label="Project setup progress">
                <div class="tm-step-progress">
                  <div class="tm-step-item tm-step-done"><span class="tm-step-number" aria-hidden="true">✓</span><div><small>STEP 01 · DONE</small><strong>Create project</strong></div></div>
                  <span class="tm-step-connector" aria-hidden="true"></span>
                  <div class="tm-step-item tm-step-done"><span class="tm-step-number" aria-hidden="true">✓</span><div><small>STEP 02 · DONE</small><strong>Hotel details</strong></div></div>
                  <span class="tm-step-connector" aria-hidden="true"></span>
                  <div class="tm-step-item tm-step-active" aria-current="step"><span class="tm-step-number" aria-hidden="true">3</span><div><small>STEP 03 · CURRENT</small><strong>Select template</strong></div></div>
                </div>
                <div class="tm-project-details">
                  <div class="tm-project-emblem" aria-hidden="true">✦</div>
                  <div class="tm-project-description"><span>SELECTING TEMPLATE FOR</span><strong><?= template_escape($hotelDetails['hotel_name']) ?></strong>
                    <p>Team Member: <?= template_escape($project['team_member'] ?: 'Unassigned') ?> · <?= template_escape($project['name']) ?> · Preview the designs below.</p></div>
                  <span class="tm-draft-badge"><?= $project['template'] === '' ? 'Draft saved' : 'Change design' ?></span>
                </div>
              </section>
              <?php endif; ?>
              <?php if ($errorMessage !== ''): ?><div class="tm-error" role="alert"><?= template_escape($errorMessage) ?></div><?php endif; ?>
              <?php $justAdded = filter_var($_GET['gallery_added'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>30]]); if ($project && $justAdded): ?><div class="tm-photo-ready">✓ <?= (int)$justAdded ?> new gallery photos successfully saved.</div><?php endif; ?>
              <?php if ($project && ($_GET['gallery_notice'] ?? '') === 'zip_skipped'): ?><div class="tm-photo-alert" role="status">JPG/PNG photos successfully save ho gayi. Optional ZIP skip ho gayi kyunki PHP ZIP extension disabled hai.</div><?php endif; ?>
              <?php if ($project && !empty($missingPhotos)): ?><div class="tm-photo-alert" role="alert"><?= (int)$missingPhotos ?> gallery photo file(s) missing hain. <a href="hotel-details.php?project_id=<?= (int)$projectId ?>">Hotel Details</a> mein missing records remove karke images re-upload karo.</div><?php endif; ?>
              <?php if ($project): ?><div class="tm-photo-alert" role="alert" id="tmGalleryLoadError" hidden>Photo database mein saved hai, lekin browser mein load nahi ho rahi. <a href="hotel-details.php?project_id=<?= (int)$projectId ?>">Hotel Details</a> mein check karo; XAMPP Apache logs bhi dekho.</div><?php endif; ?>
              <?php if ($project && $hotelPhotos): ?><div class="tm-photo-ready">✓ <?= count($hotelPhotos) ?> saved hotel photos · Gallery ke photos 4 designs mein preview honge. Final website mein poori gallery dikhegi.</div><?php elseif ($project): ?><div class="tm-photo-alert">Abhi koi saved gallery image nahi mili. <a href="hotel-details.php?project_id=<?= (int)$projectId ?>">Hotel Details mein JPG/PNG ya ZIP upload karo</a> aur Save karo.</div><?php endif; ?>
              <?php if (!$project): ?>
              <section class="tm-hero" aria-label="Template introduction"><div class="tm-hero-copy"><span class="tm-hero-tag">YOUR CREATIVE STARTING POINT</span><h2>Beautiful foundations.<br><em>Made for your vision.</em></h2><p>Choose a template, customize your hotel details, and keep everything organized in My Projects.</p><a href="#templateCollection" class="tm-hero-btn">Explore templates <span aria-hidden="true">↗</span></a></div><div class="tm-hero-art" aria-hidden="true"><div class="tm-float tm-float-back"></div><div class="tm-float tm-float-front"><div class="tm-window-chrome"><i></i><i></i><i></i></div><div class="tm-float-scene"><small>THE COLLECTION</small><strong>Stay<br>inspired.</strong><span>EXPLORE HOTELS ↗</span></div></div><div class="tm-star">✦</div></div></section>
              <?php endif; ?>
              <section id="templateCollection" class="tm-collection" aria-labelledby="tmCollectionTitle"><div class="tm-collection-head"><div><div class="tm-eyebrow"><?= $project ? 'CHOOSE ONE OF FOUR DESIGNS' : 'PICK THE PERFECT START' ?></div><h2 id="tmCollectionTitle"><?= $project ? 'Select your final template' : 'Explore templates' ?> <span class="tm-count">04</span></h2><?php if ($project): ?><p class="tm-choice-help">Preview any design, then click Use Template to apply it to <?= template_escape($hotelDetails['hotel_name']) ?>. You can edit hotel details later.</p><?php endif; ?></div><div class="tm-toolbar"><label class="tm-search"><span aria-hidden="true">⌕</span><span class="sr-only">Search templates</span><input type="search" id="templateSearch" placeholder="Search templates..."></label><label class="tm-filter-label"><span class="sr-only">Filter templates by style</span><select id="templateFilter"><option value="all">All styles</option><option value="luxury">Luxury</option><option value="nature">Nature</option><option value="modern">Modern</option><option value="minimal">Minimal</option></select></label></div></div>
                <div class="tm-grid" id="templateGrid" data-project-id="<?= $project ? (int)$projectId : '' ?>" data-hotel-brand="<?= $project ? template_escape($hotelDetails['hotel_name']) : '' ?>" data-gallery-cover="<?= $project ? template_escape($hotelCoverUrl) : '' ?>" data-gallery-ids="<?= $project ? implode(',', array_map('intval', array_column($hotelPhotos, 'id'))) : '' ?>" data-saved-settings="<?= template_escape(json_encode($savedTemplateSettings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>" data-csrf="<?= template_escape(csrf_token()) ?>">
                  <article class="tm-card" data-title="harbor luxury elegant coastal premium" data-style="luxury"><div class="tm-preview tm-harbor"><div class="tm-browser"><span></span><span></span><span></span><small>harbor.hotel</small></div><div class="tm-design tm-harbor-design"><div class="tm-design-top">HARBOR <span>MENU &nbsp; ☰</span></div><div class="tm-design-center"><small>RELAX INTO THE EXTRAORDINARY</small><strong>Beyond<br>the ordinary.</strong><i>DISCOVER YOUR STAY ↗</i></div><div class="tm-design-bottom">01 &nbsp; / &nbsp; THE ART OF STAYING</div></div><span class="tm-preview-id">01</span></div><div class="tm-card-content"><div class="tm-card-row"><div><span class="tm-category">LUXURY · EDITORIAL</span><h3>Harbor <span class="tm-tag">Popular</span></h3></div><span class="tm-emoji" aria-hidden="true">✦</span></div><p>Premium, editorial design for boutique hotels and sophisticated stays.</p><div class="tm-actions"><button type="button" class="tm-btn tm-btn-quiet" data-preview="Harbor">Preview <span aria-hidden="true">↗</span></button><button type="button" class="tm-btn tm-btn-main" data-use="Harbor">Use Template <span aria-hidden="true">→</span></button></div></div></article>
                  <article class="tm-card" data-title="willow nature resort earthy calm scenic" data-style="nature"><div class="tm-preview tm-willow"><div class="tm-browser"><span></span><span></span><span></span><small>willow.retreat</small></div><div class="tm-design tm-willow-design"><div class="tm-design-top">willow <span>THE RETREAT &nbsp; ☰</span></div><div class="tm-design-center"><small>WHERE NATURE FEELS LIKE HOME</small><strong>Find your<br>stillness.</strong><i>EXPLORE THE RETREAT ↗</i></div><div class="tm-design-bottom">NATURE · COMFORT · ESCAPE</div></div><span class="tm-preview-id">02</span></div><div class="tm-card-content"><div class="tm-card-row"><div><span class="tm-category">NATURE · WELLNESS</span><h3>Willow</h3></div><span class="tm-emoji" aria-hidden="true">❋</span></div><p>Grounded colors and calm typography for resorts and mountain retreats.</p><div class="tm-actions"><button type="button" class="tm-btn tm-btn-quiet" data-preview="Willow">Preview <span aria-hidden="true">↗</span></button><button type="button" class="tm-btn tm-btn-main" data-use="Willow">Use Template <span aria-hidden="true">→</span></button></div></div></article>
                  <article class="tm-card" data-title="urban city modern vibrant business hotel" data-style="modern"><div class="tm-preview tm-urban"><div class="tm-browser"><span></span><span></span><span></span><small>urban.stays</small></div><div class="tm-design tm-urban-design"><div class="tm-design-top">URBN. <span>ROOMS &nbsp; EXPERIENCES &nbsp; ☰</span></div><div class="tm-design-center"><small>YOUR CITY. YOUR STYLE.</small><strong>The city<br>is yours.</strong><i>BOOK YOUR EXPERIENCE ↗</i></div><div class="tm-design-bottom">DESIGNED FOR THE WAY YOU MOVE</div></div><span class="tm-preview-id">03</span></div><div class="tm-card-content"><div class="tm-card-row"><div><span class="tm-category">MODERN · CITY HOTEL</span><h3>Urban</h3></div><span class="tm-emoji" aria-hidden="true">↗</span></div><p>High-contrast, energetic layouts for city hotels and modern brands.</p><div class="tm-actions"><button type="button" class="tm-btn tm-btn-quiet" data-preview="Urban">Preview <span aria-hidden="true">↗</span></button><button type="button" class="tm-btn tm-btn-main" data-use="Urban">Use Template <span aria-hidden="true">→</span></button></div></div></article>
                  <article class="tm-card" data-title="minimal clean bright timeless boutique" data-style="minimal"><div class="tm-preview tm-minimal"><div class="tm-browser"><span></span><span></span><span></span><small>theminimal.hotel</small></div><div class="tm-design tm-minimal-design"><div class="tm-design-top">THE MINIMAL <span>HOME &nbsp; STAY &nbsp; ABOUT</span></div><div class="tm-design-center"><small>SIMPLY A BETTER STAY</small><strong>Space to<br>slow down.</strong><i>VIEW OUR ROOMS ↗</i></div><div class="tm-design-bottom">SIMPLE THINGS. BEAUTIFULLY DONE.</div></div><span class="tm-preview-id">04</span></div><div class="tm-card-content"><div class="tm-card-row"><div><span class="tm-category">MINIMAL · CONTEMPORARY</span><h3>Minimal</h3></div><span class="tm-emoji" aria-hidden="true">◇</span></div><p>Timeless neutral palettes and spacious layouts that let every detail shine.</p><div class="tm-actions"><button type="button" class="tm-btn tm-btn-quiet" data-preview="Minimal">Preview <span aria-hidden="true">↗</span></button><button type="button" class="tm-btn tm-btn-main" data-use="Minimal">Use Template <span aria-hidden="true">→</span></button></div></div></article>
                </div><div class="tm-empty" id="tmEmpty" hidden>No templates found. Try another search.</div>
              </section>
              <div class="tm-footer-note"><span>✧</span><div><strong>Your idea deserves a beautiful start.</strong><p>Pick a template now, then manage your draft from My Projects.</p></div><a href="projects.php">Go to My Projects ↗</a></div>
            </main>
          </div>
        </div>
        <div class="mobile-overlay" id="mobileOverlay" hidden></div>
        <dialog id="tmCreateDialog" class="tm-dialog" aria-labelledby="tmCreateTitle">
          <form method="post" action="templates.php" class="tm-form">
            <input type="hidden" name="csrf_token" value="<?= template_escape(csrf_token()) ?>">
            <input type="hidden" name="template" id="tmSelectedTemplate">
            <input type="hidden" name="brand_name" id="tmChosenBrand" value="">
            <input type="hidden" name="headline" id="tmChosenHeadline" value="">
            <div class="tm-dialog-head"><div><span class="tm-eyebrow"><?= $project ? 'SELECT FOR EXISTING PROJECT' : 'NEW HOTEL WEBSITE' ?></span><h2 id="tmCreateTitle">Use <span id="tmSelectedName">Harbor</span> template</h2><p><?= $project ? 'Apply this design and save its text to MySQL.' : 'Save a draft to your My Projects list.' ?></p></div><button type="button" class="tm-close" data-close aria-label="Close dialog">×</button></div>
            <?php if ($project): ?>
              <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
              <p class="tm-selected-project">Project: <strong><?= template_escape($project['name']) ?></strong> · <?= template_escape($project['team_member'] ?: 'Unassigned') ?></p>
            <?php else: ?>
              <label>Project name <span aria-hidden="true">*</span><input required maxlength="150" name="name" id="tmProjectName" placeholder="e.g. Grand Lakeside Hotel"></label>
              <label>Hotel / property name<input maxlength="150" name="property_name" placeholder="e.g. Grand Lakeside"></label>
              <label>Team Member <span aria-hidden="true">*</span><select name="team_member" required><option value="" selected disabled>Select team member</option><?php foreach ($teamMembers as $member): ?><option value="<?= template_escape($member) ?>"><?= template_escape($member) ?></option><?php endforeach; ?></select></label>
            <?php endif; ?>
            <div class="tm-dialog-actions"><button type="button" class="tm-btn tm-btn-quiet" data-close>Cancel</button><button type="submit" class="tm-btn tm-btn-main"><?= $project ? 'Apply Template →' : 'Create Project →' ?></button></div>
          </form>
        </dialog>
        <dialog id="tmPreviewDialog" class="tm-dialog tm-preview-dialog" aria-labelledby="tmPreviewTitle"><div class="tm-dialog-head"><div><span class="tm-eyebrow">DESIGN PREVIEW</span><h2 id="tmPreviewTitle">Harbor</h2><p>Concept preview — not a fully built website.</p></div><button type="button" class="tm-close" data-close aria-label="Close preview">×</button></div><div id="tmPreviewMount"></div>
        <?php if ($project && $hotelPhotos): ?><section class="tm-upload-gallery" aria-label="Your saved gallery images"><div class="tm-upload-gallery-heading"><strong>Your Hotel Gallery <span>· <?= count($hotelPhotos) ?> Photos</span></strong><small>Ye photos final website ke Gallery section mein show hongi.</small></div><div class="tm-upload-gallery-track"><?php foreach ($hotelPhotos as $i => $photo): ?><img src="gallery-image.php?project_id=<?= (int)$projectId ?>&amp;image_id=<?= (int)$photo['id'] ?>&amp;v=20261028-5" alt="Hotel gallery photo <?= $i + 1 ?>" loading="lazy"><?php endforeach; ?></div></section><?php endif; ?>
        <fieldset class="tm-personalize"><legend>Customize preview text</legend>
          <label>Hotel / brand name<input id="tmEditBrand" type="text" maxlength="45" placeholder="Hotel name"></label>
          <label>Main headline<input id="tmEditHeadline" type="text" maxlength="75" placeholder="Main headline"></label>
          <div class="tm-personalize-tools"><span><?= $project ? 'Text database mein Save karo; Apply Template par bhi save hoga.' : 'Changes are saved in this browser until you create a project.' ?></span><button type="button" id="tmResetPreview" class="tm-btn tm-btn-quiet">Reset text</button></div>
        </fieldset>
        <?php if ($project): ?><div class="tm-save-preview-row"><button type="button" class="tm-btn tm-btn-quiet" id="tmSavePreview">Save Text to MySQL</button><span id="tmSaveStatus" role="status" aria-live="polite">Saved text will be linked to this project.</span></div><?php endif; ?>
        <div class="tm-dialog-actions"><button class="tm-btn tm-btn-quiet" data-close type="button">Close</button><button class="tm-btn tm-btn-main" id="tmPreviewUse" type="button">Use Template →</button></div></dialog>
        <script src="js/dashboard.js"></script><script src="js/templates.js?v=20261028-7"></script>
      </body></html>
