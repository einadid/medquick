<?php
http_response_code(404);
$pageTitle = '404 - Page Not Found';
require_once 'includes/header.php';
?>

<div class="max-w-2xl mx-auto text-center py-16">
    <div class="text-9xl font-bold text-gray-300 mb-4">404</div>
    <h1 class="text-4xl font-bold mb-4">Page Not Found</h1>
    <p class="text-xl text-gray-600 mb-8">The page you're looking for doesn't exist or has been moved.</p>
    <a href="<?php echo SITE_URL; ?>" class="inline-block px-8 py-3 bg-blue-600 text-white font-bold">
        GO TO HOMEPAGE
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>