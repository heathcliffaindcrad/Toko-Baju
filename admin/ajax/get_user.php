<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    die('Akses ditolak!');
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        ?>
        <form method="POST" action="users.php">
            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
            
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">No. Telepon</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
                <button type="button" class="btn btn-secondary" onclick="hideEditModal()">Batal</button>
            </div>
        </form>
        <?php
    } else {
        echo '<div class="alert alert-danger">User tidak ditemukan!</div>';
    }
} else {
    echo '<div class="alert alert-danger">ID user tidak valid!</div>';
}
?>