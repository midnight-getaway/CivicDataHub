<?php
/**
 * api/delete_view.php — Deletes a saved dashboard view owned by the logged-in user.
 *
 * Dependencies: Session support and db_connect.php.
 * Data sources: saved_views table.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Start session to identify the requesting authenticated user.
session_start();
header('Content-Type: application/json');

// Load PDO connection for saved view deletion.
require __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// We only read the session, so release the lock immediately
session_write_close();

// Enforce POST-only deletes for this endpoint.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Parse JSON request body and read the target view identifier.
$input = json_decode(file_get_contents('php://input'), true);

$view_id = (int) ($input['view_id'] ?? 0);

if ($view_id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid view ID']);
    exit;
}

try {
    // Delete only the saved view that belongs to the current user.
    $stmt = $pdo->prepare("DELETE FROM saved_views WHERE id = ? AND user_id = ?");
    $stmt->execute([$view_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'View deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'View not found or unauthorized']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}

// PDO automatically closes connection when script execution ends.
