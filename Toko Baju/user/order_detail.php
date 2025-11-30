<?php
require_once '../includes/auth.php';
$page_title = "Detail Pesanan";
include '../includes/header.php';

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_order'])) {
        try {
            // Check if order belongs to user and can be cancelled
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order && in_array($order['status'], ['pending', 'paid'])) {
                // Update order status to cancelled
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$order_id]);
                
                // Restore product stock if order was paid
                if ($order['status'] === 'paid') {
                    $items_stmt = $pdo->prepare("SELECT product_id, quantity FROM transaction_items WHERE transaction_id = ?");
                    $items_stmt->execute([$order_id]);
                    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($items as $item) {
                        $update_stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                        $update_stmt->execute([$item['quantity'], $item['product_id']]);
                    }
                }
                
                $_SESSION['success_message'] = "Pesanan berhasil dibatalkan";
                header("Location: order_detail.php?id=" . $order_id);
                exit;
            } else {
                $_SESSION['error_message'] = "Pesanan tidak dapat dibatalkan";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['confirm_received'])) {
        try {
            // Update order status to completed
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE id = ? AND user_id = ? AND status = 'shipped'");
            $stmt->execute([$order_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Pesanan berhasil dikonfirmasi sebagai diterima";
            } else {
                $_SESSION['error_message'] = "Gagal mengkonfirmasi pesanan";
            }
            
            header("Location: order_detail.php?id=" . $order_id);
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.full_name, u.email, u.phone, u.address 
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "Pesanan tidak ditemukan!";
        header('Location: orders.php');
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT ti.*, p.name as product_name, p.image
        FROM transaction_items ti 
        LEFT JOIN products p ON ti.product_id = p.id 
        WHERE ti.transaction_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header('Location: orders.php');
    exit;
}
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Detail Pesanan #<?php echo $order['transaction_code']; ?></h2>
        <a href="orders.php" class="btn btn-secondary">Kembali ke Daftar Pesanan</a>
    </div>

    <div class="card-body">
        <!-- Display Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Order Information -->
        <div class="row">
            <div class="col-md-6">
                <div class="info-section">
                    <h4>Informasi Pesanan</h4>
                    <table class="info-table">
                        <tr>
                            <td><strong>Kode Transaksi</strong></td>
                            <td><?php echo $order['transaction_code']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status</strong></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo getStatusText($order['status']); ?>
                                </span>
                                <?php if ($order['status'] == 'paid'): ?>
                                    <br><small class="text-muted">Pesanan Anda sedang diproses oleh admin</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Pesanan</strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Metode Pembayaran</strong></td>
                            <td><?php echo $order['payment_method'] ?: '-'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="info-section">
                    <h4>Informasi Customer</h4>
                    <table class="info-table">
                        <tr>
                            <td><strong>Nama</strong></td>
                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email</strong></td>
                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Telepon</strong></td>
                            <td><?php echo htmlspecialchars($order['phone'] ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Alamat</strong></td>
                            <td><?php echo htmlspecialchars($order['address'] ?: '-'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="info-section">
            <h4>Detail Produk</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="../<?php echo UPLOAD_PATH . $item['image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image-sm me-3">
                                        <?php else: ?>
                                            <div class="product-image-placeholder-sm me-3">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <?php if (isset($item['variant']) && $item['variant']): ?>
                                                <br><small class="text-muted">Variant: <?php echo htmlspecialchars($item['variant']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <?php if (isset($order['shipping_cost']) && $order['shipping_cost'] > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Biaya Pengiriman:</strong></td>
                            <td><strong><?php echo formatPrice($order['shipping_cost']); ?></strong></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <?php if ($order['notes']): ?>
        <div class="info-section">
            <h4>Catatan</h4>
            <div class="notes-box">
                <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <?php if ($order['status'] == 'pending'): ?>
                <!-- Payment Button - Redirect to payment page -->
                <a href="payment.php?id=<?php echo $order['id']; ?>" class="btn btn-warning btn-lg">
                    <i class="fas fa-credit-card me-2"></i>Bayar Sekarang
                </a>
                
                <!-- Cancel Order Form -->
                <form method="POST" class="d-inline" onsubmit="return confirmCancel()">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" name="cancel_order" class="btn btn-danger btn-lg">
                        <i class="fas fa-times me-2"></i>Batalkan Pesanan
                    </button>
                </form>
                
            <?php elseif ($order['status'] == 'shipped'): ?>
                <!-- Confirm Received Form -->
                <form method="POST" class="d-inline" onsubmit="return confirmReceived()">
                    <button type="submit" name="confirm_received" class="btn btn-success btn-lg">
                        <i class="fas fa-check me-2"></i>Konfirmasi Diterima
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.info-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.info-section h4 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}

.info-table {
    width: 100%;
}

.info-table td {
    padding: 0.5rem 0;
    border-bottom: 1px solid #dee2e6;
}

.info-table td:first-child {
    width: 40%;
    font-weight: 500;
}

.notes-box {
    background: white;
    padding: 1rem;
    border-radius: 5px;
    border-left: 4px solid #007bff;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-paid { background: #cce7ff; color: #004085; }
.status-processing { background: #d1ecf1; color: #0c5460; }
.status-ready_pickup { background: #e2e3e5; color: #383d41; }
.status-shipped { background: #d4edda; color: #155724; }
.status-completed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.product-image-sm {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
}

.product-image-placeholder-sm {
    width: 50px;
    height: 50px;
    background: #f8f9fa;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ccc;
}

@media (max-width: 768px) {
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
    
    .card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }
}
</style>

<script>
function confirmCancel() {
    return confirm('Apakah Anda yakin ingin membatalkan pesanan ini? Tindakan ini tidak dapat dibatalkan.');
}

function confirmReceived() {
    return confirm('Apakah Anda yakin ingin mengkonfirmasi bahwa pesanan telah diterima?');
}

<?php
// Helper function to get status text
function getStatusText($status) {
    $statusMap = [
        'pending' => 'Menunggu Pembayaran',
        'paid' => 'Telah Dibayar - Sedang Diproses',
        'processing' => 'Sedang Diproses',
        'ready_pickup' => 'Siap Diambil',
        'shipped' => 'Dikirim',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan'
    ];
    
    return $statusMap[$status] ?? ucfirst(str_replace('_', ' ', $status));
}
?>
</script>

<?php include '../includes/footer.php'; ?>