<?php
// check.php
// Usage: https://www.xyz.com/check.php?mid=MIDVALUE&oid=OIDVALUE

// --- Basic config ---
$allowed_file = __DIR__ . '/allowed_mids.txt'; // file containing allowed mids (one per line)
$external_api_base = 'https://king.thesmmpanel.shop/api/aadhar-info/check'; // provided API base

// --- Read input ---
$mid = isset($_GET['mid']) ? trim($_GET['mid']) : '';
$oid = isset($_GET['oid']) ? trim($_GET['oid']) : '';

// Simple validation
if ($mid === '') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'mid parameter missing']);
    exit;
}

// Load allowed mids file
if (!file_exists($allowed_file)) {
    // If file missing, behave as none allowed (you can change this behavior)
    $allowed_mids = [];
} else {
    $allowed_mids = file($allowed_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    // normalize
    $allowed_mids = array_map('trim', $allowed_mids);
}

// Check if mid exists in allowed list (case-sensitive match)
$found = in_array($mid, $allowed_mids, true);

if (!$found) {
    // Not allowed -> custom message
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'not_allowed',
        'message' => 'Apuni api buy karne ke liye @tushar ko dm kare'
    ]);
    exit;
}

// If allowed -> call external API and return its response
// According to your note, key == mid
$api_url = $external_api_base . '?' . http_build_query([
    'mid' => $mid,
    'key' => $mid,
    'oid' => $oid
]);

// Use cURL to call external API
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

// Optional: set headers if needed by API (uncomment/edit)
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     'Accept: application/json',
//     // 'Authorization: Bearer ...'
// ]);

$response = curl_exec($ch);
$curl_err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $curl_err) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to contact upstream API',
        'detail' => $curl_err
    ]);
    exit;
}

// Try to detect JSON response and pass correct header
$is_json = false;
$trim = ltrim($response);
if (strlen($trim) > 0 && ($trim[0] === '{' || $trim[0] === '[')) {
    $is_json = true;
}

if ($is_json) {
    header('Content-Type: application/json; charset=utf-8');
} else {
    header('Content-Type: text/plain; charset=utf-8');
}

// Pass through HTTP status code from upstream when reasonable
if ($http_code >= 200 && $http_code < 600) {
    http_response_code($http_code);
}

echo $response;
exit;
