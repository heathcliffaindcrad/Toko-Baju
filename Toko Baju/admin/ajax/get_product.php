<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Akses ditolak!');
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];
    
    try {
        // Get product data
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Get categories for dropdown
            $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1rem;">
                <h3 style="margin: 0;">Edit Produk</h3>
                <button type="button" onclick="hideEditForm()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6c757d;">&times;</button>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="current_image" value="<?php echo $product['image']; ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Ukuran</label>
                                    <select name="size" class="form-select" required>
                                        <option value="S" <?php echo $product['size'] == 'S' ? 'selected' : ''; ?>>S</option>
                                        <option value="M" <?php echo $product['size'] == 'M' ? 'selected' : ''; ?>>M</option>
                                        <option value="L" <?php echo $product['size'] == 'L' ? 'selected' : ''; ?>>L</option>
                                        <option value="XL" <?php echo $product['size'] == 'XL' ? 'selected' : ''; ?>>XL</option>
                                        <option value="XXL" <?php echo $product['size'] == 'XXL' ? 'selected' : ''; ?>>XXL</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Warna</label>
                                    <input type="text" name="color" class="form-control" value="<?php echo htmlspecialchars($product['color']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Harga (Rp)</label>
                                    <input type="number" name="price" class="form-control" min="0" step="1000" value="<?php echo $product['price']; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Stok</label>
                                    <input type="number" name="stock" class="form-control" min="0" value="<?php echo $product['stock']; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Gambar Produk</label>
                            
                            <?php if (!empty($product['image'])): ?>
                                <div style="margin-bottom: 1rem;">
                                    <img src="../../<?php echo UPLOAD_PATH . $product['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd;">
                                    <div style="margin-top: 0.5rem;">
                                        <small class="text-muted">Gambar saat ini</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengganti gambar. Format: JPG, PNG, GIF. Maksimal 2MB</small>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" onclick="hideEditForm()">Batal</button>
                    <button type="submit" name="edit_product" class="btn btn-primary">Update Produk</button>
                </div>
            </form>
            <?php
        } else {
            echo '<div class="alert alert-danger">Produk tidak ditemukan!</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div class="alert alert-danger">ID produk tidak valid!</div>';
}
?>