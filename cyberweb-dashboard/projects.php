<?php
require_once __DIR__ . '/auth/require_login.php';
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/hotel_data.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function goto_projects(string $result): void { header('Location: projects.php?' . http_build_query(['notice'=>$result]), true, 303); exit; }
$userId = (int) $_SESSION['user_id'];
$statuses = ['draft','in_progress','completed','published'];
$templates = ['Harbor','Willow','Urban','Minimal'];
$teamMembers = cyberweb_team_members();
try {
    cyberweb_team_schema($pdo);
} catch (PDOException $e) {
    error_log('Team folders schema: '.$e->getMessage());
    http_response_code(500);
    exit('Team Members database setup failed. Run database/team_members_upgrade.sql in phpMyAdmin, then refresh.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf()) { http_response_code(403); exit('Session expired. Refresh this page and try again.'); }
    $action = (string)($_POST['action'] ?? '');
    $id = filter_var($_POST['project_id'] ?? null, FILTER_VALIDATE_INT);
    try {
        if ($action === 'delete') {
            if (!$id) goto_projects('invalid');
            hotel_schema($pdo);
            $photos = hotel_gallery($pdo, (int)$id, $userId);
            $pdo->beginTransaction();
            try {
                $pdo->prepare('DELETE FROM gallery_images WHERE project_id=? AND user_id=?')->execute([$id,$userId]);
                $pdo->prepare('DELETE FROM hotel_faqs WHERE project_id=? AND user_id=?')->execute([$id,$userId]);
                $pdo->prepare('DELETE FROM hotel_details WHERE project_id=? AND user_id=?')->execute([$id,$userId]);
                $pdo->prepare('DELETE FROM template_settings WHERE project_id=? AND user_id=?')->execute([$id,$userId]);
                $stmt = $pdo->prepare('DELETE FROM projects WHERE id = ? AND user_id = ?');
                $stmt->execute([$id,$userId]);
                $deleted = $stmt->rowCount() > 0;
                $pdo->commit();
            } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
            if ($deleted) foreach ($photos as $photo) {
                $path = __DIR__ . '/storage/gallery/' . (int)$id . '/' . basename((string)$photo['file_name']);
                if (is_file($path)) @unlink($path);
            }
            goto_projects($deleted ? 'deleted' : 'notfound');
        }
        if ($action === 'create' || $action === 'update') {
            $name = trim((string)($_POST['name'] ?? ''));
            $property = trim((string)($_POST['property_name'] ?? ''));
            $teamMember = trim((string)($_POST['team_member'] ?? ''));
            $template = (string)($_POST['template'] ?? '');
            $status = (string)($_POST['status'] ?? 'draft');
            if ($name === '' || mb_strlen($name) > 150 || mb_strlen($property) > 150
                || !in_array($teamMember,$teamMembers,true) || !in_array($status,$statuses,true)) goto_projects('invalid');
            if ($action === 'create') {
                // Save project first, then collect hotel details before template selection.
                $stmt = $pdo->prepare('INSERT INTO projects (user_id,name,property_name,location,team_member,template,status) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$userId,$name,$property,'',$teamMember,'','draft']);
                $newProjectId = (int)$pdo->lastInsertId();
                header('Location: hotel-details.php?' . http_build_query(['project_id'=>$newProjectId]), true, 303);
                exit;
            }
            if (!$id) goto_projects('invalid');
            if (!in_array($template,array_merge([''],$templates),true)) goto_projects('invalid');
            $stmt = $pdo->prepare('UPDATE projects SET name=?,property_name=?,team_member=?,status=? WHERE id=? AND user_id=?');
            $stmt->execute([$name,$property,$teamMember,$status,$id,$userId]);
            goto_projects('updated');
        }
        goto_projects('invalid');
    } catch (PDOException $e) { error_log('Projects write error: '.$e->getMessage()); goto_projects('db_error'); }
}
$q = trim((string)($_GET['q'] ?? ''));
$q = mb_substr($q,0,100);
$filter = (string)($_GET['status'] ?? 'all');
if ($filter !== 'all' && !in_array($filter,$statuses,true)) $filter='all';
try {
    $countQ = $pdo->prepare('SELECT status, COUNT(*) AS n FROM projects WHERE user_id=? GROUP BY status');
    $countQ->execute([$userId]);
    $counts = ['draft'=>0,'in_progress'=>0,'completed'=>0,'published'=>0];
    foreach ($countQ->fetchAll() as $c) $counts[$c['status']] = (int)$c['n'];
    $where=' WHERE user_id = :uid'; $params=[':uid'=>$userId];
    if ($q!=='') { $where.=' AND (name LIKE :q1 OR property_name LIKE :q2 OR team_member LIKE :q3)'; $params[':q1']=$params[':q2']=$params[':q3']='%'.$q.'%'; }
    if ($filter!=='all') { $where.=' AND status = :status'; $params[':status']=$filter; }
    $stmt=$pdo->prepare('SELECT id,name,property_name,team_member,template,status,created_at,updated_at FROM projects'.$where.' ORDER BY updated_at DESC, id DESC LIMIT 150');
    $stmt->execute($params);
    $projects=$stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log('Projects read error: '.$e->getMessage()); http_response_code(500); exit('Projects table not ready. Import database/projects.sql in phpMyAdmin, then refresh.'); }
$teamFolders = array_fill_keys($teamMembers, []);
foreach ($projects as $project) {
    $folder = (string)$project['team_member'];
    if ($folder === '' || !in_array($folder,$teamMembers,true)) $folder = 'Unassigned';
    if (!isset($teamFolders[$folder])) $teamFolders[$folder] = [];
    $teamFolders[$folder][] = $project;
}
$notice = (string)($_GET['notice'] ?? '');
$messages=['created'=>'Project created successfully.','template_selected'=>'Template saved successfully. Your project is ready for the next step.','updated'=>'Project updated successfully.','deleted'=>'Project deleted.','notfound'=>'Project not found.','invalid'=>'Please check the form fields.','db_error'=>'Could not save the project. Check server logs.'];
?>
<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <meta name="theme-color" content="#0a1020">

    <meta name="description" content="CYBERWEB  dashboard">

    <title>My Projects | CYBERWEB</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link

        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"

        rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/projects.css?v=20261028-6">

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
    <a class="nav-link active" aria-current="page" href="projects.php">
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

                        class="breadcrumb">Workspace <span aria-hidden="true">/</span> <strong>My Projects</strong></span>

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
  <section class="intro projects-intro">
    <div><p class="overline"><span class="overline-line"></span> HOTEL WEBSITE WORKSPACE</p>
      <h1>My projects <span class="hello-spark">✳</span></h1>
      <p class="intro-sub">Organize every hotel website in one beautiful workspace.</p>
    </div>
    <button class="pj-primary" type="button" data-open-create><span aria-hidden="true">＋</span> New project</button>
  </section>
  <?php if(isset($messages[$notice])): ?><p class="pj-notice" role="status"><?= h($messages[$notice]) ?></p><?php endif; ?>
  <section class="pj-banner" aria-label="Project overview">
    <div class="pj-banner-copy"><span class="pj-kicker">✦ &nbsp;YOUR CREATIVE SPACE</span><h2>Every great website<br><em>starts with a project.</em></h2><p>Create, organize, and refine your hotel websites from here.</p><button type="button" class="pj-banner-action" data-open-create>Create new project <span aria-hidden="true">↗</span></button></div>
    <div class="pj-banner-art" aria-hidden="true"><div class="pj-orbit pj-o1"></div><div class="pj-orbit pj-o2"></div><div class="pj-preview"><div class="pj-dots"><b></b><b></b><b></b></div><div class="pj-preview-hero"><span>THE NORTHLINE</span><strong>Stay somewhere<br>extraordinary.</strong></div><div class="pj-preview-bars"><span></span><span></span><span></span></div></div><div class="pj-float">✦ &nbsp;Your next launch</div></div>
  </section>
  <section class="pj-stats" aria-label="Project statistics">
    <div class="pj-stat"><span class="pj-stat-icon violet">▦</span><div><span>Total projects</span><strong><?= array_sum($counts) ?></strong></div></div>
    <div class="pj-stat"><span class="pj-stat-icon amber">◷</span><div><span>In progress</span><strong><?= $counts['in_progress'] ?></strong></div></div>
    <div class="pj-stat"><span class="pj-stat-icon mint">✓</span><div><span>Completed</span><strong><?= $counts['completed'] ?></strong></div></div>
    <div class="pj-stat"><span class="pj-stat-icon blue">↗</span><div><span>Published</span><strong><?= $counts['published'] ?></strong></div></div>
  </section>
  <section class="pj-panel" aria-labelledby="pjTitle">
    <div class="pj-panel-head"><div><p class="overline small">TEAM MEMBER FOLDERS</p><h2 id="pjTitle">Team projects <span class="pj-count"><?= count($projects) ?></span></h2></div><p class="pj-panel-note">Each member's projects and selected templates · Saved in MySQL</p></div>
    <form method="get" action="projects.php" class="pj-filters" role="search">
      <label class="pj-search"><span aria-hidden="true">⌕</span><span class="sr-only">Search projects</span><input type="search" name="q" value="<?= h($q) ?>" maxlength="100" placeholder="Search by hotel, project, or team member…"></label>
      <label class="pj-select"><span class="sr-only">Filter by status</span><select name="status"><option value="all">All statuses</option><?php foreach($statuses as $s): ?><option value="<?= h($s) ?>" <?= $filter===$s?'selected':'' ?>><?= h(ucwords(str_replace('_',' ',$s))) ?></option><?php endforeach; ?></select></label>
      <button type="submit" class="pj-filter-btn">Apply filters</button><a class="pj-clear" href="projects.php">Clear</a>
    </form>
    <?php if (!$projects): ?>
      <div class="pj-empty"><div class="pj-empty-symbol">✦</div><h3>No projects found</h3><p><?= $q!=='' || $filter!=='all' ? 'Try clearing your search and filters.' : 'Your next great hotel website starts here.' ?></p><button type="button" class="pj-primary" data-open-create>+ Create project</button></div>
    <?php endif; ?>
    <div class="pj-folder-list" aria-label="Team member project folders">
      <?php $openFolders = 0; foreach ($teamFolders as $memberName => $folderProjects):
          $folderCount = count($folderProjects);
          $selectedCount = count(array_filter($folderProjects, fn($p) => $p['template'] !== ''));
          $expanded = $folderCount > 0 && $openFolders < 2;
          if ($expanded) $openFolders++;
      ?>
      <details class="pj-team-folder" <?= $expanded ? 'open' : '' ?>>
        <summary class="pj-folder-summary"><span class="pj-folder-icon" aria-hidden="true">▣</span><span class="pj-folder-name"><?= h($memberName) ?><small><?= $folderCount ?> project<?= $folderCount === 1 ? '' : 's' ?> · <?= $selectedCount ?> selected template<?= $selectedCount === 1 ? '' : 's' ?></small></span><span class="pj-folder-count"><?= $folderCount ?></span><span class="pj-folder-chevron" aria-hidden="true">⌄</span></summary>
        <?php if ($folderProjects): ?><div class="pj-grid">
      <?php foreach($folderProjects as $project): ?>
        <article class="pj-card">
          <div class="pj-cover pj-cover-<?= h(strtolower($project['template']) ?: 'unselected') ?>"><div class="pj-cover-orb"></div><span class="pj-cover-tag"><?= $project['template'] !== '' ? h($project['template']) . ' template' : 'Template not selected' ?></span><div class="pj-cover-monogram"><?= h(mb_strtoupper(mb_substr($project['name'],0,1))) ?></div></div>
          <div class="pj-card-body"><div class="pj-card-meta"><span class="pj-status pj-status-<?= h($project['status']) ?>"><?= h(ucwords(str_replace('_',' ',$project['status']))) ?></span><time datetime="<?= h($project['updated_at']) ?>">Updated <?= h(date('M j, Y',strtotime($project['updated_at']))) ?></time></div>
            <h3><?= h($project['name']) ?></h3><p class="pj-property"><?= h($project['property_name'] ?: 'Hotel website') ?></p><p class="pj-team-member">♙ Team Member: <strong><?= h($project['team_member'] ?: 'Unassigned') ?></strong></p>
            <div class="pj-card-actions"><a class="pj-edit" href="hotel-details.php?project_id=<?= (int)$project['id'] ?>">Hotel details</a><a class="pj-edit" href="ai-designs.php?project_id=<?= (int)$project['id'] ?>">AI Designs</a><a class="pj-edit" href="templates.php?project_id=<?= (int)$project['id'] ?>"><?= $project['template'] === '' ? 'Select design' : 'Change design' ?></a><?php if ($project['template'] !== ''): ?><a class="pj-edit" href="preview-site.php?project_id=<?= (int)$project['id'] ?>">View website ↗</a><?php endif; ?><button class="pj-edit" type="button" data-edit data-id="<?= (int)$project['id'] ?>" data-name="<?= h($project['name']) ?>" data-property="<?= h($project['property_name']) ?>" data-team="<?= h($project['team_member']) ?>" data-status="<?= h($project['status']) ?>">Edit project</button>
            <form method="post" action="projects.php" class="pj-delete-form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>"><button type="submit" class="pj-delete" aria-label="Delete <?= h($project['name']) ?>" data-delete>Delete</button></form></div>
          </div>
        </article>
      <?php endforeach; ?>
        </div><?php else: ?><p class="pj-folder-empty">Is member ke folder mein abhi koi project nahi hai. New Project banate waqt ye Team Member select karo.</p><?php endif; ?>
      </details>
      <?php endforeach; ?>
    </div>
  </section>
  <p class="pj-footer-note">Flow: Create Project → Hotel Details & Photo ZIP → Select Template → Final Preview. You can edit hotel details and change the design later.</p>
</main>
<div class="pj-modal" id="projectModal" hidden><div class="pj-backdrop" data-close-modal></div><section class="pj-dialog" role="dialog" aria-modal="true" aria-labelledby="pjDialogTitle"><div class="pj-dialog-top"><div><p class="overline small">PROJECT DETAILS</p><h2 id="pjDialogTitle">Create a project</h2></div><button type="button" class="pj-x" data-close-modal aria-label="Close dialog">✕</button></div>
<form method="post" action="projects.php" id="projectForm"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" id="pjAction" value="create"><input type="hidden" name="project_id" id="pjId" value="">
<label class="pj-field">Project Name <input name="name" id="pjName" required maxlength="150" placeholder="e.g. Ocean View Inn Website"></label>
<label class="pj-field">Hotel / Property Name <input name="property_name" id="pjProperty" maxlength="150" placeholder="e.g. Ocean View Inn"></label>
<label class="pj-field">Team Member <span aria-hidden="true">*</span><select name="team_member" id="pjTeamMember" required><option value="" selected disabled>Select team member</option><?php foreach ($teamMembers as $member): ?><option value="<?= h($member) ?>"><?= h($member) ?></option><?php endforeach; ?></select></label>
<div class="pj-field-row" id="pjStatusField"><label class="pj-field">Status <select name="status" id="pjStatus"><?php foreach($statuses as $s): ?><option value="<?= h($s) ?>"><?= h(ucwords(str_replace('_',' ',$s))) ?></option><?php endforeach; ?></select></label></div>
<p id="pjNextStepNote">Next: fill hotel name, phone, address, amenities, things to do and upload a photo ZIP. Then select your template.</p>
<div class="pj-dialog-actions"><button type="button" class="pj-cancel" data-close-modal>Cancel</button><button class="pj-primary" type="submit" id="pjSubmit">Create project</button></div></form></section></div>
</div></div><div class="mobile-overlay" id="mobileOverlay" hidden></div><script src="js/dashboard.js"></script><script src="js/projects.js?v=20261028-6"></script></body></html>
