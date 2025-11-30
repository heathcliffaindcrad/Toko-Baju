<?php
require_once '../includes/auth.php';
$page_title = "Dashboard Admin";
include '../includes/header.php';

// Get statistics dengan cara yang benar
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$users_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$products_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$categories_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
$transactions_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM transactions WHERE DATE(created_at) = CURDATE() AND status = 'completed'");
$today_sales = $stmt->fetchColumn();
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Dashboard</h2>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $users_count; ?></div>
            <div class="stat-label">Total Pengguna</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $products_count; ?></div>
            <div class="stat-label">Total Produk</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $categories_count; ?></div>
            <div class="stat-label">Total Kategori</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $transactions_count; ?></div>
            <div class="stat-label">Total Transaksi</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo formatPrice($today_sales); ?></div>
            <div class="stat-label">Penjualan Hari Ini</div>
        </div>
    </div>
</div>

<?php
// Query untuk mendapatkan produk stok menipis (stok <= 2 tapi > 0)
$stmt_low_stock = $pdo->prepare("
    SELECT name, stock 
    FROM products 
    WHERE stock <= 2 AND stock > 0 AND is_active = TRUE
    ORDER BY stock ASC, name ASC
    LIMIT 10
");
$stmt_low_stock->execute();
$low_stock_products = $stmt_low_stock->fetchAll(PDO::FETCH_ASSOC);

// Query untuk mendapatkan produk stok habis (stok = 0)
$stmt_out_of_stock = $pdo->prepare("
    SELECT name, stock 
    FROM products 
    WHERE stock = 0 AND is_active = TRUE
    ORDER BY name ASC
    LIMIT 10
");
$stmt_out_of_stock->execute();
$out_of_stock_products = $stmt_out_of_stock->fetchAll(PDO::FETCH_ASSOC);

// Query untuk transaksi terbaru
$stmt_recent_transactions = $pdo->prepare("
    SELECT t.transaction_code, t.total_amount, t.status, t.created_at, u.full_name
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
$stmt_recent_transactions->execute();
$recent_transactions = $stmt_recent_transactions->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <!-- Stok Menipis -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Stok Menipis
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($low_stock_products)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo $product['stock']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($product['stock'] == 1): ?>
                                                <span class="badge bg-danger">Sangat Kritis</span>
                                            <?php elseif ($product['stock'] == 2): ?>
                                                <span class="badge bg-warning">Kritik</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Total <?php echo count($low_stock_products); ?> produk stok menipis
                        </small>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-success fa-2x mb-3"></i>
                        <p class="text-muted">Tidak ada produk yang stoknya menipis.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Stok Habis -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    Stok Habis
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($out_of_stock_products)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($out_of_stock_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                                        </td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Restock
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Total <?php echo count($out_of_stock_products); ?> produk stok habis
                        </small>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-success fa-2x mb-3"></i>
                        <p class="text-muted">Tidak ada produk yang stoknya habis.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Transaksi Terbaru -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history me-2"></i>
            Transaksi Terbaru
        </h3>
    </div>
    <div class="card-body">
        <?php if (!empty($recent_transactions)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kode Transaksi</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($transaction['transaction_code']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['full_name']); ?></td>
                                <td><?php echo formatPrice($transaction['total_amount']); ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'pending' => 'bg-warning',
                                        'paid' => 'bg-info',
                                        'processing' => 'bg-primary',
                                        'shipped' => 'bg-success',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger'
                                    ];
                                    $status_text = [
                                        'pending' => 'Menunggu Bayar',
                                        'paid' => 'Dibayar',
                                        'processing' => 'Diproses',
                                        'shipped' => 'Dikirim',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Dibatalkan'
                                    ];
                                    $class = $status_class[$transaction['status']] ?? 'bg-secondary';
                                    $text = $status_text[$transaction['status']] ?? $transaction['status'];
                                    ?>
                                    <span class="badge <?php echo $class; ?>">
                                        <?php echo $text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($transaction['created_at'])); ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('H:i', strtotime($transaction['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <!-- PERBAIKAN DI SINI -->
                                    <?php if (isset($transaction['id'])): ?>
                                        <a href="order_detail.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        </a>
                                    <?php else: ?>
                                        <span class="btn btn-sm btn-outline-secondary disabled">
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-center">
                <a href="transactions.php" class="btn btn-outline-primary">
                    <i class="fas fa-list me-1"></i> Lihat Semua Transaksi
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Belum ada transaksi</h5>
                <p class="text-muted">Transaksi yang dilakukan customer akan muncul di sini.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto refresh setiap 30 detik untuk data real-time
document.addEventListener('DOMContentLoaded', function() {
    function refreshData() {
        // Di sini bisa ditambahkan AJAX call untuk refresh data
        console.log('Refreshing dashboard data...');
    }
    
    // Refresh setiap 30 detik
    setInterval(refreshData, 30000);
    
    // Highlight rows berdasarkan status stok
    const lowStockRows = document.querySelectorAll('.table tbody tr');
    lowStockRows.forEach(row => {
        const stockCell = row.querySelector('td:nth-child(2)');
        if (stockCell) {
            const stock = parseInt(stockCell.textContent);
            if (stock === 1) {
                row.style.backgroundColor = 'rgba(220, 53, 69, 0.05)';
            } else if (stock === 2) {
                row.style.backgroundColor = 'rgba(255, 193, 7, 0.05)';
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>