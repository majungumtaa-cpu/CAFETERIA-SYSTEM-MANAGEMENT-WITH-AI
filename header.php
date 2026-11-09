<?php
// header.php
?>
<header class="header">
    <div class="logo">
        <i class="fas fa-utensils"></i>
        <h1>Cafeteria Management System</h1>
    </div>
    <div class="user-info">
        
        <span><?php echo $_SESSION['full_name']; ?></span>
        <?php if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'employee'): ?>
        <a href="admin_profile.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-user-cog"></i> Profile
        </a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-sm btn-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>