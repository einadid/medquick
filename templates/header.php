<?php
// FILE: templates/header.php (Final Unified Mobile Header)
$is_logged_in = is_logged_in();
$role = $_SESSION['role'] ?? null;
$is_special_layout = $is_logged_in && in_array($role, [ROLE_ADMIN, ROLE_SHOP_ADMIN, ROLE_SALESMAN]);
$has_bottom_nav = $is_logged_in && ($role === ROLE_CUSTOMER); // Only customer has a bottom nav now
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME); ?> - QuickMed</title>
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <style>
        /* ... (All your CSS styles remain the same as before) ... */
        body { padding-top: 68px; /* For fixed navbar */ }
        @media (max-width: 767px) { body.has-bottom-nav { padding-bottom: 70px; } }
        @media (min-width: 1024px) { body.has-sidebar { padding-left: 256px; } }
    </style>
</head>
<body class="antialiased <?= $is_special_layout ? 'has-sidebar' : '' ?> <?= $has_bottom_nav ? 'has-bottom-nav' : '' ?>">
    <div id="toast-container"></div>
    <div id="app" class="min-h-screen">
        <?php if ($is_special_layout): ?>
            <div class="lg:flex">
                <?php // Load the correct sidebar for desktop
                    if ($role === ROLE_ADMIN) include __DIR__ . '/_admin_sidebar.php';
                    if ($role === ROLE_SHOP_ADMIN) include __DIR__ . '/_shop_admin_sidebar.php';
                    if ($role === ROLE_SALESMAN) include __DIR__ . '/_salesman_sidebar.php';
                ?>
                <div class="flex-grow flex flex-col w-full lg:ml-64">
                    <!-- **NEW: Unified Mobile Top Bar for all Special Roles** -->
                    <header class="lg:hidden bg-white shadow-sm fixed top-0 left-0 right-0 z-30 px-4 h-[68px] flex items-center justify-between">
                        <a href="dashboard.php" class="flex items-center gap-2"><i class="fas fa-pills text-2xl text-teal-600"></i><span class="font-bold text-lg">QuickMed</span></a>
                        <a href="profile.php" class="block p-1" title="My Profile & Logout">
                            <img src="<?= e($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>" class="w-9 h-9 rounded-full object-cover">
                        </a>
                    </header>
                    <main class="flex-grow overflow-y-auto">
        <?php else: /* Default Layout for Customer & Public */ ?>
            <div class="flex flex-col min-h-screen">
                <?php is_logged_in() ? include __DIR__ . '/nav_authenticated.php' : include __DIR__ . '/nav_public.php'; ?>
                <main class="flex-grow">
        <?php endif; ?>