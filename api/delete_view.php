<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// We only read the session, so release the lock immediately
session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$view_id = (int) ($input['view_id'] ?? 0);

if ($view_id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid view ID']);
    exit;
}

try {
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
