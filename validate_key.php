<?php
header('Content-Type: text/plain');

// --- Bypass token (from environment variable) ---
$expectedBypass = getenv('BYPASS_TOKEN');
if (isset($_GET['bypass']) && $_GET['bypass'] === $expectedBypass) {
    // token matches – proceed
} else {
    die('ERROR');
}

$key = isset($_GET['key']) ? trim($_GET['key']) : '';
if (empty($key)) die('ERROR');

// Database credentials from environment variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');

if (!$host || !$user || !$pass) die('ERROR');

$conn = new mysqli($host, $user, $pass, $db, (int)$port);
if ($conn->connect_error) die('ERROR');

$hwid = isset($_GET['hwid']) ? $conn->real_escape_string($_GET['hwid']) : '';
$pc   = isset($_GET['pc'])   ? $conn->real_escape_string($_GET['pc'])   : '';

// Check ban
$banCheck = $conn->query("SELECT COUNT(*) FROM banned_devices WHERE hwid='$hwid' OR pc_name='$pc'");
if ($banCheck && $banCheck->fetch_row()[0] > 0) die('BANNED');

// Validate key
$result = $conn->query("SELECT used, hwid FROM activation_keys WHERE key_code='$key' AND expires_at > NOW() LIMIT 1");
if (!$result || $result->num_rows == 0) die('INVALID');

$row = $result->fetch_assoc();
$used = $row['used'];
$storedHwid = $row['hwid'];

if ($used && !empty($storedHwid) && $storedHwid !== $hwid) die('LOCKED');

// Activate if not already used
if (!$used || empty($storedHwid)) {
    $conn->query("UPDATE activation_keys SET used=1, used_by='$pc', hwid='$hwid', pc_name='$pc', used_at=NOW() WHERE key_code='$key'");
}
die('OK');
?>