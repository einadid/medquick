document.addEventListener('DOMContentLoaded', () => {
    // --- LAZY LOADING IMAGES ---
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    const lazyImageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src || img.src; // Use data-src if available
                img.onload = () => img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });
    lazyImages.forEach(img => lazyImageObserver.observe(img));

    // --- AJAX SEARCH ---
    const searchInput = document.getElementById('main-search');
    const suggestionsBox = document.getElementById('search-suggestions');

    if (searchInput) {
        searchInput.addEventListener('keyup', async (e) => {
            const query = e.target.value.trim();
            if (query.length < 2) {
                suggestionsBox.innerHTML = '';
                suggestionsBox.classList.add('hidden');
                return;
            }

            try {
                const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
                const suggestions = await response.json();
                
                if (suggestions.length > 0) {
                    suggestionsBox.innerHTML = suggestions.map(s => 
                        `<a href="medicine.php?id=${s.id}" class="block p-2 hover:bg-gray-100">
                            ${s.name} <span class="text-sm text-gray-500">by ${s.manufacturer}</span>
                        </a>`
                    ).join('');
                    suggestionsBox.classList.remove('hidden');
                } else {
                    suggestionsBox.innerHTML = '<div class="p-2 text-gray-500">No results found.</div>';
                    suggestionsBox.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
                suggestionsBox.classList.add('hidden');
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target)) {
                suggestionsBox.classList.add('hidden');
            }
        });
    }

    // --- CLIENT-SIDE CART (localStorage) ---
    const getCart = () => JSON.parse(localStorage.getItem('quickmed_cart')) || {};
    const saveCart = (cart) => localStorage.setItem('quickmed_cart', JSON.stringify(cart));
    
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const id = e.target.dataset.id;
            const name = e.target.dataset.name;
            const price = parseFloat(e.target.dataset.price || '10.00'); 

            let cart = getCart();
            if (cart[id]) {
                cart[id].qty++;
            } else {
                cart[id] = { name: name, qty: 1, price: price };
            }
            saveCart(cart);
            
            // Visual feedback
            e.target.textContent = 'Added!';
            setTimeout(() => { e.target.textContent = 'Add to Cart'; }, 1000);
            updateCartCount();
        });
    });

    const updateCartCount = () => {
        const cart = getCart();
        const count = Object.values(cart).reduce((sum, item) => sum + item.qty, 0);
        const cartCountEl = document.getElementById('cart-count');
        if (cartCountEl) {
            cartCountEl.textContent = count;
            cartCountEl.classList.toggle('hidden', count === 0);
        }
    };
    
    updateCartCount();

    // --- ANIMATED COUNTERS ---
    const counters = document.querySelectorAll('.counter');
    const speed = 200; // The lower the slower

    const animateCounter = (counter) => {
        const target = +counter.dataset.target;
        const inc = target / speed;

        let count = 0;

        const updateCount = () => {
            count += inc;
            if (count < target) {
                counter.innerText = Math.ceil(count);
                setTimeout(updateCount, 1);
            } else {
                counter.innerText = target;
            }
        };
        updateCount();
    };

    const counterObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });

});
