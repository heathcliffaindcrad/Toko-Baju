<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php' && basename($_SERVER['PHP_SELF']) != 'register.php') {
    redirect('login.php');
}

if (isLoggedIn() && isAdmin() && strpos($_SERVER['PHP_SELF'], '/admin/') === false) {
    redirect('admin/dashboard.php');
}

if (isLoggedIn() && !isAdmin() && strpos($_SERVER['PHP_SELF'], '/user/') === false) {
    redirect('user/dashboard.php');
}
?>