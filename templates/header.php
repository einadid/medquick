<?php
// FILE: templates/header.php (The Ultimate, Final, and Working Version)
// This file sets up the entire page structure with a unified header.

// Assume these functions and constants are defined elsewhere (e.g., config.php, functions.php)
// function is_logged_in(): bool { /* ... */ }
// function e(string $text): string { /* ... */ return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
// define('APP_NAME', 'YourApp');
// define('ROLE_ADMIN', 'admin');
// define('ROLE_SHOP_ADMIN', 'shop_admin');
// define('ROLE_SALESMAN', 'salesman');
// define('ROLE_CUSTOMER', 'customer');

$is_logged_in = is_logged_in();
$role = $_SESSION['role'] ?? null;

// Determine if the user has a special sidebar-based layout (Admin, Shop Admin, Salesman).
// These sidebars are overlays and don't affect the main content's padding.
$is_special_layout = $is_logged_in && in_array($role, [ROLE_ADMIN, ROLE_SHOP_ADMIN, ROLE_SALESMAN]);

// Determine if the user has a mobile bottom navigation bar (Customer, Salesman, Shop Admin).
$has_bottom_nav = $is_logged_in && in_array($role, [ROLE_CUSTOMER, ROLE_SALESMAN, ROLE_SHOP_ADMIN]);

// Define common CSS class for primary buttons
$btn_primary_class = 'px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME); ?> - QuickMed</title>
    
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    
    <!-- External Libraries -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    
    <!-- Global Styles and Theming -->
    <style>
        /* Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        /* Custom CSS Variables */
        :root {
            --color-primary: #0D9488;
            --font-sans: 'Inter', sans-serif;
            --navbar-height: 68px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-sans);
            background-color: #f8fafc;
            color: #1e293b;
            padding-top: var(--navbar-height); /* Space for the fixed top navbar */
        }
        
        /* Add bottom padding ONLY for roles that have a mobile bottom navbar */
        @media (max-width: 1023px) { /* Mobile/Tablet */
            body.has-bottom-nav {
                padding-bottom: 70px; /* Adjust if bottom nav height changes */
            }
        }
        
        /* No padding-left needed anymore on body or main content as sidebar is an overlay */
        
        /* Basic styles for the button classes defined in PHP */
        .btn-primary {
            @apply <?= $btn_primary_class ?>; /* Apply Tailwind classes directly */
        }

        /* Alpine.js x-cloak directive to hide elements before Alpine initializes */
        [x-cloak] { display: none !important; }

        /* Other global styles like custom scrollbar, animations, toast notifications, etc. */
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        /* Add any other specific global styles here */

        /* Styles for the cart count badge */
        #cart-count {
            @apply absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full;
        }
    </style>
