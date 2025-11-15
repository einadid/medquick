// FILE: assets/js/main.js (Full, Final, Clean Version)

// --- GLOBAL HELPER FUNCTIONS ---
const getCart = () => JSON.parse(localStorage.getItem('quickmed_cart')) || {};
const saveCart = (cart) => localStorage.setItem('quickmed_cart', JSON.stringify(cart));

function showToast(message, type = 'info', duration = 3500) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerText = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

const updateCartCount = () => {
    const cart = getCart();
    let count = 0;
    Object.values(cart).forEach(item => count += item.qty);
    const cartCountEl = document.getElementById('cart-count');
    if (cartCountEl) cartCountEl.innerText = count;
};


// --- MAIN LOGIC ---
document.addEventListener('DOMContentLoaded', () => {

    updateCartCount();

    // Reliable Add to Cart Listener
    document.body.addEventListener('click', function(event) {
        const button = event.target.closest('.add-to-cart-btn');
        if (button) {
            event.preventDefault();
            const id = button.dataset.id;
            const name = button.dataset.name;
            const price = parseFloat(button.dataset.price || '0');

            let cart = getCart();
            if (cart[id]) cart[id].qty++;
            else cart[id] = { name: name, qty: 1, price: price };

            saveCart(cart);
            showToast(`'${name}' added to cart.`, 'success');
            updateCartCount();
        }
    });


    // --- Lazy Loading Images ---
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    if ("IntersectionObserver" in window) {
        const lazyImageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        lazyImages.forEach(img => lazyImageObserver.observe(img));
    }


    // --- Animated Counter ---
    const counters = document.querySelectorAll('.counter');

    const animateCounter = (counter) => {
        const target = +counter.dataset.target;
        if (isNaN(target)) return;

        const duration = 1500;
        let start = null;

        const step = (timestamp) => {
            if (!start) start = timestamp;
            const progress = Math.min((timestamp - start) / duration, 1);
            let current = Math.floor(progress * target);
            counter.innerText = current.toLocaleString();
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    if ("IntersectionObserver" in window && counters.length > 0) {
        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(counter => counterObserver.observe(counter));
    }

}); // DOMContentLoaded END


// ===============================
//  ALPINE.JS ADMIN DASHBOARD
// ===============================

document.addEventListener('alpine:init', () => {
    Alpine.data('adminDashboard', () => ({

        assignShopModal: {
            open: false,
            userId: null,
            newRole: '',
            selectedShop: '',
            originalSelect: null
        },

        closeModal() {
            this.assignShopModal.open = false;
        },

        cancelRoleChange() {
            if (this.assignShopModal.originalSelect) {
                this.assignShopModal.originalSelect.value = this.assignShopModal.originalSelect.dataset.originalRole;
            }
            this.closeModal();
        },

        updateRole(userId, newRole, event) {
            this.assignShopModal.originalSelect = event.target;

            if (newRole === 'salesman' || newRole === 'shop_admin') {
                this.assignShopModal.open = true;
                this.assignShopModal.userId = userId;
                this.assignShopModal.newRole = newRole;
                this.assignShopModal.selectedShop = '';
            } else {
                this.sendRoleUpdateRequest(userId, newRole, null);
            }
        },

        async confirmShopAssignment() {
            if (!this.assignShopModal.selectedShop) {
                showToast('Please select a shop.', 'error');
                return;
            }

            await this.sendRoleUpdateRequest(
                this.assignShopModal.userId,
                this.assignShopModal.newRole,
                this.assignShopModal.selectedShop
            );

            this.closeModal();
        },

        async sendRoleUpdateRequest(userId, role, shopId = null) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const response = await fetch('user_manage_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        action: 'update_role',
                        user_id: userId,
                        role: role,
                        shop_id: shopId,
                        csrf_token: csrfToken
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');

                    const shopNameCell = document.getElementById(`shop-name-${userId}`);
                    if (shopNameCell) shopNameCell.textContent = result.new_shop_name || 'N/A';

                    this.assignShopModal.originalSelect.dataset.originalRole = role;
                } else {
                    showToast(result.message, 'error');
                    this.cancelRoleChange();
                }

            } catch (error) {
                showToast('A network error occurred.', 'error');
                this.cancelRoleChange();
            }
        }

    }));
});


// ===============================
//  REMOVED OLD LOAD MORE AJAX
// ===============================
// Logic now handled inside catalog.php
// createMedicineCard() also removed (server-rendered now)
