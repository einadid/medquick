// Realtime order tracking with WebSocket
class OrderTracker {
    constructor(orderId) {
        this.orderId = orderId;
        this.socket = null;
        this.connect();
    }
    
    connect() {
        // Use wss:// for production
        this.socket = new WebSocket('ws://localhost:8080');
        
        this.socket.onopen = () => {
            console.log('WebSocket connection established');
            // Subscribe to order updates
            this.socket.send(JSON.stringify({
                action: 'subscribe',
                orderId: this.orderId
            }));
        };
        
        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleUpdate(data);
        };
        
        this.socket.onclose = () => {
            console.log('WebSocket connection closed');
            // Try to reconnect after 5 seconds
            setTimeout(() => this.connect(), 5000);
        };
        
        this.socket.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
    }
    
    handleUpdate(data) {
        if (data.type === 'status_update') {
            this.updateStatusUI(data.status, data.timestamp);
        } else if (data.type === 'location_update') {
            this.updateLocationUI(data.lat, data.lng, data.address);
        }
    }
    
    updateStatusUI(status, timestamp) {
        const statusElement = document.getElementById('currentStatus');
        if (statusElement) {
            statusElement.textContent = this.getStatusText(status);
            statusElement.className = `px-2 py-1 text-xs rounded-full ${this.getStatusColor(status)}`;
        }
        
        // Add to status history
        const historyElement = document.getElementById('statusHistory');
        if (historyElement) {
            const time = new Date(timestamp).toLocaleTimeString();
            const li = document.createElement('li');
            li.className = 'mb-2';
            li.innerHTML = `
                <span class="font-semibold">${this.getStatusText(status)}</span>
                <span class="text-gray-500 text-sm ml-2">${time}</span>
            `;
            historyElement.prepend(li);
        }
    }
    
    updateLocationUI(lat, lng, address) {
        // Update map if available
        if (window.orderMap) {
            orderMap.setLocation(lat, lng);
        }
        
        // Update address text
        const addressElement = document.getElementById('deliveryLocation');
        if (addressElement) {
            addressElement.textContent = address || 'Location updated';
        }
    }
    
    getStatusText(status) {
        const statusMap = {
            'pending': 'অপেক্ষমান',
            'confirmed': 'কনফার্মড',
            'processing': 'প্রসেসিং',
            'shipped': 'শিপড',
            'out_for_delivery': 'ডেলিভারির জন্য বের হয়েছে',
            'delivered': 'ডেলিভার্ড',
            'cancelled': 'বাতিল'
        };
        return statusMap[status] || status;
    }
    
    getStatusColor(status) {
        const colorMap = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'confirmed': 'bg-blue-100 text-blue-800',
            'processing': 'bg-indigo-100 text-indigo-800',
            'shipped': 'bg-purple-100 text-purple-800',
            'out_for_delivery': 'bg-orange-100 text-orange-800',
            'delivered': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800'
        };
        return colorMap[status] || 'bg-gray-100 text-gray-800';
    }
    
    disconnect() {
        if (this.socket) {
            this.socket.close();
        }
    }
}

// Initialize tracker if on order page
document.addEventListener('DOMContentLoaded', function() {
    const orderIdElement = document.getElementById('orderId');
    if (orderIdElement) {
        const orderId = orderIdElement.dataset.orderId;
        window.orderTracker = new OrderTracker(orderId);
    }
});