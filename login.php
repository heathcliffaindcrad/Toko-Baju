<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$page_title = "Login";
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        
        if ($user['role'] === 'admin') {
            redirect('admin/dashboard.php');
        } else {
            redirect('user/dashboard.php');
        }
    } else {
        $_SESSION['error'] = "Username atau password salah!";
    }
}
?>

<link rel="stylesheet" href="assets/css/style.css">
<div class="auth-container">
    <div class="auth-card">
        <h2 class="auth-title">Login</h2>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username atau Email</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        
        <p style="text-align: center; margin-top: 1rem;">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </p>
        
        <div style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: #666;">
            <strong>Admin:</strong> admin / password<br>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>