<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function generateTransactionCode() {
    return 'TRX' . date('Ymd') . strtoupper(uniqid());
}

function getLowStockProducts($pdo, $threshold = 10) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE stock <= ? AND stock > 0");
    $stmt->execute([$threshold]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOutOfStockProducts($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE stock = 0");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addNotification($pdo, $user_id, $title, $message, $type = 'info') {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $type]);
}

// FUNGSI BARU YANG DITAMBAHKAN:

/**
 * Get user notifications
 */
function getUserNotifications($pdo, $user_id, $limit = 10) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($pdo, $notification_id) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    return $stmt->execute([$notification_id]);
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Get sales statistics
 */
function getSalesStatistics($pdo, $period = 'today') {
    $query = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value
              FROM transactions 
              WHERE status = 'completed'";
    
    switch ($period) {
        case 'today':
            $query .= " AND DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $query .= " AND YEARWEEK(created_at) = YEARWEEK(CURDATE())";
            break;
        case 'month':
            $query .= " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            break;
        case 'year':
            $query .= " AND YEAR(created_at) = YEAR(CURDATE())";
            break;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get popular products
 */
function getPopularProducts($pdo, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT p.*, SUM(ti.quantity) as total_sold
        FROM products p
        LEFT JOIN transaction_items ti ON p.id = ti.product_id
        LEFT JOIN transactions t ON ti.transaction_id = t.id
        WHERE t.status = 'completed'
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Validate image upload
 */
function validateImage($file, $max_size = 2097152) { // 2MB default
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading file.";
        return $errors;
    }

    if ($file['size'] > $max_size) {
        $errors[] = "File size too large. Maximum size: " . ($max_size / 1024 / 1024) . "MB";
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        $errors[] = "Invalid file type. Allowed: JPG, PNG, GIF, WEBP";
    }

    return $errors;
}

/**
 * Upload image and return filename
 */
function uploadImage($file, $upload_dir = '../uploads/products/') {
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }

    return false;
}

/**
 * Delete image file
 */
function deleteImage($filename, $upload_dir = '../uploads/products/') {
    $filepath = $upload_dir . $filename;
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get transaction status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'badge-warning',
        'paid' => 'badge-info',
        'processing' => 'badge-primary',
        'ready_pickup' => 'badge-secondary',
        'shipped' => 'badge-success',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger'
    ];
    
    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Format date to Indonesian format
 */
function formatDateIndonesian($date, $include_time = false) {
    $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    
    $formatted = $day . ' ' . $month . ' ' . $year;
    
    if ($include_time) {
        $formatted .= ' ' . date('H:i', $timestamp);
    }
    
    return $formatted;
}

/**
 * Get dashboard statistics
 */
function getDashboardStatistics($pdo) {
    $stats = [];
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $stats['total_products'] = $stmt->fetchColumn();
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Total transactions
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $stats['total_transactions'] = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM transactions WHERE status = 'completed'");
    $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // Today's orders
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $stats['today_orders'] = $stmt->fetchColumn();
    
    // Pending orders
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_orders'] = $stmt->fetchColumn();
    
    return $stats;
}

/**
 * Check if product has low stock
 */
function isLowStock($stock, $threshold = 10) {
    return $stock > 0 && $stock <= $threshold;
}

/**
 * Check if product is out of stock
 */
function isOutOfStock($stock) {
    return $stock == 0;
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Log activity
 */
function logActivity($pdo, $user_id, $action, $description) {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $action, $description]);
}

/**
 * Get recent activities
 */
function getRecentActivities($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT al.*, u.username, u.full_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>