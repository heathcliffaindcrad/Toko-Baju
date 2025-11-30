<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    die('Akses ditolak!');
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $category_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        ?>
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f8f9fa;">
            <h3 style="margin: 0;">Edit Kategori</h3>
            <button type="button" class="btn" onclick="hideEditForm()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form method="POST" action="categories.php">
                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                
                <div class="form-group">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                </div>
                
                <div class="form-group" style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" name="edit_category" class="btn btn-primary">Update Kategori</button>
                    <button type="button" class="btn" onclick="hideEditForm()">Batal</button>
                </div>
            </form>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Kategori tidak ditemukan!</div>';
    }
} else {
    echo '<div class="alert alert-danger">ID kategori tidak valid!</div>';
}
?>