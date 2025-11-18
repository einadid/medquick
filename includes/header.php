<?php
// Auto-load dependencies if not already loaded
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../config.php';
}

if (!function_exists('getCurrentUser')) {
    require_once __DIR__ . '/functions.php';
}

// Get current user safely
try {
    $currentUser = getCurrentUser();
} catch (Exception $e) {
    error_log("Header getCurrentUser error: " . $e->getMessage());
    $currentUser = null;
}

// Get flash messages
$flash = function_exists('getFlash') ? getFlash() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - QuickMed' : 'QuickMed Pharmacy'; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="QuickMed - Multi-shop pharmacy management system. Compare prices, order medicines online, fast delivery.">
    <meta name="keywords" content="pharmacy, medicine, online pharmacy, healthcare, prescription">
    <meta name="author" content="QuickMed Pharmacy">
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💊</text></svg>">
    
    <!-- Green Theme Stylesheet -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/vintage-style.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Desktop Navigation Bar - Green Theme -->
<nav class="vintage-navbar">
    <div class="navbar-container">
        <!-- Logo -->
        <a href="<?php echo SITE_URL; ?>/index.php" class="navbar-logo">
            <span class="navbar-logo-icon">💊</span>
            <span>QuickMed</span>
        </a>
        
        <!-- Desktop Menu -->
        <ul class="navbar-menu">
            <!-- Home Link - Always visible -->
            <li>
                <a href="<?php echo SITE_URL; ?>/index.php" 
                   class="<?php echo (isset($pageTitle) && $pageTitle == 'Home') ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Home</span>
                </a>
            </li>
            
            <?php if ($currentUser): ?>
                <!-- Logged-in User Menu -->
                
                <?php if ($currentUser['role_name'] === 'customer'): ?>
                    <!-- Customer Menu -->
                    <li>
                        <a href="<?php echo SITE_URL; ?>/customer/medicines.php"
                           class="<?php echo (isset($pageTitle) && $pageTitle == 'Medicines') ? 'active' : ''; ?>">
                            <span class="nav-icon">💊</span>
                            <span class="nav-text">Medicines</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/customer/cart.php"
                           class="<?php echo (isset($pageTitle) && $pageTitle == 'Cart') ? 'active' : ''; ?>">
                            <span class="nav-icon">🛒</span>
                            <span class="nav-text">Cart</span>
                            <?php 
                            $cartCount = function_exists('getCartCount') ? getCartCount() : 0;
                            if ($cartCount > 0): 
                            ?>
                                <span class="nav-badge"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/customer/orders.php"
                           class="<?php echo (isset($pageTitle) && $pageTitle == 'Orders') ? 'active' : ''; ?>">
                            <span class="nav-icon">📦</span>
                            <span class="nav-text">Orders</span>
                        </a>
                    </li>
                
                <?php elseif ($currentUser['role_name'] === 'admin'): ?>
                    <!-- Admin Menu -->
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/index.php">
                            <span class="nav-icon">⚙️</span>
                            <span class="nav-text">Admin</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/medicines.php">
                            <span class="nav-icon">💊</span>
                            <span class="nav-text">Medicines</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/shops.php">
                            <span class="nav-icon">🏪</span>
                            <span class="nav-text">Shops</span>
                        </a>
                    </li>
                
                <?php elseif ($currentUser['role_name'] === 'shop_manager'): ?>
                    <!-- Manager Menu -->
                    <li>
                        <a href="<?php echo SITE_URL; ?>/manager/index.php">
                            <span class="nav-icon">📊</span>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/manager/orders.php">
                            <span class="nav-icon">📦</span>
                            <span class="nav-text">Orders</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/manager/stock.php">
                            <span class="nav-icon">📋</span>
                            <span class="nav-text">Stock</span>
                        </a>
                    </li>
                
                <?php elseif ($currentUser['role_name'] === 'salesman'): ?>
                    <!-- Salesman Menu -->
                    <li>
                        <a href="<?php echo SITE_URL; ?>/salesman/index.php">
                            <span class="nav-icon">🏪</span>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/salesman/pos.php">
                            <span class="nav-icon">💳</span>
                            <span class="nav-text">POS</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/salesman/orders.php">
                            <span class="nav-icon">📦</span>
                            <span class="nav-text">Orders</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Profile & Logout (Common for all logged-in users) -->
                <li>
                    <a href="<?php echo SITE_URL; ?>/profile.php"
                       class="<?php echo (isset($pageTitle) && $pageTitle == 'Profile') ? 'active' : ''; ?>">
                        <span class="nav-icon">👤</span>
                        <span class="nav-text">Profile</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/auth/logout.php">
                        <span class="nav-icon">🚪</span>
                        <span class="nav-text">Logout</span>
                    </a>
                </li>
                
            <?php else: ?>
                <!-- Guest Menu -->
                <li>
                    <a href="<?php echo SITE_URL; ?>/customer/medicines.php">
                        <span class="nav-icon">💊</span>
                        <span class="nav-text">Medicines</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/auth/login.php"
                       class="<?php echo (isset($pageTitle) && $pageTitle == 'Login') ? 'active' : ''; ?>">
                        <span class="nav-icon">🔐</span>
                        <span class="nav-text">Login</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/auth/register.php"
                       class="<?php echo (isset($pageTitle) && $pageTitle == 'Register') ? 'active' : ''; ?>">
                        <span class="nav-icon">📝</span>
                        <span class="nav-text">Register</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Mobile Bottom Navigation (Only for customers) -->
<?php if ($currentUser && $currentUser['role_name'] === 'customer'): ?>
<nav class="mobile-bottom-nav">
    <div class="mobile-nav-container">
        <a href="<?php echo SITE_URL; ?>/index.php" 
           class="mobile-nav-item <?php echo (isset($pageTitle) && $pageTitle == 'Home') ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/customer/medicines.php" 
           class="mobile-nav-item <?php echo (isset($pageTitle) && $pageTitle == 'Medicines') ? 'active' : ''; ?>">
            <span class="nav-icon">💊</span>
            <span>Medicines</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/customer/cart.php" 
           class="mobile-nav-item <?php echo (isset($pageTitle) && $pageTitle == 'Cart') ? 'active' : ''; ?>">
            <span class="nav-icon">🛒</span>
            <span>Cart</span>
            <?php 
            $cartCount = function_exists('getCartCount') ? getCartCount() : 0;
            if ($cartCount > 0): 
            ?>
                <span class="nav-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo SITE_URL; ?>/customer/orders.php" 
           class="mobile-nav-item <?php echo (isset($pageTitle) && $pageTitle == 'Orders') ? 'active' : ''; ?>">
            <span class="nav-icon">📦</span>
            <span>Orders</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/profile.php" 
           class="mobile-nav-item <?php echo (isset($pageTitle) && $pageTitle == 'Profile') ? 'active' : ''; ?>">
            <span class="nav-icon">👤</span>
            <span>Profile</span>
        </a>
    </div>
</nav>
<?php endif; ?>

<!-- Page Wrapper & Container -->
<div class="page-wrapper">
    <div class="container">
        
        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> fade-in">
                <strong><?php echo $flash['type'] === 'success' ? '✓ Success!' : '✗ Error!'; ?></strong>
                <?php echo clean($flash['message']); ?>
            </div>
        <?php endif; ?>