<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Akses ditolak!');
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            ?>
            <form method="POST" id="resetPasswordForm">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Reset password untuk: <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                    Email: <?php echo htmlspecialchars($user['email']); ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password Baru *</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6" 
                           placeholder="Masukkan password baru (minimal 6 karakter)" id="newPassword">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru *</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6" 
                           placeholder="Ulangi password baru" id="confirmPassword">
                    <div id="password-match" style="margin-top: 5px; font-size: 0.9em;"></div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Password akan direset dan user harus login dengan password baru.
                </div>
                
                <div id="form-message" style="display: none; margin-bottom: 1rem;"></div>
                
                <div class="modal-actions">
                    <button type="submit" name="reset_password" class="btn btn-warning" id="submitBtn">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                    
                    <button type="button" class="btn btn-secondary" onclick="hideResetPasswordModal()">Batal</button>
                </div>
            </form>

            <script>
            // Simple password match validation
            document.getElementById('confirmPassword').addEventListener('input', function() {
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = this.value;
                const matchIndicator = document.getElementById('password-match');
                const submitBtn = document.getElementById('submitBtn');
                
                if (confirmPassword && newPassword !== confirmPassword) {
                    matchIndicator.textContent = '❌ Password tidak cocok';
                    matchIndicator.style.color = '#dc3545';
                    submitBtn.disabled = true;
                } else if (confirmPassword && newPassword === confirmPassword) {
                    matchIndicator.textContent = '✅ Password cocok';
                    matchIndicator.style.color = '#28a745';
                    submitBtn.disabled = false;
                } else {
                    matchIndicator.textContent = '';
                    submitBtn.disabled = false;
                }
            });

            // AJAX form submission
            document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = document.getElementById('submitBtn');
                const messageDiv = document.getElementById('form-message');
                
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                submitBtn.disabled = true;
                
                fetch('users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Check if the response contains success/error indicators
                    if (html.includes('success') || html.includes('berhasil')) {
                        // Show success message
                        showMessage('Password berhasil direset!', 'success');
                        
                        // Close modal after 2 seconds
                        setTimeout(() => {
                            hideResetPasswordModal();
                            // Optionally reload the page to show updated data
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage('Gagal mereset password!', 'error');
                        submitBtn.innerHTML = '<i class="fas fa-key"></i> Reset Password';
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    showMessage('Terjadi kesalahan: ' + error, 'error');
                    submitBtn.innerHTML = '<i class="fas fa-key"></i> Reset Password';
                    submitBtn.disabled = false;
                });
            });

            function showMessage(text, type) {
                const messageDiv = document.getElementById('form-message');
                messageDiv.innerHTML = text;
                messageDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
                messageDiv.style.display = 'block';
                
                // Scroll to message
                messageDiv.scrollIntoView({ behavior: 'smooth' });
            }
            </script>
            <?php
        } else {
            echo '<div class="alert alert-danger">User tidak ditemukan!</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error database: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div class="alert alert-danger">ID user tidak valid!</div>';
}
?>