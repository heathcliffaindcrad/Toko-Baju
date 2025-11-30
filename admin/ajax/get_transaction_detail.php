<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Akses ditolak!');
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $transaction_id = $_GET['id'];
    
    try {
        // Get transaction details
        $stmt = $pdo->prepare("
            SELECT t.*, u.full_name, u.email, u.phone, u.address 
            FROM transactions t 
            LEFT JOIN users u ON t.user_id = u.id 
            WHERE t.id = ?
        ");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            // Get transaction items
            $stmt = $pdo->prepare("
                SELECT ti.*, p.name as product_name
                FROM transaction_items ti 
                LEFT JOIN products p ON ti.product_id = p.id 
                WHERE ti.transaction_id = ?
            ");
            $stmt->execute([$transaction_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1rem;">
                <h3 style="margin: 0;">Detail Transaksi #<?php echo $transaction['transaction_code']; ?></h3>
                <button type="button" onclick="hideDetail()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6c757d;">&times;</button>
            </div>
            
            <div>
                <!-- Customer Information -->
                <div style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #eee;">
                    <h4>Informasi Customer</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($transaction['full_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($transaction['email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Telepon:</strong> <?php echo htmlspecialchars($transaction['phone'] ?: '-'); ?></p>
                            <p><strong>Alamat:</strong> <?php echo htmlspecialchars($transaction['address'] ?: '-'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Transaction Information -->
                <div style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #eee;">
                    <h4>Informasi Transaksi</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Kode Transaksi:</strong> <?php echo $transaction['transaction_code']; ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-<?php echo $transaction['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $transaction['status'])); ?>
                                </span>
                            </p>
                            <p><strong>Metode Pembayaran:</strong> <?php echo $transaction['payment_method'] ?: '-'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tanggal Transaksi:</strong> <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></p>
                            <p><strong>Total Amount:</strong> <?php echo formatPrice($transaction['total_amount']); ?></p>
                            <?php if (isset($transaction['shipping_cost']) && $transaction['shipping_cost'] > 0): ?>
                                <p><strong>Biaya Pengiriman:</strong> <?php echo formatPrice($transaction['shipping_cost']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div style="margin-bottom: 2rem;">
                    <h4>Detail Pesanan</h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                <?php if (isset($item['variant']) && $item['variant']): ?>
                                                    <br><small class="text-muted">Variant: <?php echo htmlspecialchars($item['variant']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo formatPrice($item['price']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td><strong><?php echo formatPrice($transaction['total_amount']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Notes -->
                <?php if ($transaction['notes']): ?>
                <div style="margin-bottom: 2rem;">
                    <h4>Catatan</h4>
                    <p style="background: #f8f9fa; padding: 1rem; border-radius: 5px; border-left: 4px solid #007bff;">
                        <?php echo nl2br(htmlspecialchars($transaction['notes'])); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-secondary" onclick="hideDetail()">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="showUpdateForm(<?php echo $transaction_id; ?>)">Update Status</button>
            </div>

            <style>
            .badge-pending { background: #ffc107; color: #000; }
            .badge-paid { background: #17a2b8; color: #fff; }
            .badge-processing { background: #007bff; color: #fff; }
            .badge-ready_pickup { background: #6c757d; color: #fff; }
            .badge-shipped { background: #28a745; color: #fff; }
            .badge-completed { background: #28a745; color: #fff; }
            .badge-cancelled { background: #dc3545; color: #fff; }
            
            .table th {
                background: #f8f9fa;
                font-weight: 600;
            }
            
            .table td {
                vertical-align: middle;
            }
            </style>
            <?php
        } else {
            echo '<div class="alert alert-danger">Transaksi tidak ditemukan!</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div class="alert alert-danger">ID transaksi tidak valid!</div>';
}
?>