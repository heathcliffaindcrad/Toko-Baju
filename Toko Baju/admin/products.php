<?php
require_once '../includes/auth.php';
$page_title = "Kelola Produk";
include '../includes/header.php';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $category_id = $_POST['category_id'];
        $size = $_POST['size'];
        $color = sanitize($_POST['color']);
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        
        // Handle image upload
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = $_FILES['image'];
            $image_name = time() . '_' . basename($image['name']);
            $target_path = '../' . UPLOAD_PATH . $image_name;
            
            if (move_uploaded_file($image['tmp_name'], $target_path)) {
                // Image uploaded successfully
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, category_id, size, color, price, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $category_id, $size, $color, $price, $stock, $image_name])) {
            $_SESSION['success'] = "Produk berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan produk!";
        }
    }
    elseif (isset($_POST['edit_product'])) {
        $id = $_POST['product_id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $category_id = $_POST['category_id'];
        $size = $_POST['size'];
        $color = sanitize($_POST['color']);
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        
        // Handle image upload
        $image_name = $_POST['current_image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = $_FILES['image'];
            $image_name = time() . '_' . basename($image['name']);
            $target_path = '../' . UPLOAD_PATH . $image_name;
            
            if (move_uploaded_file($image['tmp_name'], $target_path)) {
                // Delete old image if exists
                if (!empty($_POST['current_image'])) {
                    $old_image_path = '../' . UPLOAD_PATH . $_POST['current_image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
            }
        }
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, category_id = ?, size = ?, color = ?, price = ?, stock = ?, image = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $category_id, $size, $color, $price, $stock, $image_name, $id])) {
            $_SESSION['success'] = "Produk berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui produk!";
        }
    }
    elseif (isset($_POST['delete_product'])) {
        $id = $_POST['product_id'];
        
        // Get image name before deleting
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$id])) {
            // Delete image file
            if (!empty($product['image'])) {
                $image_path = '../' . UPLOAD_PATH . $product['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            $_SESSION['success'] = "Produk berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus produk!";
        }
    }
}

// Get all products with category names
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Kelola Produk</h2>
        <button class="btn btn-primary" onclick="showAddForm()">Tambah Produk</button>
    </div>

    <!-- Add Product Form -->
    <div id="addForm" style="display: none; margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3>Tambah Produk Baru</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nama Produk</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Kategori</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Ukuran</label>
                                        <select name="size" class="form-select" required>
                                            <option value="S">S</option>
                                            <option value="M">M</option>
                                            <option value="L">L</option>
                                            <option value="XL">XL</option>
                                            <option value="XXL">XXL</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Warna</label>
                                        <input type="text" name="color" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Harga (Rp)</label>
                                        <input type="number" name="price" class="form-control" min="0" step="1000" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Stok</label>
                                        <input type="number" name="stock" class="form-control" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Gambar Produk</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_product" class="btn btn-primary">Simpan Produk</button>
                        <button type="button" class="btn" onclick="hideAddForm()">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Ukuran</th>
                        <th>Warna</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="../<?php echo UPLOAD_PATH . $product['image']; ?>" alt="<?php echo $product['name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                        <i class="fas fa-image" style="color: #ccc;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $product['name']; ?></td>
                            <td><?php echo $product['category_name']; ?></td>
                            <td><?php echo $product['size']; ?></td>
                            <td><?php echo $product['color']; ?></td>
                            <td><?php echo formatPrice($product['price']); ?></td>
                            <td>
                                <span class="<?php echo $product['stock'] == 0 ? 'stock-out' : ($product['stock'] <= 10 ? 'stock-low' : 'stock-available'); ?>">
                                    <?php echo $product['stock']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="showEditForm(<?php echo $product['id']; ?>)">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-sm btn-danger" onclick="return confirm('Hapus produk ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; margin: 2rem auto; padding: 2rem; border-radius: 10px; max-width: 800px;">
        <div id="editFormContent">
            <!-- Edit form will be loaded here -->
        </div>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('addForm').style.display = 'block';
}

function hideAddForm() {
    document.getElementById('addForm').style.display = 'none';
}

function showEditForm(productId) {
    fetch('ajax/get_product.php?id=' + productId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('editFormContent').innerHTML = html;
            document.getElementById('editModal').style.display = 'block';
        });
}

function hideEditForm() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideEditForm();
    }
});
</script>

<?php include '../includes/footer.php'; ?>