</head>
<body class="antialiased <?= $has_bottom_nav ? 'has-bottom-nav' : '' ?>">
    
    <!-- Global Toast Notification Container -->
    <div id="toast-container" class="fixed top-6 right-6 z-[9999] w-full max-w-sm space-y-3"></div>
    
    <!-- Main Alpine.js Application Wrapper -->
    <div id="app" class="min-h-screen" x-data="{ sidebarOpen: false, searchOpen: false }">
        
        <?php
        // Include sidebars if a special layout user is logged in.
        // These sidebars are designed as overlays and don't push the main content.
        if ($is_special_layout) {
            if ($role === ROLE_ADMIN) include __DIR__ . '/_admin_sidebar.php';
            if ($role === ROLE_SHOP_ADMIN) include __DIR__ . '/_shop_admin_sidebar.php';
            if ($role === ROLE_SALESMAN) include __DIR__ . '/_salesman_sidebar.php';
        }
        ?>
        
        <!-- **NEW: A single, unified header for EVERYONE** -->
        <header class="bg-white/80 backdrop-blur-lg shadow-sm fixed top-0 left-0 right-0 z-30">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="flex justify-between items-center h-[var(--navbar-height)]">
                    <!-- Left Section: Hamburger (if needed) & Logo -->
                    <div class="flex items-center gap-2">
                        <?php if ($is_special_layout): ?>
                            <!-- Hamburger menu to open sidebar, only for roles with a sidebar -->
                            <button @click="sidebarOpen = true" class="text-gray-600 p-2 -ml-2 lg:hidden" aria-label="Open menu">
                                <i class="fas fa-bars text-xl"></i>
                            </button>
                            <!-- On larger screens, the sidebar is assumed to be always visible or triggered differently,
                                 so the hamburger is hidden. If the sidebar is always an overlay, then it should be visible on all screens. -->
                            <!-- Re-adding hamburger for desktop sidebar if it's ALWAYS an overlay -->
                             <button @click="sidebarOpen = true" class="text-gray-600 p-2 -ml-2 hidden lg:block" aria-label="Open menu">
                                <i class="fas fa-bars text-xl"></i>
                            </button>
                        <?php endif; ?>
                        <a href="index.php" class="flex items-center gap-2">
                            <i class="fas fa-pills text-2xl text-teal-600"></i>
                            <span class="font-bold text-xl text-slate-800">QuickMed</span>
                        </a>
                    </div>
                    
                    <!-- Center: Search Bar (Desktop) - Visible on large screens -->
                    <div class="hidden lg:flex flex-1 justify-center px-8">
                        <div class="relative w-full max-w-md">
                            <input type="text" placeholder="Search medicines, shops, etc." 
                                   class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-500 bg-gray-50 text-gray-800 text-sm">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- Right Section: Actions (Search, Cart, User Dropdown / Auth Links) -->
                    <div class="flex items-center justify-end gap-2 sm:gap-4">
                        <!-- Mobile Search Button (hidden on large screens) -->
                        <button @click="searchOpen = true" class="lg:hidden text-gray-600 p-2" aria-label="Open search">
                            <i class="fas fa-search text-xl"></i>
                        </button>

                        <?php if ($is_logged_in): ?>
                             <?php if ($role === ROLE_CUSTOMER): ?>
                                <!-- Customer-specific: Shopping Cart -->
                                <a href="cart.php" class="relative text-gray-500 p-2 hover:text-gray-900 transition-colors" aria-label="Shopping Cart">
                                    <i class="fas fa-shopping-cart text-xl"></i>
                                    <!-- Cart count badge -->
                                    <span id="cart-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">0</span>
                                </a>
                            <?php endif; ?>

                            <!-- User Profile Dropdown for all logged-in users -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" 
                                        class="flex items-center p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" 
                                        aria-label="User menu"
                                        aria-haspopup="true" 
                                        :aria-expanded="open.toString()">
                                    <img src="<?= e($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>" alt="User Avatar" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                </button>
                                <div x-show="open" 
                                     @click.away="open = false" 
                                     x-transition:enter="transition ease-out duration-100" 
                                     x-transition:enter-start="transform opacity-0 scale-95" 
                                     x-transition:enter-end="transform opacity-100 scale-100" 
                                     x-transition:leave="transition ease-in duration-75" 
                                     x-transition:leave-start="transform opacity-100 scale-100" 
                                     x-transition:leave-end="transform opacity-0 scale-95" 
                                     class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50" 
                                     x-cloak>
                                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button">
                                        <div class="px-4 py-2 text-sm text-gray-700 border-b border-gray-100">
                                            <p class="font-semibold truncate"><?= e($_SESSION['user_name'] ?? 'Guest') ?></p>
                                            <p class="text-xs text-gray-500"><?= e($_SESSION['user_email'] ?? 'N/A') ?></p>
                                        </div>
                                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Profile</a>
                                        <?php if ($role === ROLE_ADMIN): ?>
                                            <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Admin Dashboard</a>
                                        <?php elseif ($role === ROLE_SHOP_ADMIN): ?>
                                            <a href="shop_dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Shop Dashboard</a>
                                        <?php elseif ($role === ROLE_SALESMAN): ?>
                                            <a href="sales_dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Sales Dashboard</a>
                                        <?php endif; ?>
                                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem">Sign out</a>
                                    </div>
                                </div>
                            </div>
                        <?php else: // Public user actions: Login & Signup buttons ?>
                            <a href="login.php" class="text-base font-medium text-gray-600 hover:text-gray-900 px-3 py-2 transition-colors">Log in</a>
                            <a href="signup.php" class="<?= $btn_primary_class ?>">Sign up</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main content area -->
        <!-- Since sidebars are overlays, main content can span full width. -->
        <!-- The padding-top on the body handles space for the fixed header. -->
        <main class="flex-grow w-full"> 
            <!-- Page content from other files will be inserted here -->