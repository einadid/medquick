// QuickMed JavaScript Functions

// Auto-hide flash messages
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function(msg) {
        setTimeout(function() {
            msg.style.opacity = '0';
            setTimeout(function() {
                msg.remove();
            }, 300);
        }, 5000);
    });
});

// Medicine search autocomplete
function initMedicineSearch() {
    const searchInput = document.getElementById('medicineSearch');
    if (!searchInput) return;
    
    let debounceTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value;
        
        if (query.length < 2) return;
        
        debounceTimer = setTimeout(function() {
            fetch('/quickmed/ajax/search.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data);
                });
        }, 300);
    });
}

function displaySearchResults(results) {
    const resultsDiv = document.getElementById('searchResults');
    if (!resultsDiv) return;
    
    if (results.length === 0) {
        resultsDiv.innerHTML = '<div class="p-4 text-gray-600">No results found</div>';
        return;
    }
    
    let html = '';
    results.forEach(function(item) {
        html += `
            <a href="/quickmed/customer/medicine-detail.php?id=${item.id}" 
               class="block p-3 border-b hover:bg-gray-100">
                <div class="font-bold">${item.name}</div>
                <div class="text-sm text-gray-600">${item.generic_name}</div>
                <div class="text-sm text-green-600">From ${item.min_price} BDT</div>
            </a>
        `;
    });
    
    resultsDiv.innerHTML = html;
}

// Add to cart with AJAX
document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.textContent;
        
        button.disabled = true;
        button.textContent = 'ADDING...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.textContent = '✓ ADDED';
                button.classList.remove('bg-blue-600');
                button.classList.add('bg-green-600');
                
                setTimeout(function() {
                    button.textContent = originalText;
                    button.classList.remove('bg-green-600');
                    button.classList.add('bg-blue-600');
                    button.disabled = false;
                }, 2000);
            } else {
                alert(data.message);
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.textContent = originalText;
            button.disabled = false;
        });
    });
});

// Confirm delete actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this?');
}

// Print function
function printReceipt() {
    window.print();
}

// Number formatting
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initMedicineSearch();
});