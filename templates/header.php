<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        /* Small CSS animations */
        @keyframes count-up {
            from { transform: translateY(15px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .counter-anim { animation: count-up 0.5s ease-out forwards; }
        /* Simple lazy load effect */
        img[loading="lazy"] { opacity: 0; transition: opacity 0.3s; }
        img[loading="lazy"].loaded { opacity: 1; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen">
        <?php
        // Dynamic navigation based on login status
        if (is_logged_in()) {
            include __DIR__ . '/nav_authenticated.php';
        } else {
            include __DIR__ . '/nav_public.php';
        }
        ?>
        <main class="container mx-auto p-4">