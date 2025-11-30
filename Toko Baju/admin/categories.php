<?php
require_once '../includes/auth.php';
$page_title = "Kelola Kategori";
include '../includes/header.php';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        if ($stmt->execute([$name, $description])) {
            $_SESSION['success'] = "Kategori berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan kategori!";
        }
    }
    elseif (isset($_POST['edit_category'])) {
        $id = $_POST['category_id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $id])) {
            $_SESSION['success'] = "Kategori berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui kategori!";
        }
    }
    elseif (isset($_POST['delete_category'])) {
        $id = $_POST['category_id'];
        
        // Check if category has products
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt_count->execute([$id]);
        $product_count = $stmt_count->fetchColumn();
        
        if ($product_count > 0) {
            $_SESSION['error'] = "Tidak dapat menghapus kategori yang masih memiliki produk!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $_SESSION['success'] = "Kategori berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus kategori!";
            }
        }
    }
}

// Get all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Kelola Kategori</h2>
        <button class="btn btn-primary" onclick="showAddForm()">Tambah Kategori</button>
    </div>

    <!-- Add Category Form -->
    <div id="addForm" style="display: none; margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3>Tambah Kategori Baru</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nama Kategori</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="add_category" class="btn btn-primary">Simpan Kategori</button>
                        <button type="button" class="btn" onclick="hideAddForm()">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="card-body">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Produk</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <?php
                        // Count products in this category - YANG BENAR
                        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                        $stmt_count->execute([$category['id']]);
                        $product_count = $stmt_count->fetchColumn();
                        ?>
                        <tr>
                            <td><?php echo $category['name']; ?></td>
                            <td><?php echo $category['description'] ?: '-'; ?></td>
                            <td><?php echo $product_count; ?> produk</td>
                            <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="showEditForm(<?php echo $category['id']; ?>)">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="delete_category" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kategori ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Belum ada kategori</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="background: white; margin: 2rem auto; padding: 2rem; border-radius: 10px; max-width: 600px;">
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

function showEditForm(categoryId) {
    // PERBAIKAN PATH: gunakan path yang relatif terhadap current directory
    fetch('ajax/get_category.php?id=' + categoryId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('editFormContent').innerHTML = html;
            document.getElementById('editModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading category data: ' + error.message);
        });
}

function hideEditForm() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideEditForm();
    }
});
</script>

<?php include '../includes/footer.php'; ?>