<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? null;
$payment_method = $input['payment_method'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$order_id || !$payment_method) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

try {
    // Verify order belongs to user and is pending
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak valid']);
        exit;
    }
    
    // Update order status and payment method
    $stmt = $pdo->prepare("UPDATE transactions SET status = 'paid', payment_method = ?, paid_at = NOW() WHERE id = ?");
    $stmt->execute([$payment_method, $order_id]);
    
    echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>