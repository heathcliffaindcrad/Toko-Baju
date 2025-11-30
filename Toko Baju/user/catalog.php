<?php
require_once '../includes/auth.php';
$page_title = "Katalog Produk";
include '../includes/header.php';

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$size_filter = $_GET['size'] ?? '';
$color_filter = $_GET['color'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.is_active = TRUE AND p.stock > 0
";

$params = [];

if ($category_filter) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($size_filter) {
    $query .= " AND p.size = ?";
    $params[] = $size_filter;
}

if ($color_filter) {
    $query .= " AND p.color LIKE ?";
    $params[] = "%$color_filter%";
}

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get unique colors
$colors = $pdo->query("SELECT DISTINCT color FROM products WHERE color IS NOT NULL ORDER BY color")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Katalog Produk</h2>
    </div>

    <!-- Filters -->
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Cari Produk</label>
                <input type="text" name="search" class="form-control" value="<?php echo $search; ?>" placeholder="Nama produk...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Kategori</label>
                <select name="category" class="form-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ukuran</label>
                <select name="size" class="form-select">
                    <option value="">Semua Ukuran</option>
                    <option value="S" <?php echo $size_filter == 'S' ? 'selected' : ''; ?>>S</option>
                    <option value="M" <?php echo $size_filter == 'M' ? 'selected' : ''; ?>>M</option>
                    <option value="L" <?php echo $size_filter == 'L' ? 'selected' : ''; ?>>L</option>
                    <option value="XL" <?php echo $size_filter == 'XL' ? 'selected' : ''; ?>>XL</option>
                    <option value="XXL" <?php echo $size_filter == 'XXL' ? 'selected' : ''; ?>>XXL</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Warna</label>
                <select name="color" class="form-select">
                    <option value="">Semua Warna</option>
                    <?php foreach ($colors as $color): ?>
                        <option value="<?php echo $color['color']; ?>" <?php echo $color_filter == $color['color'] ? 'selected' : ''; ?>>
                            <?php echo $color['color']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="catalog.php" class="btn">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Products Grid -->
    <div class="card-body">
        <?php if ($products): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php if (!empty($product['image'])): ?>
                            <img src="../<?php echo UPLOAD_PATH . $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image" style="font-size: 3rem; color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?php echo $product['name']; ?></h3>
                            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                            <div class="product-meta">
                                <span><?php echo $product['category_name']; ?></span>
                                <span>Size: <?php echo $product['size']; ?></span>
                                <span>Color: <?php echo $product['color']; ?></span>
                            </div>
                            <div class="product-stock" style="margin-bottom: 1rem;">
                                <span class="<?php echo $product['stock'] <= 10 ? 'stock-low' : 'stock-available'; ?>">
                                    Stok: <?php echo $product['stock']; ?>
                                </span>
                            </div>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary" style="width: 100%;">
                                            <i class="fas fa-shopping-cart"></i> Beli
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem;">
                <i class="fas fa-search" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>Produk tidak ditemukan</h3>
                <p>Coba ubah filter pencarian Anda</p>
                <a href="catalog.php" class="btn btn-primary">Lihat Semua Produk</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>