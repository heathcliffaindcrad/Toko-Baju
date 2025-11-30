<?php
// includes/header.php - Versi sederhana
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Toko Pakaian' : 'Toko Pakaian'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo isAdmin() ? 'admin/dashboard.php' : (isLoggedIn() ? 'user/dashboard.php' : 'index.php'); ?>">
                    <i class="fas fa-tshirt"></i> Toko Pakaian
                </a>
            </div>
            
            <div class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="dashboard.php" class="nav-link">Dashboard</a>
                        <a href="products.php" class="nav-link">Produk</a>
                        <a href="categories.php" class="nav-link">Kategori</a>
                        <a href="users.php" class="nav-link">Pengguna</a>
                        <a href="transactions.php" class="nav-link">Transaksi</a>
                        <a href="reports.php" class="nav-link">Laporan</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="nav-link">Dashboard</a>
                        <a href="catalog.php" class="nav-link">Katalog</a>
                        <a href="cart.php" class="nav-link">Keranjang</a>
                        <a href="orders.php" class="nav-link">Pesanan</a>
                    <?php endif; ?>
                    
                    <div class="nav-user">
                        <span class="user-name"><?php echo $_SESSION['full_name']; ?></span>
                        <div class="user-dropdown">
                            <a href="<?php echo isAdmin() ? '../user/profile.php' : 'profile.php'; ?>" class="dropdown-link">
                                <i class="fas fa-user"></i> Profil
                            </a>
                            <a href="../logout.php" class="dropdown-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../login.php" class="nav-link">Login</a>
                    <a href="../register.php" class="nav-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>