<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$page_title = "Register";
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
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
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address])) {
                $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
                redirect('login.php');
            } else {
                $_SESSION['error'] = "Terjadi kesalahan saat registrasi!";
            }
        }
    }
}
?>

<link rel="stylesheet" href="assets/css/style.css">
<div class="auth-container">
    <div class="auth-card">
        <h2 class="auth-title">Register</h2>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">No. Telepon</label>
                <input type="text" name="phone" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Daftar</button>
        </form>
        
        <p style="text-align: center; margin-top: 1rem;">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>