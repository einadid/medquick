<?php
// FILE: templates/nav_authenticated.php (Final Responsive & Layout-Fixed Version)
// PURPOSE: Navigation bar for logged-in users, with professional search and responsive layout.

// Ensure user image is in session for the navbar display
if (is_logged_in() && !isset($_SESSION['user_image'])) {
    try {
        $stmt = $pdo->prepare("SELECT profile_image_path FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['user_image'] = $stmt->fetchColumn();
    } catch (PDOException $e) { /* Fallback to default image */ }
}
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<div x-data="{ searchOpen: false }" @open-search.window="searchOpen = true">
    <!-- Header for Desktop and Tablet (md screens and larger) -->
    <header class="hidden md:block bg-white/80 backdrop-blur-lg shadow-sm sticky top-0 z-40">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="flex justify-between items-center py-3">
                <!-- Logo -->
                <div class="flex-1 flex justify-start items-center">
                    <a href="index.php" class="flex items-center gap-2">
                        <i class="fas fa-pills text-2xl text-teal-600"></i>
                        <span class="font-bold text-xl text-slate-800">QuickMed</span>
                    </a>
                </div>
                
                <!-- Desktop Search Bar -->
                <div class="flex-1 flex justify-center" x-data>
                    <div class="w-full max-w-lg relative">
                        <input type="text" id="desktop-search" @keyup="search($event)" placeholder="Search for medicines..." class="w-full p-2.5 pl-10 border border-gray-300 rounded-full bg-gray-50 focus:bg-white focus:ring-2 focus:ring-teal-500 transition">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                        <div id="desktop-search-suggestions" class="absolute top-full left-0 right-0 bg-white border mt-1 rounded-md shadow-lg z-50 hidden"></div>
                    </div>
                </div>

                <!-- Right-side Actions -->
                <div class="flex-1 flex items-center justify-end space-x-4">
                    <?php if(has_role(ROLE_CUSTOMER)): ?>
                        <a href="cart.php" class="relative text-gray-500 hover:text-gray-900 p-2" title="My Cart">
                            <i class="fas fa-shopping-cart text-xl"></i>
                            <span id="cart-count" class="absolute top-0 right-0 -mt-1 -mr-1 text-xs bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
                        </a>
                    <?php endif; ?>
                    
                    <!-- User Dropdown Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 p-1 rounded-full hover:bg-gray-100 transition-colors">
                            <img src="<?= e($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover border-2 border-gray-200">
                            <span class="font-medium text-gray-600 hidden sm:inline">Hi, <?= e(explode(' ', $_SESSION['user_name'])[0]) ?></span>
                            <i class="fas fa-chevron-down text-xs text-gray-500 hidden sm:inline transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50" style="display: none;">
                            <div class="py-1">
                                <div class="px-4 py-3 border-b"><p class="text-sm font-medium text-gray-900 truncate"><?= e($_SESSION['user_name']) ?></p><p class="text-sm text-gray-500"><?= e(ucfirst(str_replace('_', ' ', $_SESSION['role']))) ?></p></div>
                                <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                                <?php if (has_role(ROLE_ADMIN)): ?><a href="audit_log.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Audit Log</a><?php endif; ?>
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                                <div class="border-t border-gray-100"></div>
                                <a href="logout.php" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Minimal Top Bar for Mobile -->
    <div class="md:hidden bg-white shadow-sm sticky top-0 z-40 px-4 py-3">
        <div class="flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-2"><i class="fas fa-pills text-2xl text-teal-600"></i><span class="font-bold text-lg text-slate-800">QuickMed</span></a>
            <button @click="searchOpen = true" class="text-gray-600 hover:text-teal-600 p-2"><i class="fas fa-search text-xl"></i></button>
        </div>
    </div>

    <!-- Mobile Bottom Navigation (Customer-specific) -->
    <?php if (has_role(ROLE_CUSTOMER)): ?>
    <div class="md:hidden fixed bottom-0 left-0 right-0 h-[70px] bg-white border-t border-gray-200 shadow-[0_-2px_10px_rgba(0,0,0,0.05)] z-40">
        <div class="grid grid-cols-4 h-full">
            <a href="index.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 <?= $current_page == 'index.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-home text-xl"></i><span class="text-xs mt-1">Home</span></a>
            <a href="catalog.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 <?= $current_page == 'catalog.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-th-large text-xl"></i><span class="text-xs mt-1">Catalog</span></a>
            <a href="cart.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 relative <?= $current_page == 'cart.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-shopping-cart text-xl"></i><span id="cart-count-mobile" class="absolute top-2 right-4 text-xs bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center border-2 border-white hidden">0</span><span class="text-xs mt-1">Cart</span></a>
            <a href="dashboard.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 <?= ($current_page == 'dashboard.php' || $current_page == 'profile.php' || $current_page == 'orders.php') ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-user-circle text-xl"></i><span class="text-xs mt-1">Account</span></a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Global Full-screen Mobile Search Modal -->
    <div x-show="searchOpen" class="fixed inset-0 z-50 bg-white p-4 flex flex-col" x-transition.opacity style="display: none;">
        <div class="flex-shrink-0 flex items-center gap-4 mb-4">
            <input type="text" id="mobile-search" @keyup="search($event)" placeholder="Search for any medicine..." class="flex-grow w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500" x-ref="mobileSearch" @keydown.escape.window="searchOpen = false">
            <button @click="searchOpen = false" class="text-gray-500 text-sm font-semibold">Cancel</button>
        </div>
        <div id="mobile-search-suggestions" class="flex-grow overflow-y-auto -mx-4"></div>
    </div>
</div>

<!-- Reusable Search Logic Script -->
<script>
    if (typeof search !== 'function') {
        async function search(event) {
            const query = event.target.value.trim();
            const isMobile = event.target.id === 'mobile-search';
            const suggestionsBox = document.getElementById(isMobile ? 'mobile-search-suggestions' : 'desktop-search-suggestions');
            if (!suggestionsBox) return;
            
            if (query.length < 2) { suggestionsBox.innerHTML = ''; if (!isMobile) suggestionsBox.classList.add('hidden'); return; }
            try {
                const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
                const suggestions = await response.json();
                if (suggestions.length > 0) {
                    suggestionsBox.innerHTML = suggestions.map(s => `<a href="medicine_details.php?id=${s.id}" class="flex items-center gap-4 p-3 hover:bg-gray-100 border-b"><img src="${s.image_path || 'assets/images/default_med.png'}" class="w-10 h-10 object-contain rounded"><div><p class="font-semibold">${s.name}</p><p class="text-sm text-gray-500">by ${s.manufacturer}</p></div></a>`).join('');
                    if (!isMobile) suggestionsBox.classList.remove('hidden');
                } else {
                    suggestionsBox.innerHTML = '<div class="p-4 text-center text-gray-500">No results found.</div>';
                    if (!isMobile) suggestionsBox.classList.remove('hidden');
                }
            } catch (error) { console.error('Search error:', error); }
        }
    }

    if (typeof desktopSearchClickHandler !== 'function') {
        var desktopSearchClickHandler = (e) => {
            const desktopSearchContainer = document.getElementById('desktop-search')?.closest('.relative');
            if (desktopSearchContainer && !desktopSearchContainer.contains(e.target)) {
                const suggestionsBox = document.getElementById('desktop-search-suggestions');
                if(suggestionsBox) suggestionsBox.classList.add('hidden');
            }
        };
        document.addEventListener('click', desktopSearchClickHandler);
    }
</script>