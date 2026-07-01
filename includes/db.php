<?php
require_once __DIR__ . '/config.php';

// Single shared mysqli connection (prepared statements throughout).
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die(
        'Database connection failed: ' . htmlspecialchars($e->getMessage()) .
        '<br>Make sure MySQL is running and you imported <code>db/schema.sql</code>.'
    );
}

/**
 * Run a prepared SELECT/INSERT/UPDATE/DELETE.
 * $types example: "si" (string, int). Returns mysqli_stmt.
 */
function q(string $sql, string $types = '', array $params = []): mysqli_stmt {
    global $db;
    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/** Fetch all rows as associative arrays. */
function fetch_all(string $sql, string $types = '', array $params = []): array {
    $res = q($sql, $types, $params)->get_result();
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/** Fetch a single row (or null). */
function fetch_one(string $sql, string $types = '', array $params = []): ?array {
    $res = q($sql, $types, $params)->get_result();
    return $res ? ($res->fetch_assoc() ?: null) : null;
}
