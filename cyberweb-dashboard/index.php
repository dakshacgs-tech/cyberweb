<?php
// Initialize the existing authentication session.
require_once __DIR__ . '/auth/bootstrap.php';

// No authenticated session? Always show the login page.
if (empty($_SESSION['user_id'])) {
    header('Location: /cyberweb-dashboard/login.php', true, 302);
    exit;
}

// Prevent the browser from showing a cached dashboard after logout.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <meta name="theme-color" content="#0a1020">

    <meta name="description" content="CYBERWEB  dashboard">

    <title>Workspace Overview | CYBERWEB</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link

        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"

        rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

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
    <a class="nav-link active"
       href="index.php"
       aria-current="page">
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

                        class="breadcrumb">Workspace <span aria-hidden="true">/</span> <strong>Overview</strong></span>

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

            <main class="main-content" id="main">

                <div class="intro">

                    <div>

                        <p class="overline"><span class="overline-line"></span> YOUR CREATIVE CONTROL CENTER</p>

                        <h1>Good to see you <span class="hello-spark">✳</span></h1>

                        <p class="intro-sub">Everything you need to design, organize, and launch beautiful hotel

                            websites.</p>

                    </div><span class="date-pill" id="todayDate">Your workspace</span>

                </div>

                <section class="hero-card" aria-labelledby="heroTitle">

                    <div class="hero-orb one" aria-hidden="true"></div>

                    <div class="hero-orb two" aria-hidden="true"></div>

                    <div class="hero-left"><span class="hero-kicker"><span class="kicker-dot"></span> YOUR NEXT GREAT

                            WEBSITE STARTS HERE</span>

                        <h2 id="heroTitle">Create something<br><em>extraordinary.</em></h2>

                        <p>From property details to polished pages—turn your next hotel idea into a beautiful digital

                            experience.</p><a href="projects.php#new-project" class="hero-cta">Create Hotel Website <span

                                aria-hidden="true">↗</span></a>

                        <div class="hero-foot"><span class="tiny-face">✦</span> Four crafted design directions to

                            explore</div>

                    </div>

                    <div class="hero-art" aria-hidden="true">

                        <div class="art-back"></div>

                        <div class="art-window">

                            <div class="art-chrome"><b></b><b></b><b></b><span>hotel-preview.site</span></div>

                            <div class="art-photo"><span>THE NORTHLINE</span>

                                <h3>Some stays<br>stay with you.</h3><span class="art-pill">Explore our rooms ↗</span>

                            </div>

                            <div class="art-mini"><i></i><i></i><i></i></div>

                        </div>

                        <div class="float-chip chip-a">✦ &nbsp; Beautifully built</div>

                        <div class="float-chip chip-b">↗ &nbsp; Launch ready</div>

                    </div>

                </section>

                <section class="reports-section" aria-labelledby="reportsTitle">

                    <div class="section-heading">

                        <div>

                            <p class="overline small">AT A GLANCE</p>

                            <h2 id="reportsTitle">Workspace overview</h2>

                        </div><span class="demo-chip">Sample statistics</span>

                    </div>

                    <div class="stats-grid">

                        <article class="stat-card">

                            <div class="stat-top"><span class="stat-icon lilac">▤</span><span

                                    class="stat-arrow">↗</span></div>

                            <p>Hotel projects</p><strong>12</strong><span class="stat-bottom">Across your

                                workspace</span>

                        </article>

                        <article class="stat-card">

                            <div class="stat-top"><span class="stat-icon blue">◈</span><span class="stat-arrow">↗</span>

                            </div>

                            <p>Design templates</p><strong>04</strong><span class="stat-bottom">Ready to

                                customize</span>

                        </article>

                        <article class="stat-card">

                            <div class="stat-top"><span class="stat-icon peach">◷</span><span

                                    class="stat-arrow">↗</span></div>

                            <p>Recent revisions</p><strong>08</strong><span class="stat-bottom">Saved design

                                versions</span>

                        </article>

                        <article class="stat-card">

                            <div class="stat-top"><span class="stat-icon mint">⇩</span><span class="stat-arrow">↗</span>

                            </div>

                            <p>ZIP exports</p><strong>06</strong><span class="stat-bottom">Generated downloads</span>

                        </article>

                    </div>

                </section>

                <section class="projects-section" aria-labelledby="projectsTitle">

                    <div class="section-heading project-heading">

                        <div>

                            <p class="overline small">PICK UP WHERE YOU LEFT OFF</p>

                            <h2 id="projectsTitle">Recent projects</h2>

                        </div><a href="projects.php" class="view-all">View all projects <span

                                aria-hidden="true">↗</span></a>

                    </div>

                    <div class="project-panel">

                        <div class="project-toolbar">

                            <div class="project-count"><span class="dot-indigo"></span> Your latest work <span

                                    class="count-pill">2 projects</span></div><label class="search-box"><span

                                    aria-hidden="true">⌕</span><span class="sr-only">Search recent projects</span><input

                                    id="projectSearch" type="search" placeholder="Search projects..."></label>

                        </div>

                        <div id="projectRows">

                            <article class="project-row" data-search="hotel project 01 harbor design">

                                <div class="project-art art-harbor">H<span>01</span></div>

                                <div class="project-details">

                                    <h3>Hotel Project 01 <span class="status-badge">In progress</span></h3>

                                    <p>Harbor Design <span class="middot">·</span> Hotel website</p>

                                </div><a class="open-project" href="projects.php">Open editor

                                    <span aria-hidden="true">↗</span></a>

                            </article>

                            <article class="project-row" data-search="hotel project 02 willow design">

                                <div class="project-art art-willow">W<span>02</span></div>

                                <div class="project-details">

                                    <h3>Hotel Project 02 <span class="status-badge">In progress</span></h3>

                                    <p>Willow Design <span class="middot">·</span> Hotel website</p>

                                </div><a class="open-project" href="projects.php">Open editor

                                    <span aria-hidden="true">↗</span></a>

                            </article>

                        </div>

                        <p class="no-results" id="noResults" hidden>No matching projects in this sample list.</p>

                    </div>

                    <p class="demo-note">Dashboard totals and project names are sample content from the supplied design.

                        Connect your projects backend for live values.</p>

                </section>

            </main>

        </div>

    </div>

    <div class="mobile-overlay" id="mobileOverlay" hidden></div>

    <script src="js/dashboard.js"></script>

</body>

</html>