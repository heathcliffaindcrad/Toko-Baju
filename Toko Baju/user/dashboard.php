<?php
require_once '../includes/auth.php';
$page_title = "Dashboard User";
include '../includes/header.php';

// Get user statistics
$user_id = $_SESSION['user_id'];

// Total orders count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
$stmt->execute([$user_id]);
$orders_count = $stmt->fetchColumn();

// Pending orders count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status IN ('pending', 'paid', 'processing')");
$stmt->execute([$user_id]);
$pending_orders = $stmt->fetchColumn();

// Completed orders count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_orders = $stmt->fetchColumn();

// Get recent orders
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? AND is_read = FALSE 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Dashboard</h2>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $orders_count; ?></div>
            <div class="stat-label">Total Pesanan</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $pending_orders; ?></div>
            <div class="stat-label">Pesanan Diproses</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $completed_orders; ?></div>
            <div class="stat-label">Pesanan Selesai</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pesanan Terbaru</h3>
                <a href="orders.php" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php if ($recent_orders): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['transaction_code']; ?></td>
                                        <td><?php echo formatPrice($order['total_amount']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Belum ada pesanan.</p>
                    <a href="catalog.php" class="btn btn-primary">Mulai Belanja</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Notifikasi Terbaru</h3>
            </div>
            <div class="card-body">
                <?php if ($notifications): ?>
                    <div class="notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item" style="padding: 1rem; border-bottom: 1px solid #f0f0f0;">
                                <div class="notification-title" style="font-weight: bold; margin-bottom: 0.5rem;">
                                    <?php echo $notification['title']; ?>
                                </div>
                                <div class="notification-message" style="color: #666;">
                                    <?php echo $notification['message']; ?>
                                </div>
                                <div class="notification-time" style="font-size: 0.875rem; color: #999;">
                                    <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Tidak ada notifikasi baru.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>