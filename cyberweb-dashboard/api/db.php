
<?php

$host = "127.0.0.1";
$dbname = "cyberweb";
$dbUsername = "root";
$dbPassword = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbUsername,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Enable strict mode for this database connection
    $pdo->exec(
        "SET SESSION sql_mode = CONCAT_WS(',',
            NULLIF(@@SESSION.sql_mode, ''),
            'STRICT_TRANS_TABLES'
        )"
    );

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Database connection failed.");
}
