<?php
// FILE: templates/nav_public.php (Final Professional Version)
// PURPOSE: Top navigation bar for public (logged-out) users. Works with the new header layout.
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!-- This component is included by header.php for public visitors -->
<header class="bg-white/80 backdrop-blur-lg shadow-sm fixed top-0 left-0 right-0 z-30">
    <div class="container mx-auto px-4 sm:px-6">
        <!-- Desktop and Tablet View (md and larger) -->
        <div class="hidden md:flex justify-between items-center h-[68px]">
            <!-- Logo -->
            <div class="flex-1 flex justify-start">
                <a href="index.php" class="flex items-center gap-2">
                    <i class="fas fa-pills text-2xl text-teal-600"></i>
                    <span class="font-bold text-xl text-slate-800">QuickMed</span>
                </a>
            </div>
            
            <!-- Centered Navigation Links -->
            <nav class="flex-1 flex justify-center space-x-8">
                <a href="catalog.php" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors">Medicines</a>
                <a href="shops.php" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors">Pharmacies</a>
                <a href="#" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors">Contact</a>
            </nav>

            <!-- Right-side Action Buttons -->
            <div class="flex-1 flex items-center justify-end space-x-4">
                <a href="login.php" class="whitespace-nowrap text-base font-medium text-gray-500 hover:text-gray-900 transition-colors">Log in</a>
                <a href="signup.php" class="btn-primary">Sign up</a>
            </div>
        </div>
        <!-- Mobile View (smaller than md) -->
        <div class="md:hidden flex justify-between items-center h-[68px]">
            <a href="index.php" class="flex items-center gap-2">
                <i class="fas fa-pills text-2xl text-teal-600"></i>
                <span class="font-bold text-lg text-slate-800">QuickMed</span>
            </a>
            <!-- The @click event dispatches a global event that is caught by an Alpine component -->
            <button @click="$dispatch('open-search')" class="text-gray-600 hover:text-teal-600 p-2" aria-label="Search">
                <i class="fas fa-search text-xl"></i>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Bottom Navigation for public users -->
<div class="md:hidden fixed bottom-0 left-0 right-0 h-[70px] bg-white border-t border-gray-200 shadow-[0_-2px_10px_rgba(0,0,0,0.05)] z-40">
    <div class="grid grid-cols-4 h-full">
        <a href="index.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 <?= $current_page == 'index.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-home text-xl"></i><span class="text-xs mt-1">Home</span></a>
        <a href="catalog.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 <?= $current_page == 'catalog.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-th-large text-xl"></i><span class="text-xs mt-1">Catalog</span></a>
        <a href="cart.php" class="flex flex-col items-center justify-center pt-2 relative hover:bg-gray-50 <?= $current_page == 'cart.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-shopping-cart text-xl"></i><span id="cart-count-mobile" class="absolute top-2 right-4 text-xs bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center border-2 border-white hidden">0</span><span class="text-xs mt-1">Cart</span></a>
        <a href="login.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 <?= ($current_page == 'login.php' || $current_page == 'signup.php') ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-user-circle text-xl"></i><span class="text-xs mt-1">Account</span></a>
    </div>
</div>

<!-- 
    The Global Search Modal & its logic should ideally be in footer.php to avoid repetition,
    but including it here makes this component self-contained for clarity.
-->
<div x-data="{ searchOpen: false }" @open-search.window="searchOpen = true" class="contents">
    <div x-show="searchOpen" class="fixed inset-0 z-50 bg-white p-4 flex flex-col" x-transition.opacity style="display: none;">
        <div class="flex-shrink-0 flex items-center gap-4 mb-4">
            <input type="text" id="mobile-search" @keyup="search($event)" placeholder="Search for any medicine..." class="flex-grow w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500" x-ref="mobileSearch" @keydown.escape.window="searchOpen = false">
            <button @click="searchOpen = false" class="text-gray-500 text-sm font-semibold">Cancel</button>
        </div>
        <div id="mobile-search-suggestions" class="flex-grow overflow-y-auto -mx-4"></div>
    </div>
</div>

<script>
if (typeof search !== 'function') {
    var searchDebounce;
    async function search(event) {
        const query = event.target.value.trim();
        const suggestionsBox = document.getElementById(event.target.id === 'mobile-search' ? 'mobile-search-suggestions' : 'desktop-search-suggestions');
        if (!suggestionsBox) return;

        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(async () => {
            if (query.length < 2) { suggestionsBox.innerHTML = ''; suggestionsBox.classList.add('hidden'); return; }
            try {
                const response = await fetch(`search_medicines.php?q=${encodeURIComponent(query)}`);
                const suggestions = await response.json();
                if (suggestions.length > 0) {
                    suggestionsBox.innerHTML = suggestions.map(s => `<a href="medicine_details.php?id=${s.id}" class="flex items-center gap-4 p-3 hover:bg-gray-100 border-b"><img src="${s.image_path||'assets/images/default_med.png'}" class="w-10 h-10 object-contain rounded"><div><p class="font-semibold">${s.name}</p><p class="text-sm text-gray-500">${s.manufacturer}</p></div></a>`).join('');
                } else {
                    suggestionsBox.innerHTML = '<div class="p-4 text-center text-gray-500">No results found.</div>';
                }
                suggestionsBox.classList.remove('hidden');
            } catch (error) { console.error('Search error:', error); }
        }, 300);
    }
}
</script>