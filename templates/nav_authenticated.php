<?php
// FILE: templates/nav_authenticated.php (Final, Cleaned, No DB Query)
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>

<div x-data="{ searchOpen: false }" @open-search.window="searchOpen = true">

<!-- ================================
     DESKTOP NAVBAR (FIXED TOP)
================================ -->
<header class="hidden md:block bg-white/80 backdrop-blur-lg shadow-sm fixed top-0 left-0 right-0 z-40">
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
                    <input type="text" 
                           id="desktop-search" 
                           @keyup="search($event)" 
                           placeholder="Search for medicines..." 
                           class="w-full p-2.5 pl-10 border border-gray-300 rounded-full bg-gray-50 focus:bg-white focus:ring-2 focus:ring-teal-500">

                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>

                    <div id="desktop-search-suggestions"
                         class="absolute top-full left-0 right-0 bg-white border mt-1 rounded-md shadow-lg z-50 hidden">
                    </div>
                </div>
            </div>

            <!-- Right Actions -->
            <div class="flex-1 flex items-center justify-end space-x-4">

                <?php if (has_role(ROLE_CUSTOMER)): ?>
                <a href="cart.php"
                   class="relative text-gray-500 hover:text-gray-900 p-2"
                   title="My Cart">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span id="cart-count"
                          class="absolute top-0 right-0 -mt-1 -mr-1 text-xs bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
                </a>
                <?php endif; ?>

                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="flex items-center space-x-2 p-1 rounded-full hover:bg-gray-100 transition">
                        <img src="<?= e($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>"
                             class="w-9 h-9 rounded-full object-cover border" alt="Avatar">
                        <span class="font-medium text-gray-600 hidden sm:inline">
                            Hi, <?= e(explode(' ', $_SESSION['user_name'])[0]) ?>
                        </span>
                        <i class="fas fa-chevron-down text-xs text-gray-500 hidden sm:inline"
                           :class="{ 'rotate-180': open }"></i>
                    </button>

                    <div x-show="open"
                         @click.away="open = false"
                         x-transition 
                         class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                         style="display:none;">
                        <div class="py-1">

                            <!-- User Info -->
                            <div class="px-4 py-3 border-b">
                                <p class="text-sm font-medium text-gray-900 truncate"><?= e($_SESSION['user_name']) ?></p>
                                <p class="text-sm text-gray-500"><?= e(ucfirst(str_replace('_',' ',$_SESSION['role']))) ?></p>
                            </div>

                            <a href="dashboard.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Dashboard</a>

                            <?php if (has_role(ROLE_ADMIN)): ?>
                            <a href="audit_log.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Audit Log</a>
                            <?php endif; ?>

                            <a href="profile.php" class="block px-4 py-2 text-sm hover:bg-gray-100">My Profile</a>

                            <div class="border-t"></div>

                            <a href="logout.php"
                               class="block px-4 py-2 text-sm text-red-700 hover:bg-red-50">Logout</a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</header>



<!-- ================================
     MOBILE TOP BAR
================================ -->
<div class="md:hidden bg-white shadow-sm fixed top-0 left-0 right-0 z-40 px-4 h-[70px] flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2">
        <i class="fas fa-pills text-2xl text-teal-600"></i>
        <span class="font-bold text-lg">QuickMed</span>
    </a>

    <button @click="searchOpen = true"
            class="text-gray-600 p-2">
        <i class="fas fa-search text-xl"></i>
    </button>
</div>



<!-- ================================
     MOBILE BOTTOM NAV
================================ -->
<?php if (has_role(ROLE_CUSTOMER)): ?>
<div class="md:hidden fixed bottom-0 left-0 right-0 h-[70px] bg-white border-t shadow-lg z-40">
    <div class="grid grid-cols-4 h-full">

        <a href="index.php"
           class="flex flex-col items-center justify-center pt-2 <?= $current_page==='index.php'?'text-teal-600':'text-gray-500' ?>">
            <i class="fas fa-home text-xl"></i>
            <span class="text-xs mt-1">Home</span>
        </a>

        <a href="catalog.php"
           class="flex flex-col items-center justify-center pt-2 <?= $current_page==='catalog.php'?'text-teal-600':'text-gray-500' ?>">
            <i class="fas fa-th-large text-xl"></i>
            <span class="text-xs mt-1">Catalog</span>
        </a>

        <a href="cart.php"
           class="flex flex-col items-center justify-center pt-2 relative <?= $current_page==='cart.php'?'text-teal-600':'text-gray-500' ?>">
            <i class="fas fa-shopping-cart text-xl"></i>
            <span id="cart-count-mobile"
                  class="absolute top-2 right-4 text-xs bg-red-600 text-white rounded-full w-5 h-5 hidden">0</span>
            <span class="text-xs mt-1">Cart</span>
        </a>

        <a href="dashboard.php"
           class="flex flex-col items-center justify-center pt-2 <?= in_array($current_page,['dashboard.php','profile.php','orders.php'])?'text-teal-600':'text-gray-500' ?>">
            <i class="fas fa-user-circle text-xl"></i>
            <span class="text-xs mt-1">Account</span>
        </a>

    </div>
</div>
<?php endif; ?>



<!-- ================================
     MOBILE FULLSCREEN SEARCH MODAL
================================ -->
<div x-show="searchOpen"
     class="fixed inset-0 z-50 bg-white p-4 flex flex-col"
     x-transition.opacity 
     style="display:none;">

    <div class="flex items-center gap-4 mb-4">
        <input type="text"
               id="mobile-search"
               @keyup="search($event)"
               placeholder="Search for any medicine..."
               class="flex-grow p-3 border-2 border-gray-300 rounded-lg focus:ring-teal-500"
               x-ref="mobileSearch">

        <button @click="searchOpen = false"
                class="text-gray-500 font-semibold">Cancel</button>
    </div>

    <div id="mobile-search-suggestions"
         class="flex-grow overflow-y-auto -mx-4"></div>
</div>



<!-- ================================
     UNIVERSAL SEARCH SCRIPT
================================ -->
<script>
if (typeof search !== "function") {
    async function search(event) {
        const query = event.target.value.trim();
        const isMobile = event.target.id === "mobile-search";
        const box = document.getElementById(isMobile ? "mobile-search-suggestions" : "desktop-search-suggestions");

        if (!box) return;

        if (query.length < 2) {
            box.innerHTML = "";
            if (!isMobile) box.classList.add("hidden");
            return;
        }

        try {
            const res = await fetch(`search.php?q=${encodeURIComponent(query)}`);
            const data = await res.json();

            if (data.length > 0) {
                box.innerHTML = data.map(s => `
                    <a href="medicine_details.php?id=${s.id}" 
                       class="flex items-center gap-4 p-3 hover:bg-gray-100 border-b">
                        <img src="${s.image_path || 'assets/images/default_med.png'}"
                             class="w-10 h-10">
                        <div>
                            <p class="font-semibold">${s.name}</p>
                            <p class="text-sm text-gray-500">by ${s.manufacturer}</p>
                        </div>
                    </a>
                `).join("");

                if (!isMobile) box.classList.remove("hidden");

            } else {
                box.innerHTML = `<div class="p-4 text-center text-gray-500">No results found.</div>`;
                if (!isMobile) box.classList.remove("hidden");
            }

        } catch (e) {
            console.error("Search error:", e);
        }
    }
}

document.addEventListener("click", (e) => {
    const container = document.getElementById("desktop-search")?.closest(".relative");
    const box = document.getElementById("desktop-search-suggestions");
    if (container && !container.contains(e.target)) {
        if (box) box.classList.add("hidden");
    }
});
</script>

</div>
