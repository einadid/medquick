<?php
$isSalesman = (is_logged_in() && has_role(ROLE_SALESMAN));
$isShopAdmin = (is_logged_in() && has_role(ROLE_SHOP_ADMIN));
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME); ?> - QuickMed</title>
    
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --color-primary: #0D9488;
            --color-primary-hover: #0F766E;
            --font-sans: 'Inter', sans-serif;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-sans);
            background-color: #f8fafc;
            color: #1e293b;
        }
        
        body:not(.salesman-layout):not(.shop-admin-layout) { padding-bottom: 70px; }
        body.salesman-layout { padding-bottom: 70px; }
        body.shop-admin-layout { padding-bottom: 70px; }

        @media (min-width: 1024px) {
            body { padding-bottom: 0; }
            body.salesman-layout, body.shop-admin-layout { padding-left: 256px; }
        }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        
        .btn-primary { background-color: var(--color-primary); color: white; font-weight: 600; padding: 0.625rem 1.25rem; border-radius: 0.5rem; transition: background-color 0.2s, transform 0.2s; display: inline-block; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1); }
        .btn-primary:hover { background-color: var(--color-primary-hover); transform: translateY(-2px); }

        #toast-container { position: fixed; top: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: 0.75rem; width: 350px; max-width: 90vw; }
        .toast { display: flex; align-items: center; padding: 1rem 1.5rem; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); border-left-width: 4px; animation: toast-in 0.5s cubic-bezier(0.21, 1.02, 0.73, 1), toast-out 0.5s cubic-bezier(0.06, 0.71, 0.55, 1) 3.5s forwards; }
        .toast-success { background-color: #F0FDF4; border-color: #22C55E; color: #15803D; }
        .toast-error { background-color: #FEF2F2; border-color: #EF4444; color: #B91C1C; }
        @keyframes toast-in { from { transform: translateX(120%); } to { transform: translateX(0); } }
        @keyframes toast-out { from { transform: translateX(0); } to { transform: translateX(120%); } }
    </style>
</head>
<body class="antialiased <?= $isSalesman ? 'salesman-layout' : '' ?> <?= $isShopAdmin ? 'shop-admin-layout' : '' ?>">
    <div id="toast-container"></div>
    
    <div id="app" class="min-h-screen">
        
        <?php if ($isSalesman || $isShopAdmin): ?>
            <div class="lg:flex">
                <?php 
                    if ($isSalesman) include __DIR__ . '/_salesman_sidebar.php';
                    if ($isShopAdmin) include __DIR__ . '/_shop_admin_sidebar.php';
                ?>
                
                <div class="flex-grow flex flex-col w-full">
                    
                    <header class="lg:hidden bg-white shadow-sm sticky top-0 z-30 px-4 py-3">
                        <div class="flex justify-between items-center">
                            <a href="index.php" class="flex items-center gap-2"><i class="fas fa-pills text-2xl text-teal-600"></i><span class="font-bold text-lg text-slate-800">QuickMed</span></a>
                            <a href="profile.php" class="block p-1" title="My Profile">
                                <img src="<?= e($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover border-2 border-gray-200">
                            </a>
                        </div>
                    </header>
                    
                    <main class="flex-grow overflow-y-auto">
        <?php else: ?>
            <div class="flex flex-col min-h-screen">
                <?php
                if (is_logged_in()) {
                    include __DIR__ . '/nav_authenticated.php';
                } else {
                    include __DIR__ . '/nav_public.php';
                }
                ?>
                <main class="flex-grow">
        <?php endif; ?>