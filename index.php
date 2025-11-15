<?php
// FILE: index.php (Professional Homepage)
// PURPOSE: The main homepage of QuickMed, showcasing key features and data.

require_once 'src/session.php';
require_once 'config/database.php';

// Redirect logged-in users (except Admin/Customer) to their dashboard.
if (is_logged_in() && !has_role(ROLE_ADMIN) && !has_role(ROLE_CUSTOMER)) {
    redirect('dashboard.php');
}

$pageTitle = "QuickMed - Your Health, Delivered Fast";

// --- Data Fetching for the Homepage ---
try {
    // 1. Stats for the banners
    $stats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM medicines) as total_medicines,
            (SELECT COALESCE(SUM(quantity), 0) FROM inventory_batches) as total_stock,
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(DISTINCT m.id) 
             FROM medicines m 
             JOIN inventory_batches ib ON m.id = ib.medicine_id 
             WHERE ib.quantity > 0 AND ib.quantity < m.reorder_level
             ) as low_stock_count,
            (SELECT COUNT(*) 
             FROM inventory_batches 
             WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND quantity > 0
            ) as expiring_soon_count
    ")->fetch();

    // 2. Featured/Recommended Medicines
    $featured_medicines = $pdo->query("
        SELECT 
            m.id, m.name, m.manufacturer, m.image_path,
            MIN(ib.price) as price 
        FROM medicines m
        JOIN inventory_batches ib ON m.id = ib.medicine_id
        WHERE ib.quantity > 0 AND ib.expiry_date > CURDATE()
        GROUP BY m.id
        ORDER BY SUM(ib.quantity) DESC
        LIMIT 12
    ")->fetchAll();

    // 3. New: Latest Blog Posts (Dummy data for now, replace with actual blog table)
    $latest_blog_posts = [
        [
            'title' => 'Understanding Common Cold vs. Flu',
            'summary' => 'Differentiating between the common cold and influenza is crucial for proper treatment...',
            'image' => 'assets/images/blog_cold_flu.jpg',
            'link' => '#blog-cold-flu',
            'date' => '2023-10-26'
        ],
        [
            'title' => 'The Importance of Timely Medication',
            'summary' => 'Missing doses can reduce the effectiveness of your treatment. Learn why consistency matters.',
            'image' => 'assets/images/blog_medication.jpg',
            'link' => '#blog-medication-timing',
            'date' => '2023-10-20'
        ],
        [
            'title' => 'Managing Seasonal Allergies Effectively',
            'summary' => 'Tips and tricks to keep your seasonal allergies under control and enjoy the outdoors.',
            'image' => 'assets/images/blog_allergies.jpg',
            'link' => '#blog-allergies',
            'date' => '2023-10-15'
        ],
    ];

} catch (PDOException $e) {
    error_log("Homepage DB Error: " . $e->getMessage());
    $stats = ['total_medicines' => 'N/A', 'total_stock' => 'N/A', 'total_users' => 'N/A', 'low_stock_count' => 'N/A', 'expiring_soon_count' => 'N/A'];
    $featured_medicines = [];
    $latest_blog_posts = [];
}

include 'templates/header.php';
?>

