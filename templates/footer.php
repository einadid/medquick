<?php
// FILE: templates/footer.php (Final Version with Global Search Logic)
?>
        </main> <!-- .flex-grow ends -->

        <?php /* --- Role-based Footer/Nav Logic (same as before) --- */ ?>
        
    </div> <!-- #app div ends -->

    <!-- **NEW: Global Search Modal and Logic moved here for consistency** -->
    <div x-show="searchOpen" class="fixed inset-0 z-50 bg-white p-4 flex flex-col" x-transition.opacity style="display: none;">
        <div class="flex-shrink-0 flex items-center gap-4 mb-4">
            <input type="text" id="mobile-search" @input="search($event)" placeholder="Search for any medicine..." class="flex-grow w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500" x-ref="mobileSearch" @keydown.escape.window="searchOpen = false">
            <button @click="searchOpen = false" class="text-gray-500 text-sm font-semibold">Cancel</button>
        </div>
        <div id="mobile-search-suggestions" class="flex-grow overflow-y-auto -mx-4">
            <p class="text-center text-gray-400 mt-16">Start typing to see results...</p>
        </div>
    </div>
    
    <!-- =============================================== -->
    <!-- ============== GLOBAL SCRIPTS =================== -->
    <!-- =============================================== -->
    <script src="assets/js/main.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- **FINAL & ROBUST Global Search Function** -->
    <script>
    // Ensure the function is defined only once
    if (typeof window.search !== 'function') {
        let searchDebounceTimer;

        window.search = async function(event) {
            const query = event.target.value.trim();
            const isMobile = event.target.id === 'mobile-search';
            const suggestionsBoxId = isMobile ? 'mobile-search-suggestions' : 'desktop-search-suggestions';
            const suggestionsBox = document.getElementById(suggestionsBoxId);
            
            if (!suggestionsBox) {
                console.error(`Suggestion box with ID "${suggestionsBoxId}" not found.`);
                return;
            }

            clearTimeout(searchDebounceTimer);

            if (query.length < 2) {
                suggestionsBox.innerHTML = isMobile ? '<p class="text-center text-gray-400 mt-16">Type at least 2 characters...</p>' : '';
                if (!isMobile) suggestionsBox.classList.add('hidden');
                return;
            }

            // Show a simple loading state
            suggestionsBox.innerHTML = '<div class="p-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
            if (!isMobile) suggestionsBox.classList.remove('hidden');

            searchDebounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(`search_medicines.php?q=${encodeURIComponent(query)}`);
                    if (!response.ok) throw new Error('Network response was not OK');
                    
                    const result = await response.json();

                    if (result.success && result.data.length > 0) {
                        suggestionsBox.innerHTML = result.data.map(s => 
                            `<a href="medicine_details.php?id=${s.id}" class="flex items-center gap-4 p-3 hover:bg-gray-100 border-b last:border-b-0">
                                <img src="${s.image_path || 'assets/images/default_med.png'}" alt="${s.name}" class="w-10 h-10 object-contain rounded">
                                <div>
                                    <p class="font-semibold">${s.name}</p>
                                    <p class="text-sm text-gray-500">${s.manufacturer}</p>
                                </div>
                            </a>`
                        ).join('');
                    } else {
                        suggestionsBox.innerHTML = '<div class="p-4 text-center text-gray-500">No results found for your search.</div>';
                    }
                    if (!isMobile) suggestionsBox.classList.remove('hidden');
                } catch (error) {
                    console.error('Search error:', error);
                    suggestionsBox.innerHTML = '<div class="p-4 text-center text-red-500">Search failed. Please try again.</div>';
                }
            }, 300); // 300ms debounce
        }
    }

    // Click away listener for desktop search
    document.addEventListener('click', (e) => {
        const desktopSearchContainer = document.getElementById('desktop-search')?.closest('.relative');
        if (desktopSearchContainer && !desktopSearchContainer.contains(e.target)) {
            const suggestionsBox = document.getElementById('desktop-search-suggestions');
            if(suggestionsBox) suggestionsBox.classList.add('hidden');
        }
    });
    </script>
</body>
</html>