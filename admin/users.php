<?php
require_once '../includes/auth.php';
$page_title = "Kelola Pengguna";
include '../includes/header.php';
echo '<link rel="stylesheet" href="assets/css/style.css">';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $role = $_POST['role'];
        
        // Validasi
        if ($password !== $confirm_password) {
            $_SESSION['error'] = "Password dan konfirmasi password tidak cocok!";
        } else {
            // Cek apakah username atau email sudah ada
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "Username atau email sudah digunakan!";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user baru
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address, $role])) {
                    $_SESSION['success'] = "User berhasil ditambahkan!";
                } else {
                    $_SESSION['error'] = "Gagal menambahkan user!";
                }
            }
        }
    }
    elseif (isset($_POST['edit_user'])) {
        $id = $_POST['user_id'];
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $role = $_POST['role'];
        
        // Cek apakah username atau email sudah ada (kecuali untuk user ini)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Username atau email sudah digunakan!";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, address = ?, role = ? WHERE id = ?");
            
            if ($stmt->execute([$username, $email, $full_name, $phone, $address, $role, $id])) {
                $_SESSION['success'] = "User berhasil diperbarui!";
            } else {
                $_SESSION['error'] = "Gagal memperbarui user!";
            }
        }
    }
    elseif (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        
        // Prevent admin from deleting themselves
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Tidak dapat menghapus akun sendiri!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            
            if ($stmt->execute([$id])) {
                $_SESSION['success'] = "User berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus user!";
            }
        }
    }
    elseif (isset($_POST['reset_password'])) {
        $id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "Password dan konfirmasi password tidak cocok!";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $id])) {
                $_SESSION['success'] = "Password berhasil direset!";
            } else {
                $_SESSION['error'] = "Gagal mereset password!";
            }
        }
    }
}

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Count statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Kelola Pengguna</h2>
        <button class="btn btn-primary" onclick="showAddForm()">Tambah User</button>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_users; ?></div>
            <div class="stat-label">Total Pengguna</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_admins; ?></div>
            <div class="stat-label">Administrator</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_customers; ?></div>
            <div class="stat-label">Customer</div>
        </div>
    </div>

    <!-- Add User Form -->
    <div id="addForm" style="display: none; margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3>Tambah User Baru</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_user" class="btn btn-primary">Simpan User</button>
                        <button type="button" class="btn" onclick="hideAddForm()">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Nama Lengkap</th>
                        <th>Telepon</th>
                        <th>Role</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <?php echo $user['username']; ?>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge badge-success">Anda</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['full_name']; ?></td>
                            <td><?php echo $user['phone'] ?: '-'; ?></td>
                            <td>
                                <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-primary' : 'badge-info'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-sm btn-primary" onclick="showEditForm(<?php echo $user['id']; ?>)">Edit</button>
                                    <button class="btn btn-sm btn-warning" onclick="showResetPasswordForm(<?php echo $user['id']; ?>)">Reset Password</button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?')">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Edit User Modal -->
<div id="editModal" class="modal-backdrop" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit User</h3>
            <button type="button" class="modal-close" onclick="hideEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="editFormContent">
                <!-- Edit form will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal-backdrop" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Reset Password</h3>
            <button type="button" class="modal-close" onclick="hideResetPasswordModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="resetPasswordFormContent">
                <!-- Reset password form will be loaded here -->
            </div>
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

function showEditForm(userId) {
    fetch('ajax/get_user.php?id=' + userId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('editFormContent').innerHTML = html;
            document.getElementById('editModal').classList.add('modal-backdrop');
            document.getElementById('editModal').style.display = 'block';
        });
}

function showResetPasswordForm(userId) {
    console.log('Loading reset password form for ID:', userId);
    
    // Show loading state
    document.getElementById('resetPasswordFormContent').innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff;"></i>
            <p>Memuat form reset password...</p>
        </div>
    `;
    
    document.getElementById('resetPasswordModal').style.display = 'block';
    
    // Coba path yang paling umum
    const paths = [
        `get_reset_password_form.php?id=${userId}`,
        `./get_reset_password_form.php?id=${userId}`,
        `ajax/get_reset_password_form.php?id=${userId}`,
        `../admin/get_reset_password_form.php?id=${userId}`
    ];
    
    let currentPathIndex = 0;
    
    function tryNextPath() {
        if (currentPathIndex >= paths.length) {
            // Jika semua path gagal, tampilkan form sederhana
            document.getElementById('resetPasswordFormContent').innerHTML = `
                <form method="POST" action="" id="simpleResetForm">
                    <input type="hidden" name="user_id" value="${userId}">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Reset password untuk user ID: <strong>${userId}</strong>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password Baru *</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6" 
                               placeholder="Masukkan password baru (minimal 6 karakter)">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password Baru *</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6" 
                               placeholder="Ulangi password baru">
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Password akan direset dan user harus login dengan password baru.
                    </div>
                    
                    <div class="modal-actions">
                        <button type="submit" name="reset_password" class="btn btn-warning">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="hideResetPasswordModal()">Batal</button>
                    </div>
                </form>
            `;
            return;
        }
        
        const currentPath = paths[currentPathIndex];
        console.log('Mencoba path:', currentPath);
        
        fetch(currentPath)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                document.getElementById('resetPasswordFormContent').innerHTML = html;
                console.log('Form reset password berhasil dimuat');
            })
            .catch(error => {
                console.error('Error dengan path:', currentPath, error);
                currentPathIndex++;
                setTimeout(tryNextPath, 100);
            });
    }
    
    tryNextPath();
}

function hideResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
    document.getElementById('resetPasswordFormContent').innerHTML = '';
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('resetPasswordModal');
    if (e.target === modal) {
        hideResetPasswordModal();
    }
});

// Juga perbaiki fungsi hideEditModal jika belum ada
function hideEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close edit modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('editModal');
    if (e.target === modal) {
        hideEditModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>