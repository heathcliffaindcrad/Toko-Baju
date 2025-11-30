<?php
require_once '../includes/auth.php';
$page_title = "Pesanan Saya";
include '../includes/header.php';

// Get user orders
$user_id = $_SESSION['user_id'];

// Build query with filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM transactions WHERE user_id = ?";
$params = [$user_id];

if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND transaction_code LIKE ?";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Pesanan Saya</h2>
    </div>

    <!-- Filters -->
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="ready_pickup" <?php echo $status_filter == 'ready_pickup' ? 'selected' : ''; ?>>Ready Pickup</option>
                    <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cari Kode Transaksi</label>
                <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Masukkan kode transaksi...">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="orders.php" class="btn">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="card-body">
        <?php if ($orders): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Transaksi</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Metode Bayar</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $order['transaction_code']; ?></strong>
                                </td>
                                <td><?php echo formatPrice($order['total_amount']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $order['payment_method'] ?: '-'; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Anda belum memiliki pesanan.</p>
                <a href="catalog.php" class="btn btn-primary">Mulai Belanja</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>