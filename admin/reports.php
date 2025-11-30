<?php
require_once '../includes/auth.php';
$page_title = "Laporan";
include '../includes/header.php';

// Set default date range (current month)
$current_month = date('Y-m');
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'sales';

// Handle export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_' . $report_type . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    if ($report_type == 'sales') {
        fputcsv($output, ['Tanggal', 'Jumlah Transaksi', 'Total Penjualan']);
        // Sales report data
    } elseif ($report_type == 'products') {
        fputcsv($output, ['Produk', 'Terjual', 'Total Pendapatan']);
        // Products report data
    }
    
    fclose($output);
    exit;
}

// PERBAIKAN: Validasi tanggal sebelum query
if (empty($start_date) || !DateTime::createFromFormat('Y-m-d', $start_date)) {
    $start_date = date('Y-m-01');
}

if (empty($end_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
    $end_date = date('Y-m-t');
}

// PERBAIKAN: Get sales report data dengan filter yang benar
$sales_query = "
    SELECT 
        DATE(t.created_at) as date,
        COUNT(*) as transaction_count,
        SUM(t.total_amount) as total_sales
    FROM transactions t
    WHERE t.status = 'completed' 
    AND DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY DATE(t.created_at)
    ORDER BY date DESC
";

$stmt = $pdo->prepare($sales_query);
$stmt->execute([$start_date, $end_date]);
$sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get best selling products
$products_query = "
    SELECT 
        p.name as product_name,
        SUM(ti.quantity) as total_sold,
        SUM(ti.quantity * ti.price) as total_revenue
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE t.status = 'completed'
    AND DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 10
";

$stmt = $pdo->prepare($products_query);
$stmt->execute([$start_date, $end_date]);
$best_selling_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// PERBAIKAN: Get summary statistics yang sesuai dengan dashboard
$summary_query = "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as average_order_value
    FROM transactions 
    WHERE status = 'completed'
    AND DATE(created_at) BETWEEN ? AND ?
";

$stmt = $pdo->prepare($summary_query);
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// PERBAIKAN: Hitung total produk terjual dengan benar
$total_products_sold_query = "
    SELECT SUM(ti.quantity) as total_products_sold
    FROM transaction_items ti
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE t.status = 'completed'
    AND DATE(t.created_at) BETWEEN ? AND ?
";

$stmt = $pdo->prepare($total_products_sold_query);
$stmt->execute([$start_date, $end_date]);
$total_products = $stmt->fetch(PDO::FETCH_ASSOC);

// PERBAIKAN: Get monthly comparison dengan periode yang benar
$prev_month_start = date('Y-m-01', strtotime('-1 month'));
$prev_month_end = date('Y-m-t', strtotime('-1 month'));

$prev_month_query = "
    SELECT 
        COUNT(*) as transaction_count,
        SUM(total_amount) as total_revenue
    FROM transactions 
    WHERE status = 'completed'
    AND DATE(created_at) BETWEEN ? AND ?
";

$stmt = $pdo->prepare($prev_month_query);
$stmt->execute([$prev_month_start, $prev_month_end]);
$prev_month = $stmt->fetch(PDO::FETCH_ASSOC);

// PERBAIKAN: Hitung statistik tambahan seperti di dashboard
$all_time_stats_query = "
    SELECT 
        COUNT(*) as total_all_transactions,
        SUM(total_amount) as total_all_revenue
    FROM transactions 
    WHERE status = 'completed'
";

$stmt = $pdo->prepare($all_time_stats_query);
$stmt->execute();
$all_time_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// PERBAIKAN: Hitung transaksi hari ini
$today_stats_query = "
    SELECT 
        COUNT(*) as today_transactions,
        SUM(total_amount) as today_revenue
    FROM transactions 
    WHERE status = 'completed'
    AND DATE(created_at) = CURDATE()
";

$stmt = $pdo->prepare($today_stats_query);
$stmt->execute();
$today_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="reports-container">
    <div class="reports-header">
        <h1 class="reports-title">Laporan</h1>
        <a href="?<?php echo http_build_query($_GET); ?>&export=1" class="btn-export">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </div>

    <!-- Filters -->
    <div class="filters-container">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="report_type">Jenis Laporan</label>
                <select id="report_type" name="report_type" class="filter-control">
                    <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>Laporan Penjualan</option>
                    <option value="products" <?php echo $report_type == 'products' ? 'selected' : ''; ?>>Produk Terlaris</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="start_date">Dari Tanggal</label>
                <input type="date" id="start_date" name="start_date" class="filter-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="filter-group">
                <label for="end_date">Sampai Tanggal</label>
                <input type="date" id="end_date" name="end_date" class="filter-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="filter-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn-primary" style="padding: 0.5rem 1.5rem;">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- PERBAIKAN: Summary Statistics yang sesuai dengan dashboard -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-number"><?php echo $summary['total_transactions'] ?? 0; ?></div>
            <div class="summary-label">Total Transaksi (Periode)</div>
            <div class="summary-period"><?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></div>
            <?php if ($prev_month['transaction_count']): ?>
                <?php 
                $growth = $summary['total_transactions'] - $prev_month['transaction_count'];
                $growth_percent = $prev_month['transaction_count'] > 0 ? ($growth / $prev_month['transaction_count']) * 100 : 0;
                ?>
                <div class="summary-comparison <?php echo $growth >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-<?php echo $growth >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                    <?php echo $growth >= 0 ? '+' : ''; ?><?php echo number_format($growth_percent, 1); ?>% dari bulan lalu
                </div>
            <?php endif; ?>
        </div>
        <div class="summary-card">
            <div class="summary-number"><?php echo formatPrice($summary['total_revenue'] ?? 0); ?></div>
            <div class="summary-label">Total Pendapatan (Periode)</div>
            <div class="summary-period"><?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></div>
            <?php if ($prev_month['total_revenue']): ?>
                <?php 
                $revenue_growth = $summary['total_revenue'] - $prev_month['total_revenue'];
                $revenue_percent = $prev_month['total_revenue'] > 0 ? ($revenue_growth / $prev_month['total_revenue']) * 100 : 0;
                ?>
                <div class="summary-comparison <?php echo $revenue_growth >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-<?php echo $revenue_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                    <?php echo $revenue_growth >= 0 ? '+' : ''; ?><?php echo number_format($revenue_percent, 1); ?>% dari bulan lalu
                </div>
            <?php endif; ?>
        </div>
        <div class="summary-card">
            <div class="summary-number"><?php echo formatPrice($summary['average_order_value'] ?? 0); ?></div>
            <div class="summary-label">Rata-rata Transaksi</div>
            <div class="summary-period">Periode Terpilih</div>
        </div>
        <div class="summary-card">
            <div class="summary-number"><?php echo $total_products['total_products_sold'] ?? 0; ?></div>
            <div class="summary-label">Total Produk Terjual</div>
            <div class="summary-period"><?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></div>
        </div>
    </div>

    <!-- PERBAIKAN: Tambahan statistik global seperti dashboard -->
    <div class="global-stats">
        <h3>Statistik Global</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?php echo $all_time_stats['total_all_transactions'] ?? 0; ?></div>
                <div class="stat-label">Total Transaksi (Semua Waktu)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo formatPrice($all_time_stats['total_all_revenue'] ?? 0); ?></div>
                <div class="stat-label">Total Pendapatan (Semua Waktu)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $today_stats['today_transactions'] ?? 0; ?></div>
                <div class="stat-label">Transaksi Hari Ini</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo formatPrice($today_stats['today_revenue'] ?? 0); ?></div>
                <div class="stat-label">Pendapatan Hari Ini</div>
            </div>
        </div>
    </div>

    <!-- Reports Content -->
    <div class="reports-content">
        <?php if ($report_type == 'sales'): ?>
            <!-- Sales Report -->
            <div class="section-title">Laporan Penjualan Harian (<?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>)</div>
            
            <?php if (!empty($sales_data)): ?>
                <div class="table-responsive">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah Transaksi</th>
                                <th>Total Penjualan</th>
                                <th>Rata-rata per Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total_transactions = 0;
                            $grand_total_sales = 0;
                            ?>
                            <?php foreach ($sales_data as $data): ?>
                                <?php 
                                $grand_total_transactions += $data['transaction_count'];
                                $grand_total_sales += $data['total_sales'];
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($data['date'])); ?></td>
                                    <td><?php echo $data['transaction_count']; ?></td>
                                    <td><?php echo formatPrice($data['total_sales']); ?></td>
                                    <td><?php echo formatPrice($data['total_sales'] / $data['transaction_count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <!-- Grand Total Row -->
                            <tr class="grand-total">
                                <td><strong>Total</strong></td>
                                <td><strong><?php echo $grand_total_transactions; ?></strong></td>
                                <td><strong><?php echo formatPrice($grand_total_sales); ?></strong></td>
                                <td><strong><?php echo formatPrice($grand_total_sales / $grand_total_transactions); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <p>Tidak ada data penjualan untuk periode yang dipilih.</p>
                </div>
            <?php endif; ?>

        <?php elseif ($report_type == 'products'): ?>
            <!-- Products Report -->
            <div class="section-title">10 Produk Terlaris (<?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>)</div>
            
            <?php if (!empty($best_selling_products)): ?>
                <div class="table-responsive">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Terjual</th>
                                <th>Total Pendapatan</th>
                                <th>Rata-rata Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total_sold = 0;
                            $grand_total_revenue = 0;
                            ?>
                            <?php foreach ($best_selling_products as $product): ?>
                                <?php 
                                $grand_total_sold += $product['total_sold'];
                                $grand_total_revenue += $product['total_revenue'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo $product['total_sold']; ?> pcs</td>
                                    <td><?php echo formatPrice($product['total_revenue']); ?></td>
                                    <td><?php echo formatPrice($product['total_revenue'] / $product['total_sold']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <!-- Grand Total Row -->
                            <tr class="grand-total">
                                <td><strong>Total</strong></td>
                                <td><strong><?php echo $grand_total_sold; ?> pcs</strong></td>
                                <td><strong><?php echo formatPrice($grand_total_revenue); ?></strong></td>
                                <td><strong><?php echo formatPrice($grand_total_revenue / $grand_total_sold); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box"></i>
                    <p>Tidak ada data produk untuk periode yang dipilih.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto update URL when filters change
document.getElementById('report_type').addEventListener('change', function() {
    this.form.submit();
});

// Date validation
document.getElementById('start_date').addEventListener('change', function() {
    const endDate = document.getElementById('end_date');
    if (this.value > endDate.value) {
        endDate.value = this.value;
    }
});

document.getElementById('end_date').addEventListener('change', function() {
    const startDate = document.getElementById('start_date');
    if (this.value < startDate.value) {
        startDate.value = this.value;
    }
});
</script>

<style>
.global-stats {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    border: 1px solid #e9ecef;
}

.global-stats h3 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1.2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-item {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #dee2e6;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.grand-total {
    background-color: #f8f9fa;
    font-weight: bold;
}

.grand-total td {
    border-top: 2px solid #dee2e6;
}

.summary-period {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.positive {
    color: #28a745;
}

.negative {
    color: #dc3545;
}
</style>

<?php include '../includes/footer.php'; ?>