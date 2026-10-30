<?php
// Install alongside your existing auth/ and api/ folders.
$guard = __DIR__ . '/auth/require_login.php';
if (!is_file($guard)) {
    http_response_code(503);
    exit('Authentication setup missing. Copy this page into your existing Cyberweb project with auth/require_login.php.');
}
require_once $guard;
require_once __DIR__ . '/api/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Database connection unavailable.');
}

function esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pageLink(int $n, string $q): string
{
    return 'users.php?' . http_build_query(['q' => $q, 'page' => $n]);
}

try {
    $q = trim((string) ($_GET['q'] ?? ''));
    $q = mb_substr($q, 0, 100);
    $params = [];
    $where = '';

    if ($q !== '') {
        $where = ' WHERE first_name LIKE :search1 OR last_name LIKE :search2 OR email LIKE :search3';
        $like = '%' . $q . '%';
        $params = [':search1' => $like, ':search2' => $like, ':search3' => $like];
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users' . $where);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $page = max(1, min(100000, (int) ($_GET['page'] ?? 1)));
    $limit = 12;
    $pages = max(1, (int) ceil($total / $limit));
    $page = min($page, $pages);
    $offset = ($page - 1) * $limit;

    $stmt = $pdo->prepare(
        'SELECT id, first_name, last_name, email, created_at FROM users' . $where .
        ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
    );
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Users fetch: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not fetch users. Verify the users table and connection, then check server logs.');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registered Users | HotelForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/users.css">
    <link rel="stylesheet" href="css/navbar.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true">✦</span>
            <span>HotelForge<small>STUDIO CLOUD</small></span>
        </a>
        <div class="nav-label">WORKSPACE</div>
        <nav class="side-nav" aria-label="Workspace navigation">
            <a class="nav-link" href="index.php"><span class="nav-symbol" aria-hidden="true">▦</span>Overview</a>
            <a class="nav-link" href="projects.php"><span class="nav-symbol" aria-hidden="true">▤</span>My Projects</a>
            <a class="nav-link" href="templates.php"><span class="nav-symbol" aria-hidden="true">◈</span>Templates</a>
            <a class="nav-link" href="media-manager.html"><span class="nav-symbol" aria-hidden="true">▧</span>Media Library</a>
            <a class="nav-link active" aria-current="page" href="users.php"><span class="nav-symbol" aria-hidden="true">♙</span>Users</a>
        </nav>
        <div class="nav-label second-label">MANAGEMENT</div>
        <nav class="side-nav" aria-label="Management navigation">
            <a class="nav-link" href="revisions.html"><span class="nav-symbol" aria-hidden="true">◷</span>Revision History</a>
            <a class="nav-link" href="exports.html"><span class="nav-symbol" aria-hidden="true">⇩</span>Exports</a>
            <a class="nav-link" href="settings.html"><span class="nav-symbol" aria-hidden="true">⚙</span>Settings</a>
        </nav>
        <div class="side-bottom"><div class="sidebar-foot">© 2026 HotelForge Studio</div></div>
    </aside>

    <div class="workspace">
        <header class="topbar">
            <div class="top-left">
                <button class="mobile-menu icon-btn" id="menuToggle" type="button"
                        aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">☰</button>
                <span class="breadcrumb">Workspace <span aria-hidden="true">/</span> <strong>Users</strong></span>
            </div>
            <nav class="top-navbar" aria-label="Top navigation">
                <a href="index.php" class="top-nav-link">Overview</a>
                <a href="projects.php" class="top-nav-link">My Projects</a>
                <a href="templates.php" class="top-nav-link">Templates</a>
                <a href="media-manager.html" class="top-nav-link">Media Library</a>
                <a href="users.php" class="top-nav-link active" aria-current="page">Users</a>
                <a href="settings.html" class="top-nav-link">Settings</a>
            </nav>
            <div class="top-right">
                <span class="top-live"><span class="live-dot" aria-hidden="true"></span> Database connected</span>
                <a class="avatar-button" href="settings.html" aria-label="Account settings">HF</a>
            </div>
        </header>

        <main class="main-content">
            <div class="intro users-intro">
                <div>
                    <p class="overline"><span class="overline-line"></span> USER MANAGEMENT</p>
                    <h1>Registered users <span class="hello-spark" aria-hidden="true">✳</span></h1>
                    <p class="intro-sub">Live account records fetched securely from the Cyberweb MySQL database.</p>
                </div>
                <span class="date-pill">Live MySQL records</span>
            </div>
            <section class="user-summary" aria-label="User summary">
                <div class="user-summary-icon" aria-hidden="true">♙</div>
                <div>
                    <span class="user-summary-label"><?= $q === '' ? 'Total registered users' : 'Matching users' ?></span>
                    <div class="user-summary-value"><?= esc($total) ?></div>
                    <span class="user-summary-note">Sensitive password hashes are never displayed</span>
                </div>
                <span class="summary-decor" aria-hidden="true">✦</span>
            </section>

            <section class="user-table-card" aria-labelledby="usersTitle">
                <div class="users-toolbar">
                    <div>
                        <p class="overline small">ACCOUNT DIRECTORY</p>
                        <h2 id="usersTitle">All users</h2>
                    </div>
                    <form method="get" action="users.php" class="users-search">
                        <label class="sr-only" for="userSearch">Search by name or email</label>
                        <input id="userSearch" name="q" value="<?= esc($q) ?>" type="search"
                               placeholder="Search name or email..." maxlength="100">
                        <button type="submit">Search</button>
                    </form>
                </div>
                <div class="users-scroll">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th scope="col">USER</th>
                                <th scope="col">EMAIL ADDRESS</th>
                                <th scope="col">JOINED</th>
                                <th scope="col">USER ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$users): ?>
                                <tr><td colspan="4" class="empty-users">No users match your search.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="user-identity">
                                                <span class="user-avatar"><?= esc(mb_strtoupper(mb_substr((string) ($user['first_name'] ?? ''), 0, 1))) ?></span>
                                                <span>
                                                    <strong><?= esc(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></strong>
                                                    <small>Registered member</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td><?= esc($user['email']) ?></td>
                                        <td><?= esc($user['created_at']) ?></td>
                                        <td><span class="user-id">#<?= esc($user['id']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    <span>Page <?= esc($page) ?> of <?= esc($pages) ?> · <?= esc($total) ?> result(s)</span>
                    <div>
                        <?php if ($page > 1): ?>
                            <a href="<?= esc(pageLink($page - 1, $q)) ?>">← Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $pages): ?>
                            <a href="<?= esc(pageLink($page + 1, $q)) ?>">Next →</a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <p class="users-safety">Access note: this page requires login. In a multi-user application, restrict the complete user directory to administrator roles only.</p>
        </main>
    </div>
</div>
<div class="mobile-overlay" id="mobileOverlay" hidden></div>
<script src="js/dashboard.js"></script>
</body>
</html>
