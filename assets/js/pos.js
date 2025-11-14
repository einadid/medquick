document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('pos-search');
    const suggestionsBox = document.getElementById('pos-suggestions');
    const billItemsContainer = document.getElementById('bill-items');
    const billEmptyMsg = document.getElementById('bill-empty-msg');
    const subtotalEl = document.getElementById('bill-subtotal');
    const discountInput = document.getElementById('bill-discount');
    const totalEl = document.getElementById('bill-total');
    const completeSaleBtn = document.getElementById('complete-sale-btn');
    const billItemTemplate = document.getElementById('bill-item-template');

    let bill = {}; // { id: { name, price, qty, stock }, ... }

    // --- Search Logic ---
    searchInput.addEventListener('keyup', async (e) => {
        const query = e.target.value.trim();
        if (query.length < 2) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`api_pos_search.php?q=${encodeURIComponent(query)}`);
            const suggestions = await response.json();
            
            if (suggestions.length > 0) {
                suggestionsBox.innerHTML = suggestions.map(s => 
                    `<div class="p-3 hover:bg-gray-100 cursor-pointer suggestion-item" data-id="${s.id}" data-name="${s.name}" data-price="${s.price}" data-stock="${s.stock}">
                        <strong>${s.name}</strong> <span class="text-sm text-gray-500">(${s.manufacturer})</span>
                        <div class="text-xs">Stock: ${s.stock} | Price: ৳${s.price}</div>
                    </div>`
                ).join('');
                suggestionsBox.classList.remove('hidden');
            } else {
                suggestionsBox.innerHTML = '<div class="p-3 text-gray-500">No results found in your shop.</div>';
                suggestionsBox.classList.remove('hidden');
            }
        } catch (error) {
            console.error('POS Search error:', error);
        }
    });

    // --- Add item to bill from suggestion ---
    document.addEventListener('click', (e) => {
        const suggestionItem = e.target.closest('.suggestion-item');
        if (suggestionItem) {
            const id = suggestionItem.dataset.id;
            
            if (bill[id]) { // If item already in bill, just increase qty
                bill[id].qty = Math.min(bill[id].qty + 1, bill[id].stock);
            } else { // Add new item
                bill[id] = {
                    name: suggestionItem.dataset.name,
                    price: parseFloat(suggestionItem.dataset.price),
                    qty: 1,
                    stock: parseInt(suggestionItem.dataset.stock)
                };
            }
            searchInput.value = '';
            suggestionsBox.classList.add('hidden');
            renderBill();
        } else if (!searchInput.contains(e.target)) {
            suggestionsBox.classList.add('hidden');
        }
    });

    // --- Render Bill ---
    function renderBill() {
        if (Object.keys(bill).length === 0) {
            billItemsContainer.innerHTML = '';
            billItemsContainer.appendChild(billEmptyMsg);
            billEmptyMsg.classList.remove('hidden');
            completeSaleBtn.disabled = true;
        } else {
            billEmptyMsg.classList.add('hidden');
            billItemsContainer.innerHTML = '';
            for (const id in bill) {
                const item = bill[id];
                const itemRowHtml = billItemTemplate.innerHTML
                    .replace(/{id}/g, id)
                    .replace('{name}', item.name)
                    .replace(/{price}/g, item.price.toFixed(2))
                    .replace('{stock}', item.stock);
                
                const itemRow = document.createElement('div');
                itemRow.innerHTML = itemRowHtml;
                itemRow.querySelector('.item-qty').value = item.qty;
                billItemsContainer.appendChild(itemRow.firstElementChild);
            }
            completeSaleBtn.disabled = false;
        }
        addBillEventListeners();
        updateTotals();
    }

    function updateTotals() {
        let subtotal = 0;
        for (const id in bill) {
            subtotal += bill[id].price * bill[id].qty;
        }
        
        const discountPercent = parseFloat(discountInput.value) || 0;
        const discountAmount = subtotal * (discountPercent / 100);
        const total = subtotal - discountAmount;

        subtotalEl.textContent = `৳ ${subtotal.toFixed(2)}`;
        totalEl.textContent = `৳ ${total.toFixed(2)}`;
    }

    function addBillEventListeners() {
        document.querySelectorAll('.bill-item').forEach(itemRow => {
            const id = itemRow.dataset.id;

            // Quantity change
            itemRow.querySelector('.item-qty').addEventListener('change', e => {
                let newQty = parseInt(e.target.value);
                if (newQty > 0 && newQty <= bill[id].stock) {
                    bill[id].qty = newQty;
                } else {
                    e.target.value = bill[id].qty; // Revert if invalid
                }
                renderBill();
            });

            // Remove item
            itemRow.querySelector('.remove-item-btn').addEventListener('click', () => {
                delete bill[id];
                renderBill();
            });
        });
    }

    discountInput.addEventListener('input', updateTotals);

    // --- Complete Sale ---
    completeSaleBtn.addEventListener('click', async () => {
        completeSaleBtn.disabled = true;
        completeSaleBtn.textContent = 'Processing...';

        const saleData = {
            items: bill,
            discount: parseFloat(discountInput.value) || 0,
            // Add other data if needed, e.g., customerId
        };
        
        try {
            const response = await fetch('pos_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(saleData)
            });

            const result = await response.json();

            if (result.success) {
                alert(`Sale completed! Total: ৳${result.total}. Order ID: ${result.order_id}`);
                // Reset POS for new sale
                bill = {};
                discountInput.value = 0;
                renderBill();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Sale processing error:', error);
            alert('A network error occurred. Please try again.');
        } finally {
            completeSaleBtn.disabled = false;
            completeSaleBtn.textContent = 'Complete Sale';
        }
    });

});