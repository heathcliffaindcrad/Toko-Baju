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
        $stmt = $pdo->prepare("SELECT id, transaction_code, status FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            ?>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1rem;">
                <h3 style="margin: 0;">Update Status Transaksi</h3>
                <button type="button" onclick="hideUpdateForm()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6c757d;">&times;</button>
            </div>
            
            <div>
                <form id="updateStatusForm">
                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Update status untuk: <strong><?php echo $transaction['transaction_code']; ?></strong>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status Saat Ini</label>
                        <div>
                            <span class="badge badge-<?php echo $transaction['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $transaction['status'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status Baru *</label>
                        <select name="status" class="form-select" required>
                            <option value="pending" <?php echo $transaction['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $transaction['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="processing" <?php echo $transaction['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="ready_pickup" <?php echo $transaction['status'] == 'ready_pickup' ? 'selected' : ''; ?>>Ready Pickup</option>
                            <option value="shipped" <?php echo $transaction['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="completed" <?php echo $transaction['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $transaction['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan untuk customer..."></textarea>
                    </div>
                    
                    <div id="formMessage" style="display: none; margin-bottom: 1rem;"></div>
                </form>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-secondary" onclick="hideUpdateForm()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitStatusUpdate()" id="submitBtn">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>
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