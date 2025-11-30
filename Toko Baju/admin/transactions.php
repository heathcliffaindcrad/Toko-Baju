<?php
require_once '../includes/auth.php';
$page_title = "Kelola Transaksi";
include '../includes/header.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $transaction_id = $_POST['transaction_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $transaction_id])) {
        $_SESSION['success'] = "Status transaksi berhasil diperbarui!";
        
        // Add notification for user
        $stmt = $pdo->prepare("SELECT user_id FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            $status_text = str_replace('_', ' ', $status);
            addNotification($pdo, $transaction['user_id'], 'Status Pesanan Diperbarui', "Status pesanan Anda telah diubah menjadi: " . ucfirst($status_text), 'info');
        }
    } else {
        $_SESSION['error'] = "Gagal memperbarui status transaksi!";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "
    SELECT t.*, u.full_name, u.email, u.phone 
    FROM transactions t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE 1=1
";

$params = [];

if ($status_filter) {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $query .= " AND DATE(t.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(t.created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Kelola Transaksi</h2>
    </div>

    <!-- Filters -->
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="transactions.php" class="btn">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode Transaksi</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Metode Bayar</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td>
                                <strong><?php echo $transaction['transaction_code']; ?></strong>
                            </td>
                            <td>
                                <div><?php echo $transaction['full_name']; ?></div>
                                <small class="text-muted"><?php echo $transaction['email']; ?></small>
                            </td>
                            <td><?php echo formatPrice($transaction['total_amount']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $transaction['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $transaction['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $transaction['payment_method'] ?: '-'; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="showDetail(<?php echo $transaction['id']; ?>)">Detail</button>
                                <button class="btn btn-sm btn-success" onclick="showUpdateForm(<?php echo $transaction['id']; ?>)">Update Status</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; margin: 2rem auto; padding: 2rem; border-radius: 10px; max-width: 800px;">
        <div id="detailContent">
            <!-- Detail content will be loaded here -->
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="background: white; margin: 2rem auto; padding: 2rem; border-radius: 10px; max-width: 500px;">
        <div id="updateFormContent">
            <!-- Update form will be loaded here -->
        </div>
    </div>
</div>

<script>
function showDetail(transactionId) {
    // Show loading state
    document.getElementById('detailContent').innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff;"></i>
            <p>Memuat detail transaksi...</p>
        </div>
    `;
    document.getElementById('detailModal').style.display = 'block';
    
    fetch('ajax/get_transaction_detail.php?id=' + transactionId)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            document.getElementById('detailContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('detailContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Gagal memuat detail transaksi: ${error}
                </div>
                <div style="text-align: center; margin-top: 1rem;">
                    <button class="btn btn-primary" onclick="hideDetail()">Tutup</button>
                </div>
            `;
        });
}

function showUpdateForm(transactionId) {
    // Show loading state
    document.getElementById('updateFormContent').innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff;"></i>
            <p>Memuat form update status...</p>
        </div>
    `;
    document.getElementById('updateModal').style.display = 'block';
    
    // Coba berbagai path untuk menemukan file yang benar
    const paths = [
        `ajax/get_update_status_form.php?id=${transactionId}`,
        `./ajax/get_update_status_form.php?id=${transactionId}`,
        `../admin/ajax/get_update_status_form.php?id=${transactionId}`,
        `get_update_status_form.php?id=${transactionId}`
    ];
    
    let currentPathIndex = 0;
    
    function tryNextPath() {
        if (currentPathIndex >= paths.length) {
            document.getElementById('updateFormContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Tidak dapat memuat form update status. Pastikan file get_update_status_form.php ada.
                </div>
                <div style="text-align: center; margin-top: 1rem;">
                    <button class="btn btn-primary" onclick="hideUpdateForm()">Tutup</button>
                </div>
            `;
            return;
        }
        
        const currentPath = paths[currentPathIndex];
        console.log('Mencoba path:', currentPath);
        
        fetch(currentPath)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.text();
            })
            .then(html => {
                document.getElementById('updateFormContent').innerHTML = html;
                console.log('Form berhasil dimuat');
            })
            .catch(error => {
                console.error('Error dengan path:', currentPath, error);
                currentPathIndex++;
                tryNextPath();
            });
    }
    
    tryNextPath();
}

function hideDetail() {
    document.getElementById('detailModal').style.display = 'none';
    document.getElementById('detailContent').innerHTML = '';
}

function hideUpdateForm() {
    document.getElementById('updateModal').style.display = 'none';
    document.getElementById('updateFormContent').innerHTML = '';
}

// Fungsi untuk submit status update (akan dipanggil dari form)
function submitStatusUpdate() {
    const form = document.getElementById('updateStatusForm');
    if (!form) {
        console.error('Form tidak ditemukan');
        return;
    }
    
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitBtn');
    const messageDiv = document.getElementById('formMessage');
    
    if (!submitBtn) {
        console.error('Submit button tidak ditemukan');
        return;
    }
    
    // Show loading state
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    submitBtn.disabled = true;
    
    // Add action identifier
    formData.append('update_status', 'true');
    
    console.log('Mengirim data update status...');
    
    // Submit ke halaman yang sama (transactions.php)
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.text();
    })
    .then(html => {
        console.log('Response received');
        
        // Cek jika berhasil dari response
        if (html.includes('berhasil') || html.includes('success')) {
            showMessage('Status transaksi berhasil diperbarui!', 'success', messageDiv);
            
            // Close modal and reload page after success
            setTimeout(() => {
                hideUpdateForm();
                location.reload();
            }, 1500);
        } else {
            showMessage('Gagal memperbarui status transaksi!', 'error', messageDiv);
            resetButton(submitBtn, originalText);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Terjadi kesalahan: ' + error.message, 'error', messageDiv);
        resetButton(submitBtn, originalText);
    });
}

// Helper functions untuk form
function showMessage(text, type, messageDiv) {
    if (!messageDiv) {
        console.error('Message div tidak ditemukan');
        return;
    }
    
    messageDiv.innerHTML = `
        <div class="alert alert-${type === 'success' ? 'success' : 'danger'}">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${text}
        </div>
    `;
    messageDiv.style.display = 'block';
    
    // Scroll to message
    messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function resetButton(button, originalText) {
    button.innerHTML = originalText;
    button.disabled = false;
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('detailModal')) {
        hideDetail();
    }
    if (e.target === document.getElementById('updateModal')) {
        hideUpdateForm();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideDetail();
        hideUpdateForm();
    }
});

// Pastikan fungsi tersedia secara global
window.submitStatusUpdate = submitStatusUpdate;
window.hideDetail = hideDetail;
window.hideUpdateForm = hideUpdateForm;
window.showUpdateForm = showUpdateForm;
</script>

<?php include '../includes/footer.php'; ?>