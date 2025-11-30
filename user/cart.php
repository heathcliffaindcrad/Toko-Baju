<?php
require_once '../includes/auth.php';
$page_title = "Keranjang Belanja";
include '../includes/header.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_to_cart'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        // Check if product exists and has enough stock
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = TRUE AND stock >= ?");
        $stmt->execute([$product_id, $quantity]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Add to cart or update quantity
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
            $_SESSION['success'] = "Produk berhasil ditambahkan ke keranjang!";
        } else {
            $_SESSION['error'] = "Produk tidak tersedia atau stok tidak mencukupi!";
        }
    }
    elseif (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        $_SESSION['success'] = "Keranjang berhasil diperbarui!";
    }
    elseif (isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success'] = "Produk berhasil dihapus dari keranjang!";
    }
    elseif (isset($_POST['checkout'])) {
        if (empty($_SESSION['cart'])) {
            $_SESSION['error'] = "Keranjang belanja kosong!";
        } else {
            // Process checkout
            $total_amount = 0;
            $transaction_code = generateTransactionCode();
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Create transaction
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (user_id, transaction_code, total_amount, shipping_address, payment_method) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $shipping_address = $_SESSION['address'] ?? '';
                $payment_method = $_POST['payment_method'] ?? 'transfer';
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $transaction_code,
                    0, // Will update after calculating
                    $shipping_address,
                    $payment_method
                ]);
                
                $transaction_id = $pdo->lastInsertId();
                $total_amount = 0;
                
                // Add transaction items and update stock
                foreach ($_SESSION['cart'] as $product_id => $quantity) {
                    // Get product details
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = TRUE AND stock >= ? FOR UPDATE");
                    $stmt->execute([$product_id, $quantity]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$product) {
                        throw new Exception("Produk tidak tersedia atau stok tidak mencukupi!");
                    }
                    
                    $item_total = $product['price'] * $quantity;
                    $total_amount += $item_total;
                    
                    // Add transaction item
                    $stmt = $pdo->prepare("
                        INSERT INTO transaction_items (transaction_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$transaction_id, $product_id, $quantity, $product['price']]);
                    
                    // Update product stock
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$quantity, $product_id]);
                }
                
                // Update transaction total amount
                $stmt = $pdo->prepare("UPDATE transactions SET total_amount = ? WHERE id = ?");
                $stmt->execute([$total_amount, $transaction_id]);
                
                // Commit transaction
                $pdo->commit();
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                $_SESSION['success'] = "Checkout berhasil! Kode transaksi: " . $transaction_code;
                redirect('orders.php');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Checkout gagal: " . $e->getMessage();
            }
        }
    }
}

// Get cart products
$cart_products = [];
$total_amount = 0;

if (!empty($_SESSION['cart'])) {
    $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE id IN ($placeholders) AND is_active = TRUE
    ");
    $stmt->execute(array_keys($_SESSION['cart']));
    $cart_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cart_products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $total_amount += $product['price'] * $quantity;
    }
}
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Keranjang Belanja</h2>
    </div>

    <div class="card-body">
        <?php if ($cart_products): ?>
            <form method="POST" action="">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_products as $product): ?>
                                <?php
                                $quantity = $_SESSION['cart'][$product['id']];
                                $subtotal = $product['price'] * $quantity;
                                ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="../<?php echo UPLOAD_PATH . $product['image']; ?>" alt="<?php echo $product['name']; ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                                    <i class="fas fa-image" style="color: #ccc;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight: bold;"><?php echo $product['name']; ?></div>
                                                <div style="color: #666; font-size: 0.875rem;">
                                                    Size: <?php echo $product['size']; ?> | Color: <?php echo $product['color']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo formatPrice($product['price']); ?></td>
                                    <td>
                                        <input type="number" name="quantities[<?php echo $product['id']; ?>]" 
                                               class="form-control" value="<?php echo $quantity; ?>" 
                                               min="1" max="<?php echo $product['stock']; ?>" style="width: 80px;">
                                    </td>
                                    <td><?php echo formatPrice($subtotal); ?></td>
                                    <td>
                                        <button type="submit" name="remove_item" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                                <td style="font-weight: bold; font-size: 1.2rem;"><?php echo formatPrice($total_amount); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="row" style="margin-top: 2rem;">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="form-label">Alamat Pengiriman</label>
                            <textarea name="shipping_address" class="form-control" rows="3" required><?php echo $_SESSION['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Metode Pembayaran</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="transfer">Transfer Bank</option>
                                <option value="cod">Cash on Delivery (COD)</option>
                                <option value="e-wallet">E-Wallet</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-grid gap-2">
                            <button type="submit" name="update_cart" class="btn btn-primary">Update Keranjang</button>
                            <button type="submit" name="checkout" class="btn btn-success">Checkout</button>
                            <a href="catalog.php" class="btn">Lanjutkan Belanja</a>
                        </div>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem;">
                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>Keranjang belanja kosong</h3>
                <p>Silakan tambahkan produk ke keranjang belanja Anda</p>
                <a href="catalog.php" class="btn btn-primary">Mulai Belanja</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>