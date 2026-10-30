<?php
// Database-backed, atomic 10-concurrent-session limit.
const MAX_ACTIVE_LOGINS = 10;
const SESSION_IDLE_MINUTES = 30;

function claim_login_slot(PDO $pdo, int $userId): ?string {
    $token = bin2hex(random_bytes(32));
    $pdo->beginTransaction();
    try {
        $lock = $pdo->query('SELECT id FROM login_capacity_lock WHERE id = 1 FOR UPDATE')->fetchColumn();
        if (!$lock) { throw new RuntimeException('Run database/active_sessions.sql first.'); }
        $pdo->exec('DELETE FROM active_login_sessions WHERE last_seen < UTC_TIMESTAMP() - INTERVAL 30 MINUTE');
        $count = (int)$pdo->query('SELECT COUNT(*) FROM active_login_sessions')->fetchColumn();
        if ($count >= MAX_ACTIVE_LOGINS) { $pdo->rollBack(); return null; }
        $stmt = $pdo->prepare('INSERT INTO active_login_sessions (session_token, user_id, last_seen) VALUES (?, ?, UTC_TIMESTAMP())');
        $stmt->execute([$token, $userId]);
        $pdo->commit();
        return $token;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}

function refresh_login_slot(PDO $pdo, int $userId, string $token): bool {
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) { return false; }
    $stmt = $pdo->prepare('UPDATE active_login_sessions SET last_seen = UTC_TIMESTAMP() WHERE session_token = ? AND user_id = ? AND last_seen >= UTC_TIMESTAMP() - INTERVAL 30 MINUTE');
    $stmt->execute([$token, $userId]);
    // MySQL affected-rows can be zero when two page loads occur in same second.
    if ($stmt->rowCount() > 0) { return true; }
    $check = $pdo->prepare('SELECT 1 FROM active_login_sessions WHERE session_token = ? AND user_id = ? AND last_seen >= UTC_TIMESTAMP() - INTERVAL 30 MINUTE');
    $check->execute([$token, $userId]);
    return (bool)$check->fetchColumn();
}

function release_login_slot(PDO $pdo, string $token): void {
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) { return; }
    $stmt = $pdo->prepare('DELETE FROM active_login_sessions WHERE session_token = ?');
    $stmt->execute([$token]);
}
