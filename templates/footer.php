<?php
// FILE: templates/header.php (The Ultimate Layout Controller)
// This file sets up the entire page structure, including different layouts for different user roles.

$is_logged_in = is_logged_in();
$role = $_SESSION['role'] ?? null;

// Determine if the user has a special sidebar-based layout.
$is_special_layout = $is_logged_in && in_array($role, [ROLE_ADMIN, ROLE_SHOP_ADMIN, ROLE_SALESMAN]);

// Determine if the user has a mobile bottom navigation bar.
$has_bottom_nav = $is_logged_in && in_array($role, [ROLE_CUSTOMER, ROLE_SALESMAN, ROLE_SHOP_ADMIN]);
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
            --color-primary: #0D9488; /* Teal-600 */
            --font-sans: 'Inter', sans-serif;
            --navbar-height: 68px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-sans);
            background-color: #f8fafc; /* Slate-50 */
            color: #1e293b; /* Slate-800 */
            /* Add padding-top to body for the fixed navbar */
            padding-top: var(--navbar-height);
        }
        
        /* Add bottom padding ONLY for roles with a mobile bottom navbar */
        @media (max-width: 1023px) { /* Apply on mobile/tablet */
            body.has-bottom-nav {
                padding-bottom: 70px; /* Height of the bottom navbar */
            }
        }
        
        /* For roles with a sidebar on desktop */
        @media (min-width: 1024px) { /* lg screens and up */
            body.has-sidebar {
                padding-left: 256px; /* Width of the sidebar (w-64) */
            }
        }
        
        /* Custom scrollbar, animations, buttons, etc. */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .btn-primary { background-color: var(--color-primary); /* ... */ }
        #toast-container { /* ... */ }
    </style>
</head>
<body class="antialiased <?= $is_special_layout ? 'has-sidebar' : '' ?> <?= $has_bottom_nav ? 'has-bottom-nav' : '' ?>">
    
    <div id="toast-container"></div>
    
    <!-- Alpine.js state for mobile sidebar -->
    <div id="app" class="min-h-screen" x-data="{ sidebarOpen: false }">
        
        <?php if ($is_special_layout): ?>
            <!-- --- Layout for Admin, Shop Admin, Salesman --- -->
            <?php 
                // Load the correct sidebar based on role
                if ($role === ROLE_ADMIN) include __DIR__ . '/_admin_sidebar.php';
                if ($role === ROLE_SHOP_ADMIN) include __DIR__ . '/_shop_admin_sidebar.php';
                if ($role === ROLE_SALESMAN) include __DIR__ . '/_salesman_sidebar.php';
            ?>
            
            <!-- This wrapper contains the main content area -->
            <div class="flex flex-col w-full lg:ml-64">
                
                <!-- Mobile Top Bar with Hamburger Menu to open the sidebar -->
                <header class="lg:hidden bg-white shadow-sm fixed top-0 left-0 right-0 z-30 px-4 h-[68px] flex items-center justify-between">
                    <a href="index.php" class="flex items-center gap-2">
                        <i class="fas fa-pills text-2xl text-teal-600"></i>
                        <span class="font-bold text-lg text-slate-800">QuickMed</span>
                    </a>
                    <button @click="sidebarOpen = true" class="text-gray-600 p-2" aria-label="Open menu">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </header>
                
                <!-- Main scrollable content area -->
                <main class="flex-grow w-full">
        
        <?php else: ?>
            <!-- --- Default Layout for Customer & Public --- -->
            <div class="flex flex-col min-h-screen">
                <?php 
                // Load the appropriate top navigation bar
                if ($is_logged_in) {
                    include __DIR__ . '/nav_authenticated.php';
                } else {
                    include __DIR__ . '/nav_public.php';
                }
                ?>
                <main class="flex-grow">
        <?php endif; ?>
        
        <!-- =================================================================== -->
        <!-- == Page content from other files will be inserted here == -->
        <!-- =================================================================== -->