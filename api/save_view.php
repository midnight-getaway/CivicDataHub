<?php
/**
 * api/save_view.php — Saves a named dashboard view and filter payload for the logged-in user.
 *
 * Dependencies: Session support and db_connect.php.
 * Data sources: saved_views table.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Start session to identify the requesting authenticated user.
session_start();
header('Content-Type: application/json');

// Load PDO connection for saved view persistence.
require __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// We only read the session, so release the lock immediately
session_write_close();

// Enforce POST-only writes for this endpoint.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Parse JSON request body containing view metadata and filter state.
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$view_name = trim($input['view_name'] ?? '');
$dashboard_url = trim($input['dashboard_url'] ?? '');
$dashboard_name = trim($input['dashboard_name'] ?? '');
$filters = $input['filters'] ?? [];

if (empty($view_name) || empty($dashboard_url) || empty($dashboard_name) || empty($filters)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Insert one saved dashboard view tied to the current user account.
    $stmt = $pdo->prepare("
        INSERT INTO saved_views (user_id, view_name, dashboard_url, dashboard_name, filters)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $view_name,
        $dashboard_url,
        $dashboard_name,
        json_encode($filters)
    ]);
    
    echo json_encode(['success' => true, 'message' => 'View saved successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}

// PDO automatically closes connection when script execution ends.
