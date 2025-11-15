// FILE: assets/js/pos.js (Upgraded with Toast Notifications)

document.addEventListener('alpine:init', () => {
    Alpine.data('posApp', () => ({
        // ... (properties like searchQuery, medicines, etc., remain the same) ...
        searchQuery: '', activeCategory: '', medicines: [], loading: false, billItems: [], discount: 0, processingSale: false,
        
        init() {
            this.activeCategory = 'Painkiller & Fever';
            this.fetchMedicines();
        },
        async fetchMedicines() { /* ... (same as before) ... */ },
        selectCategory(category) { /* ... (same as before) ... */ },
        
        addToBill(medicine) {
            const existingItem = this.billItems.find(item => item.id === medicine.id);
            if (existingItem) {
                if (existingItem.qty < existingItem.stock) {
                    existingItem.qty++;
                } else {
                    // **CHANGED: Use toast instead of alert**
                    showToast('Maximum stock reached for this item.', 'info');
                }
            } else {
                this.billItems.push({ id: medicine.id, name: medicine.name, price: parseFloat(medicine.price), qty: 1, stock: parseInt(medicine.stock) });
            }
        },

        removeFromBill(index) { /* ... (same as before) ... */ },

        updateQty(id, amount) {
            const item = this.billItems.find(item => item.id === id);
            if (item) {
                const newQty = item.qty + amount;
                if (newQty > 0 && newQty <= item.stock) {
                    item.qty = newQty;
                } else if (newQty > item.stock) {
                    // **CHANGED: Use toast instead of alert**
                    showToast('Maximum stock limit reached.', 'info');
                    item.qty = item.stock;
                }
            }
        },

        validateQty(item) {
            if (item.qty > item.stock) {
                // **CHANGED: Use toast instead of alert**
                showToast('Quantity cannot exceed available stock.', 'error');
                item.qty = item.stock;
            }
            if (item.qty < 1 || isNaN(item.qty)) {
                item.qty = 1;
            }
        },
        
        get subtotal() { /* ... (same as before) ... */ },
        get total() { /* ... (same as before) ... */ },

        async completeSale() {
            if (this.billItems.length === 0) {
                showToast('Bill is empty. Please add items first.', 'error');
                return;
            }
            // **REMOVED: confirm() dialog for a smoother flow**
            
            this.processingSale = true;
            try {
                const saleData = { items: this.billItems.map(item => ({ id: item.id, qty: item.qty })), discount: this.discount };
                
                const response = await fetch('pos_process.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(saleData) });
                const result = await response.json();
                
                if (result.success) {
                    // **CHANGED: Use toast instead of alert**
                    showToast(`Sale #${result.order_id} completed successfully!`, 'success');
                    this.billItems = [];
                    this.discount = 0;
                    this.fetchMedicines(); // Refresh stock counts
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Sale completion error:', error);
                showToast('A network error occurred. Please try again.', 'error');
            } finally {
                this.processingSale = false;
            }
        }
    }));
});