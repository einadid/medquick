<div class="container mx-auto p-4 md:p-6">
    <h1 class="text-3xl font-bold mb-6">Salesman Dashboard</h1>

    <div class="mb-8">
        <a href="pos.php" class="block w-full md:w-3/4 lg:w-1/2 mx-auto bg-blue-600 text-white text-center p-8 rounded-lg shadow-lg hover:bg-blue-700 transition-colors transform hover:scale-105">
            <i class="fas fa-cash-register text-5xl mb-4"></i>
            <h2 class="text-4xl font-bold">Start New Sale</h2>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <p class="text-gray-500">Shop's Sales Today</p>
            <p class="text-3xl font-bold">৳<span class="counter" data-target="<?= (int)($stats['today_sales'] ?? 0); ?>">0</span></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <p class="text-gray-500">Shop's Transactions Today</p>
            <p class="text-3xl font-bold counter" data-target="<?= (int)($stats['sales_count'] ?? 0); ?>">0</p>
        </div>
    </div>
</div>