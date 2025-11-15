<?php
// FILE: templates/home_view.php (Final Professional Version)
// PURPOSE: The main view for the homepage, rendered within the layout from header.php.
?>
<!-- This content is now placed inside the <main> tag opened by header.php -->
<div class="fade-in">
    <!-- 1. Hero Section with Animated Background -->
    <section class="relative bg-slate-50 overflow-hidden">
        <!-- Animated Blobs -->
        <div class="absolute inset-0 z-0 opacity-50">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-teal-100 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
            <div class="absolute -bottom-40 -right-20 w-96 h-96 bg-purple-100 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-pink-100 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>
        
        <div class="relative container mx-auto px-4 sm:px-6 py-20 lg:py-32 text-center">
            <h1 class="text-4xl md:text-6xl font-extrabold text-slate-900 leading-tight">Your Health, Delivered <span class="text-teal-600">Fast.</span></h1>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-600">The most reliable online pharmacy in Bangladesh. Find your medicines, place an order, or manage your pharmacy with unparalleled ease.</p>
            
            <!-- Live Search Bar -->
            <div class="mt-8 max-w-2xl mx-auto relative z-10" x-data>
                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                <input type="text" id="main-search" @keyup="search($event)" placeholder="Search for Napa, Seclo, Fexo..." class="w-full p-4 pl-14 border-2 border-gray-200 rounded-full shadow-lg focus:ring-2 focus:ring-teal-500 transition">
                <div id="main-search-suggestions" class="absolute top-full w-full bg-white text-left mt-2 rounded-lg shadow-lg hidden border z-20"></div>
            </div>

            <div class="mt-8 flex justify-center gap-4">
                <a href="catalog.php" class="btn-primary px-8 py-3 text-lg">Explore Medicines</a>
                <a href="#" class="bg-white px-8 py-3 text-lg font-semibold text-gray-700 rounded-lg shadow-sm border hover:bg-gray-50 transition">Learn More</a>
            </div>
        </div>
    </section>

    <!-- 2. "How QuickMed Works" Section -->
    <section class="py-16 sm:py-20 bg-white">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="text-center mb-12"><h2 class="text-3xl font-bold text-slate-800">Simple, Fast, and Reliable</h2><p class="mt-2 text-gray-500">Get your medicines in 3 easy steps.</p></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div class="p-6"><div class="flex items-center justify-center h-16 w-16 rounded-full bg-teal-100 text-teal-600 mx-auto text-2xl"><i class="fas fa-search"></i></div><h3 class="mt-5 text-lg font-semibold">1. Search & Find</h3><p class="mt-2 text-sm text-gray-600">Easily search from thousands of medicines available in our network.</p></div>
                <div class="p-6"><div class="flex items-center justify-center h-16 w-16 rounded-full bg-teal-100 text-teal-600 mx-auto text-2xl"><i class="fas fa-shopping-cart"></i></div><h3 class="mt-5 text-lg font-semibold">2. Order Online</h3><p class="mt-2 text-sm text-gray-600">Add to your cart and place your order from the comfort of your home.</p></div>
                <div class="p-6"><div class="flex items-center justify-center h-16 w-16 rounded-full bg-teal-100 text-teal-600 mx-auto text-2xl"><i class="fas fa-shipping-fast"></i></div><h3 class="mt-5 text-lg font-semibold">3. Fast Delivery</h3><p class="mt-2 text-sm text-gray-600">Get your medicines delivered to your doorstep swiftly and safely.</p></div>
            </div>
        </div>
    </section>

    <!-- 3. Animated Stats Counters -->
    <section class="py-16 bg-slate-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
                <div class="p-6"><p class="text-5xl font-extrabold text-teal-600 counter" data-target="<?= e($stats['total_medicines'] ?? 0) ?>">0</p><p class="mt-2 text-lg font-medium text-gray-600">Available Medicines</p></div>
                <div class="p-6"><p class="text-5xl font-extrabold text-teal-600 counter" data-target="<?= e($stats['total_stock'] ?? 0) ?>">0</p><p class="mt-2 text-lg font-medium text-gray-600">Units in Stock</p></div>
                <div class="p-6"><p class="text-5xl font-extrabold text-teal-600 counter" data-target="<?= e($stats['total_users'] ?? 0) ?>">0</p><p class="mt-2 text-lg font-medium text-gray-600">Happy Users</p></div>
            </div>
        </div>
    </section>

    <!-- 4. Featured Medicines Carousel -->
    <section class="py-16 sm:py-20 bg-white">
        <div class="container mx-auto px-4" data-carousel>
            <div class="flex justify-between items-center mb-8"><h2 class="text-3xl font-bold">Featured Medicines</h2><div class="hidden sm:flex items-center space-x-2"><button data-carousel-prev class="bg-slate-200 w-10 h-10 rounded-full disabled:opacity-50"><i class="fas fa-chevron-left"></i></button><button data-carousel-next class="bg-slate-200 w-10 h-10 rounded-full disabled:opacity-50"><i class="fas fa-chevron-right"></i></button></div></div>
            <div data-carousel-container class="flex overflow-x-auto scroll-smooth snap-x snap-mandatory scrollbar-hide -mx-2 pb-4">
                <?php if (empty($featured_medicines)): ?>
                    <p class="px-2">No featured medicines to show.</p>
                <?php else: foreach ($featured_medicines as $med): ?>
                    <div data-carousel-item class="flex-none w-3/4 sm:w-1/3 md:w-1/4 lg:w-1/6 px-2 snap-start">
                        <?php include 'templates/_medicine_card.php'; // Reusing the medicine card template ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </section>

    <!-- 5. Health Insights & News Section -->
    <section class="py-16 sm:py-20 bg-slate-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12"><h2 class="text-3xl font-bold text-slate-800">Health Insights & News</h2><p class="mt-2 text-gray-500">Stay updated with the latest health tips and news from QuickMed.</p></div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($blog_posts as $post): ?>
                    <a href="#" class="group block bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                        <img src="<?= e($post['image']) ?>" alt="" class="h-48 w-full object-cover">
                        <div class="p-6"><span class="text-xs font-semibold text-teal-600 bg-teal-50 px-2 py-1 rounded-full"><?= e($post['category']) ?></span><h3 class="mt-4 text-lg font-bold group-hover:text-teal-600 transition-colors"><?= e($post['title']) ?></h3><p class="mt-4 text-sm text-gray-500">Published on <?= e($post['date']) ?></p></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<!-- This style block contains animations specific to the hero section -->