<div class="fade-in">
    <!-- ================== HERO SECTION ================== -->
    <section class="relative bg-gradient-to-br from-blue-50 via-teal-50 to-white pt-16 pb-20 overflow-hidden">
        <div class="absolute inset-0 z-0">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-teal-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
            <div class="absolute top-1/2 right-1/4 w-96 h-96 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-1/4 left-1/2 w-96 h-96 bg-purple-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>
        </div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-6xl font-extrabold text-slate-800 leading-tight">
                Your Health, Delivered <span class="text-teal-600">Fast.</span>
            </h1>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-600">
                The most reliable online pharmacy and inventory system. Find your medicines, place an order, or manage your shop with ease.
            </p>
            
            <div class="mt-8 max-w-2xl mx-auto relative">
                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="main-search" class="w-full p-4 pl-14 border-2 border-gray-200 rounded-full shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors" placeholder="Search for medicines, e.g., Napa, Seclo...">
                <div id="search-suggestions" class="absolute top-full left-0 right-0 bg-white text-left mt-2 rounded-lg shadow-lg z-20 hidden border border-gray-200"></div>
            </div>
            <div class="mt-8">
                <a href="catalog.php" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-colors mr-4">
                    Explore Medicines <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <a href="#how-it-works" class="inline-flex items-center px-8 py-3 border border-gray-300 text-base font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors">
                    Learn More <i class="fas fa-info-circle ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- ============ QUICK STATS BANNERS ============= -->
    <section class="-mt-12 relative z-10">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6 text-center">
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-pills text-3xl text-blue-500 mb-2"></i>
                    <p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_medicines'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Medicines</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-boxes-stacked text-3xl text-green-500 mb-2"></i>
                    <p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_stock'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Stock Units</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-users text-3xl text-purple-500 mb-2"></i>
                    <p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_users'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Users</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-exclamation-triangle text-3xl text-orange-500 mb-2"></i>
                    <p class="text-3xl font-bold text-orange-600 counter" data-target="<?= e($stats['low_stock_count'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Low Stock</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-hourglass-end text-3xl text-red-500 mb-2"></i>
                    <p class="text-3xl font-bold text-red-600 counter" data-target="<?= e($stats['expiring_soon_count'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Expiring Soon</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ======== HOW IT WORKS SECTION (NEW) ========= -->
    <section id="how-it-works" class="py-16 sm:py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12 text-slate-800">How QuickMed Works</h2>
            <div class="grid md:grid-cols-3 gap-10 max-w-6xl mx-auto">
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-xl shadow-md border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-4 text-3xl font-bold">1</div>
                    <h3 class="font-bold text-xl text-slate-800 mb-2">Search & Find</h3>
                    <p class="text-gray-600">Easily search for any medicine using our smart search bar. Get instant results and detailed info.</p>
                </div>
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-xl shadow-md border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4 text-3xl font-bold">2</div>
                    <h3 class="font-bold text-xl text-slate-800 mb-2">Order Online</h3>
                    <p class="text-gray-600">Add medicines to your cart and complete your order with a few clicks. Secure and convenient.</p>
                </div>
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-xl shadow-md border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                    <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mb-4 text-3xl font-bold">3</div>
                    <h3 class="font-bold text-xl text-slate-800 mb-2">Fast Delivery</h3>
                    <p class="text-gray-600">Get your medications delivered right to your doorstep, quickly and safely. Your health is our priority.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ======== FEATURED MEDICINES CAROUSEL ========= -->
    <section class="py-16 sm:py-20 bg-white">
        <div class="container mx-auto px-4" data-carousel>
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-slate-800">Featured Medicines</h2>
                <div class="hidden sm:flex items-center space-x-2">
                    <button data-carousel-prev class="bg-slate-200 hover:bg-slate-300 text-slate-700 w-10 h-10 rounded-full flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed transition"><i class="fas fa-chevron-left"></i></button>
                    <button data-carousel-next class="bg-slate-200 hover:bg-slate-300 text-slate-700 w-10 h-10 rounded-full flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed transition"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>

            <div data-carousel-container class="flex overflow-x-auto scroll-smooth snap-x snap-mandatory scrollbar-hide -mx-2 pb-4">
                <?php if (empty($featured_medicines)): ?>
                    <p class="text-gray-500 px-2">No featured medicines available at the moment.</p>
                <?php else: foreach ($featured_medicines as $med): ?>
                    <div data-carousel-item class="flex-none w-3/4 sm:w-1/3 md:w-1/4 lg:w-1/6 px-2 snap-start">
                        <div class="bg-white border rounded-lg shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden group h-full flex flex-col">
                            <a href="medicine_details.php?id=<?= e($med['id']) ?>" class="block p-4 flex-grow">
                                <img src="<?= e($med['image_path'] ?? 'assets/images/default_med.png'); ?>" alt="<?= e($med['name']); ?>" class="w-full h-32 object-contain mb-4 transform group-hover:scale-105 transition-transform" loading="lazy">
                            </a>
                            <div class="p-4 border-t bg-slate-50/50">
                                <h3 class="font-semibold text-sm truncate" title="<?= e($med['name']); ?>"><?= e($med['name']); ?></h3>
                                <p class="text-xs text-gray-500 mb-3"><?= e($med['manufacturer']); ?></p>
                                <div class="flex justify-between items-center">
                                    <p class="text-lg font-bold text-teal-600">৳<?= e(number_format($med['price'], 2)) ?></p>
                                    <?php if (!is_logged_in() || has_role(ROLE_CUSTOMER)): ?>
                                    <button class="add-to-cart-btn bg-teal-100 text-teal-700 hover:bg-teal-600 hover:text-white text-xs font-bold px-3 py-1.5 rounded-full transition-colors"
                                        data-id="<?= e($med['id']); ?>"
                                        data-name="<?= e($med['name']); ?>"
                                        data-price="<?= e($med['price']); ?>">
                                        Add
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </section>

    <!-- ======== LATEST BLOG POSTS (NEW) ========= -->
    <section class="py-16 sm:py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-slate-800">Health Insights & News</h2>
                <a href="blog.php" class="text-teal-600 hover:text-teal-800 font-semibold flex items-center">
                    View All Posts <i class="fas fa-arrow-right ml-2 text-sm"></i>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (empty($latest_blog_posts)): ?>
                    <p class="text-gray-500 px-2">No blog posts available at the moment.</p>
                <?php else: foreach ($latest_blog_posts as $post): ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 transform hover:scale-105 transition-all duration-300 group">
                        <a href="<?= e($post['link']) ?>" class="block">
                            <img src="<?= e($post['image']) ?>" alt="<?= e($post['title']) ?>" class="w-full h-48 object-cover group-hover:brightness-90 transition-all duration-300" loading="lazy">
                        </a>
                        <div class="p-6">
                            <p class="text-sm text-gray-500 mb-2"><?= date('F j, Y', strtotime($post['date'])) ?></p>
                            <a href="<?= e($post['link']) ?>" class="block">
                                <h3 class="font-bold text-xl text-slate-800 mb-3 group-hover:text-teal-600 transition-colors"><?= e($post['title']) ?></h3>
                            </a>
                            <p class="text-gray-600 text-sm leading-relaxed mb-4"><?= e($post['summary']) ?></p>
                            <a href="<?= e($post['link']) ?>" class="text-teal-600 hover:text-teal-800 font-semibold text-sm flex items-center">
                                Read More <i class="fas fa-arrow-right ml-2 text-xs"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </section>

    <!-- =========== QUICK NAVIGATION CTA ============= -->
    <section class="bg-white py-16 sm:py-20 border-t">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-10 text-slate-800">How Can We Help?</h2>
            <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="catalog.php" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5">
                    <i class="fas fa-th-large text-4xl text-teal-500 group-hover:text-white mb-4 transition-colors"></i>
                    <h3 class="font-semibold text-lg">Browse Catalog</h3>
                    <p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">See all available medicines.</p>
                </a>
                <a href="<?= is_logged_in() ? 'orders.php' : 'login.php' ?>" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5">
                    <i class="fas fa-receipt text-4xl text-green-500 group-hover:text-white mb-4 transition-colors"></i>
                    <h3 class="font-semibold text-lg">My Orders</h3>
                    <p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">Track your order history.</p>
                </a>
                <a href="#" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5">
                    <i class="fas fa-map-marker-alt text-4xl text-red-500 group-hover:text-white mb-4 transition-colors"></i>
                    <h3 class="font-semibold text-lg">Shop Locator</h3>
                    <p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">Find our physical stores.</p>
                </a>
                <a href="<?= is_logged_in() ? 'dashboard.php' : 'login.php' ?>" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5">
                    <i class="fas fa-user-circle text-4xl text-purple-500 group-hover:text-white mb-4 transition-colors"></i>
                    <h3 class="font-semibold text-lg">My Account</h3>
                    <p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">Login to your dashboard.</p>
                </a>
            </div>
        </div>
    </section>
