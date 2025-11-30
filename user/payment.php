<?php
require_once '../includes/auth.php';
$page_title = "Pembayaran Pesanan";
include '../includes/header.php';

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Pesanan tidak valid!";
    header('Location: orders.php');
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Debug: Tampilkan informasi dasar
error_log("Payment Process Started - Order ID: $order_id, User ID: $user_id");

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
        $_SESSION['error_message'] = "Pesanan tidak ditemukan!";
        header('Location: orders.php');
        exit;
    }
    
    // Debug: Tampilkan status saat ini
    error_log("Current Order Status: " . $order['status']);
    
    // Check if order can be paid
    if ($order['status'] !== 'pending') {
        $_SESSION['error_message'] = "Pesanan ini tidak dapat dibayar. Status: " . getStatusText($order['status']);
        header('Location: order_detail.php?id=' . $order_id);
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
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: orders.php');
    exit;
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_proof = $_FILES['payment_proof'] ?? null;
    
    error_log("Payment Submission - Method: $payment_method");
    
    if (empty($payment_method)) {
        $error = "Pilih metode pembayaran terlebih dahulu";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Handle payment proof upload
            $proof_filename = null;
            if ($payment_proof && $payment_proof['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                if (in_array($payment_proof['type'], $allowed_types) && $payment_proof['size'] <= $max_size) {
                    $file_extension = pathinfo($payment_proof['name'], PATHINFO_EXTENSION);
                    $proof_filename = 'payment_proof_' . $order['transaction_code'] . '_' . time() . '.' . $file_extension;
                    $upload_path = '../' . UPLOAD_PATH . 'payments/' . $proof_filename;
                    
                    // Create payments directory if not exists
                    if (!is_dir(dirname($upload_path))) {
                        mkdir(dirname($upload_path), 0755, true);
                    }
                    
                    if (move_uploaded_file($payment_proof['tmp_name'], $upload_path)) {
                        $proof_filename = 'payments/' . $proof_filename;
                        error_log("Payment proof uploaded: $proof_filename");
                    } else {
                        throw new Exception("Gagal mengupload bukti pembayaran");
                    }
                } else {
                    throw new Exception("File bukti pembayaran tidak valid. Maksimal 2MB (JPG, PNG, PDF)");
                }
            }
            
            // For COD, no payment proof required
            if ($payment_method === 'cod' && !$proof_filename) {
                $proof_filename = 'COD - No proof required';
            }
            
            // Debug sebelum update
            error_log("Before UPDATE - Order ID: $order_id, Status: pending, Method: $payment_method");
            
            // Update order status to PAID
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'paid', 
                    payment_method = ?,
                    payment_proof = ?,
                    paid_at = NOW()
                WHERE id = ? AND user_id = ? AND status = 'pending'
            ");
            
            $stmt->execute([$payment_method, $proof_filename, $order_id, $user_id]);
            $rowCount = $stmt->rowCount();
            
            error_log("UPDATE executed - Rows affected: $rowCount");
            
            if ($rowCount > 0) {
                $pdo->commit();
                error_log("Payment SUCCESS - Order ID: $order_id updated to paid");
                
                // Success message based on payment method
                if ($payment_method === 'cod') {
                    $_SESSION['success_message'] = "Pesanan COD berhasil! Pesanan Anda akan segera diproses.";
                } else {
                    $_SESSION['success_message'] = "Pembayaran berhasil! Pesanan Anda sedang diproses.";
                }
                
                // Redirect to order detail
                header('Location: order_detail.php?id=' . $order_id);
                exit;
                
            } else {
                // Check why update failed
                $check_stmt = $pdo->prepare("SELECT status FROM transactions WHERE id = ? AND user_id = ?");
                $check_stmt->execute([$order_id, $user_id]);
                $current_status = $check_stmt->fetchColumn();
                
                error_log("UPDATE FAILED - Current status: $current_status");
                throw new Exception("Gagal memproses pembayaran. Status pesanan saat ini: " . getStatusText($current_status));
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
            error_log("Payment ERROR: " . $e->getMessage());
        }
    }
}
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Progress Steps -->
            <div class="progress-steps mb-5">
                <div class="step completed">
                    <div class="step-number">1</div>
                    <div class="step-label">Pesanan</div>
                </div>
                <div class="step completed">
                    <div class="step-number">2</div>
                    <div class="step-label">Konfirmasi</div>
                </div>
                <div class="step active">
                    <div class="step-number">3</div>
                    <div class="step-label">Pembayaran</div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-label">Selesai</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title mb-0">
                        <i class="fas fa-credit-card me-2"></i>Pembayaran Pesanan
                    </h2>
                </div>

                <div class="card-body">
                    <!-- Order Summary -->
                    <div class="order-summary mb-4">
                        <h5 class="mb-3">Ringkasan Pesanan</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Kode Transaksi</strong></td>
                                        <td><?php echo $order['transaction_code']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Pesanan</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status</strong></td>
                                        <td>
                                            <span class="badge bg-warning text-dark"><?php echo getStatusText($order['status']); ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Total Produk</strong></td>
                                        <td><?php echo count($items); ?> item</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Subtotal</strong></td>
                                        <td><?php echo formatPrice($order['total_amount'] - ($order['shipping_cost'] ?? 0)); ?></td>
                                    </tr>
                                    <?php if (isset($order['shipping_cost']) && $order['shipping_cost'] > 0): ?>
                                    <tr>
                                        <td><strong>Biaya Pengiriman</strong></td>
                                        <td><?php echo formatPrice($order['shipping_cost']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr class="table-primary">
                                        <td><strong>Total Pembayaran</strong></td>
                                        <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Payment Form -->
                    <form method="POST" enctype="multipart/form-data" id="paymentForm">
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Payment Method -->
                                <div class="payment-method-section mb-4">
                                    <h5 class="mb-3">Pilih Metode Pembayaran</h5>
                                    <div class="payment-options">
                                        <div class="payment-option">
                                            <input type="radio" name="payment_method" value="transfer" id="transfer" class="payment-radio" checked>
                                            <label for="transfer" class="payment-label">
                                                <div class="payment-icon">
                                                    <i class="fas fa-university"></i>
                                                </div>
                                                <div class="payment-info">
                                                    <div class="payment-name">Transfer Bank</div>
                                                    <div class="payment-desc">Transfer melalui ATM/Internet Banking/Mobile Banking</div>
                                                </div>
                                            </label>
                                        </div>

                                        <div class="payment-option">
                                            <input type="radio" name="payment_method" value="qris" id="qris" class="payment-radio">
                                            <label for="qris" class="payment-label">
                                                <div class="payment-icon">
                                                    <i class="fas fa-qrcode"></i>
                                                </div>
                                                <div class="payment-info">
                                                    <div class="payment-name">QRIS</div>
                                                    <div class="payment-desc">Scan QR code melalui aplikasi e-wallet</div>
                                                </div>
                                            </label>
                                        </div>

                                        <div class="payment-option">
                                            <input type="radio" name="payment_method" value="cod" id="cod" class="payment-radio">
                                            <label for="cod" class="payment-label">
                                                <div class="payment-icon">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </div>
                                                <div class="payment-info">
                                                    <div class="payment-name">Cash on Delivery (COD)</div>
                                                    <div class="payment-desc">Bayar ketika barang diterima</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Instructions -->
                                <div class="payment-instructions-section mb-4">
                                    <div id="transferInstructions" class="payment-instruction active">
                                        <h6>Instruksi Transfer Bank:</h6>
                                        <div class="instruction-content">
                                            <p>Silakan transfer ke salah satu rekening berikut:</p>
                                            <div class="bank-accounts">
                                                <div class="bank-account">
                                                    <div class="bank-logo">BCA</div>
                                                    <div class="bank-info">
                                                        <strong>123-456-7890</strong><br>
                                                        <span>PT. Fashion Store Indonesia</span>
                                                    </div>
                                                </div>
                                                <div class="bank-account">
                                                    <div class="bank-logo">BRI</div>
                                                    <div class="bank-info">
                                                        <strong>098-765-4321</strong><br>
                                                        <span>PT. Fashion Store Indonesia</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="alert alert-info mt-3">
                                                <strong>Penting:</strong> Transfer tepat sebesar <strong><?php echo formatPrice($order['total_amount']); ?></strong> dan upload bukti transfer di bawah.
                                            </div>
                                        </div>
                                    </div>

                                    <div id="qrisInstructions" class="payment-instruction">
                                        <h6>Instruksi QRIS:</h6>
                                        <div class="instruction-content text-center">
                                            <div class="qris-code mb-3">
                                                <div style="width: 200px; height: 200px; background: #f8f9fa; border: 2px dashed #dee2e6; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px;">
                                                    <i class="fas fa-qrcode" style="font-size: 4rem; color: #6c757d;"></i>
                                                </div>
                                            </div>
                                            <p>Scan QR code di atas menggunakan aplikasi e-wallet atau mobile banking Anda</p>
                                            <div class="alert alert-info">
                                                <strong>Nominal:</strong> <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="codInstructions" class="payment-instruction">
                                        <h6>Instruksi COD:</h6>
                                        <div class="instruction-content">
                                            <p>Anda akan membayar ketika barang sudah sampai di tempat Anda.</p>
                                            <div class="alert alert-warning">
                                                <strong>Perhatian:</strong> Siapkan uang pas sebesar <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Proof Upload -->
                                <div class="payment-proof-section mb-4">
                                    <h6>Upload Bukti Pembayaran <small class="text-muted">(Untuk Transfer Bank dan QRIS)</small></h6>
                                    <div class="upload-area" id="uploadArea">
                                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                        <p class="upload-text">Klik untuk upload bukti pembayaran</p>
                                        <p class="upload-hint">Format: JPG, PNG, PDF (Maks. 2MB)</p>
                                        <input type="file" name="payment_proof" id="payment_proof" accept=".jpg,.jpeg,.png,.pdf" class="d-none">
                                        <div id="fileName" class="file-name mt-2"></div>
                                    </div>
                                    <div id="uploadPreview" class="upload-preview mt-3"></div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Order Summary Sidebar -->
                                <div class="order-summary-sidebar">
                                    <h6 class="sidebar-title">Detail Pesanan</h6>
                                    
                                    <!-- Order Items -->
                                    <div class="order-items">
                                        <?php foreach ($items as $item): ?>
                                            <div class="order-item">
                                                <div class="item-image">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="../<?php echo UPLOAD_PATH . $item['image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                    <?php else: ?>
                                                        <div class="image-placeholder">
                                                            <i class="fas fa-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="item-info">
                                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                    <div class="item-meta">
                                                        <?php echo formatPrice($item['price']); ?> × <?php echo $item['quantity']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Payment Summary -->
                                    <div class="payment-summary">
                                        <div class="summary-row">
                                            <span>Subtotal:</span>
                                            <span><?php echo formatPrice($order['total_amount'] - ($order['shipping_cost'] ?? 0)); ?></span>
                                        </div>
                                        <?php if (isset($order['shipping_cost']) && $order['shipping_cost'] > 0): ?>
                                        <div class="summary-row">
                                            <span>Pengiriman:</span>
                                            <span><?php echo formatPrice($order['shipping_cost']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="summary-row total">
                                            <span><strong>Total:</strong></span>
                                            <span><strong><?php echo formatPrice($order['total_amount']); ?></strong></span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="action-buttons">
                                        <button type="submit" name="process_payment" class="btn btn-primary btn-lg w-100 mb-2">
                                            <i class="fas fa-credit-card me-2"></i>Konfirmasi Pembayaran
                                        </button>
                                        <a href="order_detail.php?id=<?php echo $order_id; ?>" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-arrow-left me-2"></i>Kembali
                                        </a>
                                    </div>

                                    <!-- Security Badge -->
                                    <div class="security-badge text-center mt-3">
                                        <i class="fas fa-lock text-success me-1"></i>
                                        <small class="text-muted">Pembayaran Aman & Terenkripsi</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentRadios = document.querySelectorAll('.payment-radio');
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('payment_proof');
    
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'cod') {
                uploadArea.style.opacity = '0.6';
                uploadArea.style.pointerEvents = 'none';
                fileInput.value = ''; // Clear file input
            } else {
                uploadArea.style.opacity = '1';
                uploadArea.style.pointerEvents = 'auto';
            }
        });
    });
});

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