<style>
@keyframes blob { 0% { transform: translate(0px, 0px) scale(1); } 33% { transform: translate(30px, -50px) scale(1.1); } 66% { transform: translate(-20px, 20px) scale(0.9); } 100% { transform: translate(0px, 0px) scale(1); } }
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }
</style>

<!-- This script is for handling search on this page -->
<script>
if (typeof search !== 'function') {
    async function search(event) {
        const query = event.target.value.trim();
        const suggestionsBox = document.getElementById('main-search-suggestions');
        if (!suggestionsBox) return;
        
        // This is a simplified debounce implementation
        clearTimeout(window.searchDebounce);
        window.searchDebounce = setTimeout(async () => {
            if (query.length < 2) { suggestionsBox.style.display = 'none'; return; }
            try {
                const response = await fetch(`search_medicines.php?q=${encodeURIComponent(query)}`);
                const suggestions = await response.json();
                if (suggestions.length > 0) {
                    suggestionsBox.innerHTML = suggestions.map(s => `<a href="medicine_details.php?id=${s.id}" class="flex items-center gap-4 p-3 hover:bg-gray-100 border-b"><img src="${s.image_path || 'assets/images/default_med.png'}" class="w-10 h-10 object-contain rounded"><div><p class="font-semibold">${s.name}</p><p class="text-sm text-gray-500">${s.manufacturer}</p></div></a>`).join('');
                    suggestionsBox.style.display = 'block';
                } else {
                    suggestionsBox.innerHTML = '<div class="p-4 text-center text-gray-500">No results found.</div>';
                    suggestionsBox.style.display = 'block';
                }
            } catch (error) { console.error('Search error:', error); }
        }, 300);
    }
}
</script>