<?php
// Use the identical authentication guard as the existing dashboard.
require_once __DIR__ . '/auth/require_login.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0a1020">
  <title>Edit Hotel | CYBERWEB</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/hotel-editor.css">
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
    <a class="nav-link" href="templates.php">
        <span class="nav-symbol" aria-hidden="true">◈</span>
        Templates
    </a>

    <!-- Hotel Editor -->
    <a class="nav-link active" href="edit-hotel.php" aria-current="page">
        <span class="nav-symbol" aria-hidden="true">✎</span>
        Edit Hotel
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

        </aside><div class="workspace"><header class="topbar">

                <div class="top-left"><button class="mobile-menu icon-btn" id="menuToggle" type="button"

                        aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">☰</button><span

                        class="breadcrumb">Workspace <span aria-hidden="true">/</span> <strong>Edit Hotel</strong></span>

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

            </header><main class="main-content" id="main"><div class="intro"><div><p class="overline"><span class="overline-line"></span> HOTEL CONTENT MANAGER</p><h1>Edit hotel <span class="hello-spark">✳</span></h1><p class="intro-sub">Update hotel details, amenities, room types, attractions, photos and frequently asked questions.</p></div><span class="date-pill">Hotel website editor</span></div>
<div class="hotel-editor-tools"><div class="draft-indicator"><span class="status-dot"></span><span id="saveStatus">Unsaved changes</span></div><div class="page-actions"><button class="btn secondary" type="button" id="resetButton">Reset draft</button><button class="btn secondary" type="button" id="exportButton">↓ Export JSON</button><button class="btn primary" type="submit" form="hotelForm">Save draft →</button></div></div><div class="editor-grid">
        <nav class="section-index" aria-label="Editor sections">
          <h2>PAGE SECTIONS</h2>
          <a href="#details" class="current"><span>01</span> Hotel Details</a>
          <a href="#contact"><span>02</span> Contact</a>
          <a href="#amenities"><span>03</span> Amenities</a>
          <a href="#rooms"><span>04</span> Room Types</a>
          <a href="#attractions"><span>05</span> Attractions</a>
          <a href="#gallery"><span>06</span> Photo Gallery</a>
          <a href="#faqs"><span>07</span> FAQs</a>
          <p class="index-tip">Your changes are saved as a browser draft. Export JSON for a portable copy.</p>
        </nav>
        <form id="hotelForm" class="hotel-form">
          <section id="details" class="form-card" aria-labelledby="detailsTitle">
            <div class="card-heading"><span class="card-icon">🏨</span><div><p class="eyebrow">01 / IDENTITY</p><h2 id="detailsTitle">Hotel information</h2><p>Basic information used across the website.</p></div></div>
            <div class="field-grid"><label class="field field-full">Hotel name <span class="required">*</span><input required name="hotelName" id="hotelName" placeholder="e.g. The Grand Horizon Hotel" maxlength="150"></label><label class="field field-full">Hotel address <span class="required">*</span><textarea required name="address" id="address" rows="3" placeholder="Street, city, state, ZIP code"></textarea></label></div>
          </section>
          <section id="contact" class="form-card" aria-labelledby="contactTitle">
            <div class="card-heading"><span class="card-icon">☎</span><div><p class="eyebrow">02 / COMMUNICATION</p><h2 id="contactTitle">Contact details</h2><p>How guests can reach the hotel.</p></div></div>
            <div class="field-grid"><label class="field">Contact number <span class="required">*</span><input required type="tel" name="phone" id="phone" placeholder="e.g. +1 555 012 3456"></label><label class="field">Email address <input type="email" name="email" id="email" placeholder="reservations@example.com"></label></div>
          </section>
          <section id="amenities" class="form-card" aria-labelledby="amenitiesTitle">
            <div class="card-heading"><span class="card-icon">✦</span><div><p class="eyebrow">03 / FACILITIES</p><h2 id="amenitiesTitle">Hotel amenities</h2><p>Select facilities or add your own.</p></div></div>
            <div class="amenity-options" id="amenityOptions"></div><div class="inline-add"><label class="sr-only" for="customAmenity">New amenity</label><input id="customAmenity" placeholder="Add custom amenity, e.g. Shuttle Service" maxlength="70"><button type="button" class="btn secondary" id="addAmenity">+ Add</button></div>
          </section>
          <section id="rooms" class="form-card" aria-labelledby="roomsTitle">
            <div class="card-heading"><span class="card-icon">▤</span><div><p class="eyebrow">04 / ACCOMMODATION</p><h2 id="roomsTitle">Room types</h2><p>Add two-bedroom, three-bedroom, and other room categories.</p></div></div>
            <div id="roomsList" class="repeat-list"></div><button type="button" class="add-row" id="addRoom">＋ Add room type</button>
          </section>
          <section id="attractions" class="form-card" aria-labelledby="attractionsTitle">
            <div class="card-heading"><span class="card-icon">⌖</span><div><p class="eyebrow">05 / EXPLORE</p><h2 id="attractionsTitle">Nearby attractions</h2><p>Tell guests about places to explore nearby.</p></div></div>
            <div id="attractionsList" class="repeat-list"></div><button type="button" class="add-row" id="addAttraction">＋ Add attraction</button>
          </section>
          <section id="gallery" class="form-card" aria-labelledby="galleryTitle">
            <div class="card-heading"><span class="card-icon">▧</span><div><p class="eyebrow">06 / VISUALS</p><h2 id="galleryTitle">Photo gallery</h2><p>Add a gallery image URL or preview local uploads.</p></div></div>
            <label class="upload-box" for="galleryUpload"><span class="upload-icon">☁</span><strong>Choose hotel photos</strong><span>PNG, JPG or WebP. Local previews are not persisted to the server.</span><input id="galleryUpload" type="file" accept="image/png,image/jpeg,image/webp" multiple></label>
            <div class="inline-add"><label class="sr-only" for="galleryUrl">Image URL</label><input type="url" id="galleryUrl" placeholder="https://example.com/hotel-room.jpg"><button class="btn secondary" type="button" id="addGalleryUrl">+ Add URL</button></div>
            <div id="galleryList" class="gallery-list" aria-live="polite"></div>
          </section>
          <section id="faqs" class="form-card" aria-labelledby="faqsTitle">
            <div class="card-heading"><span class="card-icon">?</span><div><p class="eyebrow">07 / SUPPORT</p><h2 id="faqsTitle">Frequently asked questions</h2><p>Edit three FAQs for your hotel website.</p></div></div>
            <div id="faqsList" class="repeat-list"></div>
          </section>
          <div class="footer-actions"><p id="footerFeedback" role="status" aria-live="polite">Draft has not been saved yet.</p><button type="submit" class="btn primary">Save hotel draft →</button></div>
        </form>
      </div><p class="editor-footnote">Drafts are saved in this browser only, not in MySQL. Local photo previews are not retained after refresh.</p></main></div></div>
<div class="mobile-overlay" id="mobileOverlay" hidden></div>
<script src="js/dashboard.js"></script>
<script src="js/hotel-editor.js"></script>
</body></html>
