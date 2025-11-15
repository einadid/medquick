// FILE: assets/js/main.js (The Ultimate Version)
// Contains all global client-side functionalities for QuickMed.

// --- GLOBAL HELPER FUNCTIONS ---
const getCart = () => JSON.parse(localStorage.getItem('quickmed_cart')) || {};
const saveCart = (cart) => localStorage.setItem('quickmed_cart', JSON.stringify(cart));

function showToast(message, type = 'info', duration = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    const toastId = 'toast-' + Date.now(); toast.id = toastId; toast.className = `toast toast-${type}`;
    let icon = type === 'success' ? '<i class="fas fa-check-circle ..."></i>' : (type === 'error' ? '<i class="fas fa-times-circle ..."></i>' : '<i class="fas fa-info-circle ..."></i>');
    toast.innerHTML = `${icon} <span class="font-medium flex-1">${message}</span>`;
    container.prepend(toast);
    toast.style.animation = `toast-in 0.5s ease-out, toast-out 0.5s ease-in ${duration / 1000}s forwards`;
    setTimeout(() => { const el = document.getElementById(toastId); if (el) el.remove(); }, duration + 500);
}

const updateCartCount = () => { /* ... (same as before) ... */ };

// --- MAIN SCRIPT ---
document.addEventListener('DOMContentLoaded', () => {
    updateCartCount();

    // --- RELIABLE "ADD TO CART" LISTENER ---
    document.body.addEventListener('click', function(event) {
        const button = event.target.closest('.add-to-cart-btn');
        if (button) {
            event.preventDefault();
            const id = button.dataset.id;
            const name = button.dataset.name;
            const price = parseFloat(button.dataset.price || '0');
            let cart = getCart();
            if (cart[id]) { cart[id].qty++; } else { cart[id] = { name: name, qty: 1, price: price }; }
            saveCart(cart);
            showToast(`'${name}' added to cart.`, 'success');
            updateCartCount();
        }
    });

    // --- LIVE SEARCH WITH DEBOUNCING ---
    const searchInput = document.getElementById('main-search');
    const suggestionsBox = document.getElementById('search-suggestions');
    let debounceTimer;

    if(searchInput && suggestionsBox) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                const query = searchInput.value.trim();
                if (query.length < 2) { suggestionsBox.innerHTML = ''; suggestionsBox.style.display = 'none'; return; }
                try {
                    const response = await fetch(`search_medicines.php?q=${encodeURIComponent(query)}`);
                    const suggestions = await response.json();
                    if (suggestions.length > 0) {
                        suggestionsBox.innerHTML = suggestions.map(s => `<a href="medicine_details.php?id=${s.id}" class="flex items-center gap-4 p-3 hover:bg-gray-100 border-b last:border-b-0"><img src="${s.image_path || 'assets/images/default_med.png'}" class="w-10 h-10 object-contain rounded"><div class="flex-grow"><p class="font-semibold">${s.name}</p><p class="text-sm text-gray-500">${s.manufacturer}</p></div></a>`).join('');
                        suggestionsBox.style.display = 'block';
                    } else {
                        suggestionsBox.innerHTML = '<div class="p-4 text-center text-gray-500">No results found.</div>';
                        suggestionsBox.style.display = 'block';
                    }
                } catch (error) { console.error('Search error:', error); }
            }, 300); // 300ms debounce delay
        });
        document.addEventListener('click', (e) => { if (!searchInput.closest('.relative').contains(e.target)) { suggestionsBox.style.display = 'none'; } });
    }

    // --- ANIMATED COUNTERS ---
    // ... (Your existing counter animation logic) ...

    // --- ENHANCED CAROUSEL NAVIGATION ---
    const carousel = document.querySelector('[data-carousel]');
    if (carousel) {
        const container = carousel.querySelector('[data-carousel-container]');
        const prevBtn = carousel.querySelector('[data-carousel-prev]');
        const nextBtn = carousel.querySelector('[data-carousel-next]');
        
        const updateButtons = () => {
            if (!container) return;
            prevBtn.disabled = container.scrollLeft <= 0;
            nextBtn.disabled = container.scrollLeft >= container.scrollWidth - container.clientWidth - 1;
        };

        nextBtn?.addEventListener('click', () => { container.scrollBy({ left: container.clientWidth, behavior: 'smooth' }); });
        prevBtn?.addEventListener('click', () => { container.scrollBy({ left: -container.clientWidth, behavior: 'smooth' }); });
        container?.addEventListener('scroll', updateButtons);
        
        // Initial check
        setTimeout(updateButtons, 100);
        window.addEventListener('resize', updateButtons);
    }
});