</div>
<?php
include 'templates/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ======== STATS COUNTER ANIMATION =========
    const counters = document.querySelectorAll('.counter');
    const animateCounter = (counter) => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 1500; // milliseconds
        const start = 0;
        let startTime = null;

        const easeOutQuad = (t) => t * (2 - t); // Quadratic easing out function

        const step = (currentTime) => {
            if (!startTime) startTime = currentTime;
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const easedProgress = easeOutQuad(progress);
            counter.innerText = Math.floor(easedProgress * target);

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                counter.innerText = target; // Ensure it hits the exact target
            }
        };

        requestAnimationFrame(step);
    };

    const counterObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target); // Stop observing once animated
            }
        });
    }, {
        threshold: 0.5 // Trigger when 50% of the element is visible
    });

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });

    // ======== FEATURED MEDICINES CAROUSEL =========
    const carouselContainers = document.querySelectorAll('[data-carousel-container]');

    carouselContainers.forEach(container => {
        const parentCarousel = container.closest('[data-carousel]');
        const prevButton = parentCarousel.querySelector('[data-carousel-prev]');
        const nextButton = parentCarousel.querySelector('[data-carousel-next]');

        const scrollAmount = container.children[0] ? container.children[0].offsetWidth + 16 : 0; // Item width + gap

        // Update button states initially
        const updateButtonStates = () => {
            if (container.scrollLeft <= 0) {
                prevButton.setAttribute('disabled', 'true');
            } else {
                prevButton.removeAttribute('disabled');
            }

            if (container.scrollLeft + container.clientWidth >= container.scrollWidth - 1) { // -1 for minor rounding issues
                nextButton.setAttribute('disabled', 'true');
            } else {
                nextButton.removeAttribute('disabled');
            }
        };

        prevButton.addEventListener('click', () => {
            container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        nextButton.addEventListener('click', () => {
            container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });

        container.addEventListener('scroll', updateButtonStates);
        window.addEventListener('resize', updateButtonStates); // Re-evaluate on resize

        updateButtonStates(); // Initial call
    });


    // ======== LIVE SEARCH SUGGESTIONS (AJAX) =========
    const mainSearchInput = document.getElementById('main-search');
    const searchSuggestionsDiv = document.getElementById('search-suggestions');
    let searchTimeout;

    mainSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) { // Only search if query is at least 2 characters
            searchSuggestionsDiv.innerHTML = '';
            searchSuggestionsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch('api/search_medicines.php?query=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    searchSuggestionsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(med => {
                            const suggestionItem = document.createElement('a');
                            suggestionItem.href = 'medicine_details.php?id=' + med.id;
                            suggestionItem.classList.add('flex', 'items-center', 'px-4', 'py-3', 'hover:bg-teal-50', 'transition-colors', 'border-b', 'border-gray-100', 'last:border-b-0');
                            suggestionItem.innerHTML = `
                                <img src="${med.image_path || 'assets/images/default_med.png'}" alt="${med.name}" class="w-10 h-10 object-contain mr-3 rounded">
                                <div>
                                    <p class="font-semibold text-slate-800">${med.name}</p>
                                    <p class="text-sm text-gray-600">${med.manufacturer} - ৳${parseFloat(med.price).toFixed(2)}</p>
                                </div>
                            `;
                            searchSuggestionsDiv.appendChild(suggestionItem);
                        });
                        searchSuggestionsDiv.classList.remove('hidden');
                    } else {
                        searchSuggestionsDiv.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error fetching search suggestions:', error);
                    searchSuggestionsDiv.classList.add('hidden');
                });
        }, 300); // Debounce search to prevent too many requests
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(event) {
        if (!mainSearchInput.contains(event.target) && !searchSuggestionsDiv.contains(event.target)) {
            searchSuggestionsDiv.classList.add('hidden');
        }
    });

    // Show suggestions again if input is focused and has content
    mainSearchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2 && searchSuggestionsDiv.children.length > 0) {
            searchSuggestionsDiv.classList.remove('hidden');
        }
    });

    // ======== ADD TO CART FUNCTIONALITY (Client-side for now) =========
    // This is a basic client-side example. In a real application,
    // this would send an AJAX request to a 'add_to_cart.php' endpoint.
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default button action if any

            const medId = this.dataset.id;
            const medName = this.dataset.name;
            const medPrice = this.dataset.price;

            // Simulate adding to cart - replace with actual AJAX call
            console.log(`Adding ${medName} (ID: ${medId}, Price: ${medPrice}) to cart.`);
            
            // Example: Show a temporary success message (using a simple toast/notification)
            showToast(`${medName} added to cart!`, 'success');

            // In a real scenario:
            // fetch('api/add_to_cart.php', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ medicine_id: medId, quantity: 1 }) // Assuming quantity 1 for 'add' button
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         showToast(`${medName} added to cart!`, 'success');
            //         // Optionally update cart count in header
            //     } else {
            //         showToast(`Failed to add ${medName} to cart: ${data.message}`, 'error');
            //     }
            // })
            // .catch(error => {
            //     console.error('Error adding to cart:', error);
            //     showToast('An error occurred while adding to cart.', 'error');
            // });
        });
    });

    // Simple Toast Notification Function (requires CSS)
    function showToast(message, type = 'info', duration = 3000) {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'fixed bottom-5 right-5 z-50 flex flex-col space-y-3';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `p-4 rounded-lg shadow-lg text-white font-semibold flex items-center transform translate-y-full opacity-0 transition-all duration-300 ease-out`;

        let bgColor = '';
        let icon = '';
        switch (type) {
            case 'success':
                bgColor = 'bg-green-500';
                icon = '<i class="fas fa-check-circle mr-2"></i>';
                break;
            case 'error':
                bgColor = 'bg-red-500';
                icon = '<i class="fas fa-times-circle mr-2"></i>';
                break;
            case 'warning':
                bgColor = 'bg-orange-500';
                icon = '<i class="fas fa-exclamation-triangle mr-2"></i>';
                break;
            default:
                bgColor = 'bg-blue-500';
                icon = '<i class="fas fa-info-circle mr-2"></i>';
        }

        toast.classList.add(bgColor);
        toast.innerHTML = `${icon}<span>${message}</span>`;
        toastContainer.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-y-full', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        }, 10); // Small delay to allow reflow

        // Animate out and remove
        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-full', 'opacity-0');
            toast.addEventListener('transitionend', () => toast.remove());
        }, duration);
    }
});
</script>

<style>
/* Custom CSS for scrollbar-hide and blob animation */
.scrollbar-hide {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

/* Hero section blob animation */
@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}

.animate-blob {
    animation: blob 7s infinite cubic-bezier(0.68, -0.55, 0.27, 1.55);
}
.animation-delay-2000 {
    animation-delay: 2s;
}
.animation-delay-4000 {
    animation-delay: 4s;
}

/* Ensure the toast container is styled if not already */
#toast-container {
    /* Styles are already applied in JS, but ensure any overrides */
}
</style>