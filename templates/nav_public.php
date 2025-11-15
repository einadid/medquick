<?php
// FILE: templates/nav_public.php (Final Version with 'Pharmacies' link)
// PURPOSE: Navigation bar for visitors who are not logged in.

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!-- This component is now self-contained and works with the new header/footer structure. -->
<div x-data="{ searchOpen: false }" @open-search.window="searchOpen = true">

    <!-- Header for Desktop and Tablet (md screens and larger) -->
    <header class="hidden md:block bg-white/80 backdrop-blur-lg shadow-sm sticky top-0 z-40">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="flex justify-between items-center py-4">
                
                <!-- Logo -->
                <div class="flex-1 flex justify-start items-center">
                    <a href="index.php" class="flex items-center gap-2">
                        <i class="fas fa-pills text-2xl text-teal-600"></i>
                        <span class="font-bold text-xl text-slate-800">QuickMed</span>
                    </a>
                </div>
                
                <!-- Centered Navigation Links -->
                <nav class="flex-1 flex justify-center space-x-8">
                    <a href="catalog.php" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors">Medicines</a>
                    <!-- **NEW: Pharmacies Link** -->
                    <a href="shops.php" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors">Pharmacies</a>
                    <a href="#" class="text-base font-medium text-gray-500 hover:text-gray-900 transition-colors">Contact</a>
                </nav>

                <!-- Right-side Action Buttons -->
                <div class="flex-1 flex items-center justify-end space-x-4">
                    <a href="login.php" class="whitespace-nowrap text-base font-medium text-gray-500 hover:text-gray-900 transition-colors">
                        Log in
                    </a>
                    <a href="signup.php" class="btn-primary">
                        Sign up
                    </a>
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

    <!-- Mobile Bottom Navigation for public users -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 h-[70px] bg-white border-t border-gray-200 shadow-[0_-2px_10px_rgba(0,0,0,0.05)] z-40">
        <div class="grid grid-cols-4 h-full">
            <a href="index.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 <?= $current_page == 'index.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-home text-xl"></i><span class="text-xs mt-1">Home</span></a>
            <a href="catalog.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 <?= $current_page == 'catalog.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-th-large text-xl"></i><span class="text-xs mt-1">Catalog</span></a>
            <a href="cart.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 relative <?= $current_page == 'cart.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-shopping-cart text-xl"></i><span id="cart-count-mobile" class="absolute top-2 right-4 text-xs bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center border-2 border-white hidden">0</span><span class="text-xs mt-1">Cart</span></a>
            <a href="login.php" class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 <?= ($current_page == 'login.php' || $current_page == 'signup.php') ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-user-circle text-xl"></i><span class="text-xs mt-1">Account</span></a>
        </div>
    </div>

    <!-- Global Full-screen Mobile Search Modal -->
    <div x-show="searchOpen" class="fixed inset-0 z-50 bg-white p-4 flex flex-col" x-transition.opacity style="display: none;">
        <div class="flex-shrink-0 flex items-center gap-4 mb-4"><input type="text" id="mobile-search" @keyup="search($event)" placeholder="Search for any medicine..." class="flex-grow w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500" x-ref="mobileSearch"><button @click="searchOpen = false" class="text-gray-500 text-sm font-semibold">Cancel</button></div>
        <div id="mobile-search-suggestions" class="flex-grow overflow-y-auto -mx-4"></div>
    </div>
</div>

<!-- Reusable Search Logic Script -->
<script>
if (typeof search !== 'function') {
    async function search(event) {
        const query = event.target.value.trim();
        const suggestionsBox = document.getElementById('mobile-search-suggestions');
        if (!suggestionsBox) return;
        if (query.length < 2) { suggestionsBox.innerHTML = ''; return; }
        try {
            const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
            const suggestions = await response.json();
            if (suggestions.length > 0) {
                suggestionsBox.innerHTML = suggestions.map(s => `<a href="medicine_details.php?id=${s.id}" class="flex items-center gap-4 p-3 hover:bg-gray-100 border-b"><img src="${s.image_path || 'assets/images/default_med.png'}" class="w-10 h-10 object-contain rounded"><div><p class="font-semibold">${s.name}</p><p class="text-sm text-gray-500">by ${s.manufacturer}</p></div></a>`).join('');
            } else {
                suggestionsBox.innerHTML = '<div class="p-4 text-center text-gray-500">No results found.</div>';
            }
        } catch (error) { console.error('Search error:', error); }
    }
}
</script>