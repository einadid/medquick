<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'classes/Medicine.php';
require_once 'classes/News.php';

$pageTitle = 'Home';

// Get statistics
$stats = getStats();

// Get featured medicines
$medicineClass = new Medicine();
try {
    $featuredMedicines = Database::getInstance()->fetchAll("
        SELECT m.*, c.name as category_name, MIN(sm.selling_price) as min_price
        FROM medicines m
        JOIN categories c ON m.category_id = c.id
        LEFT JOIN shop_medicines sm ON m.id = sm.medicine_id
        WHERE m.status = 'active'
        GROUP BY m.id
        ORDER BY m.created_at DESC
        LIMIT 8
    ");
} catch (Exception $e) {
    error_log("Error fetching medicines: " . $e->getMessage());
    $featuredMedicines = [];
}

// Get latest health news
$newsClass = new News();
try {
    $latestNews = $newsClass->getAllNews('published', 3);
} catch (Exception $e) {
    error_log("Error fetching news: " . $e->getMessage());
    $latestNews = [];
}

require_once 'includes/header.php';
?>

<!-- Hero/Welcome Section - Green Theme -->
<section class="hero-section fade-in">
    <div class="hero-content">
        <h1 class="hero-title">🏥 Welcome to QuickMed Pharmacy</h1>
        <p class="hero-subtitle">Your trusted multi-shop pharmacy management system</p>
        
        <!-- Search Bar -->
        <div class="search-wrapper">
            <form action="<?php echo SITE_URL; ?>/customer/medicines.php" method="GET" class="search-form">
                <input type="text" 
                       name="search" 
                       placeholder="🔍 Search for medicines, generics, categories..." 
                       class="search-input"
                       autocomplete="off">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> SEARCH MEDICINE
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Live Statistics - Green Cards -->
<section class="section">
    <div class="stats-grid fade-in">
        <div class="stat-card">
            <span class="stat-number"><?php echo number_format($stats['medicines']); ?></span>
            <span class="stat-label">💊 Total Medicines</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo number_format($stats['shops']); ?></span>
            <span class="stat-label">🏪 Active Shops</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo number_format($stats['customers']); ?></span>
            <span class="stat-label">😊 Happy Customers</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $stats['delivery_rate']; ?>%</span>
            <span class="stat-label">🚚 Delivery Success</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo number_format($stats['orders']); ?></span>
            <span class="stat-label">📦 Total Orders</span>
        </div>
    </div>
</section>

<!-- Featured Medicines - Sharp Green Cards -->
<?php if (!empty($featuredMedicines)): ?>
<section class="section fade-in">
    <h2 class="section-header">✨ Featured Medicines</h2>
    
    <div class="medicine-grid">
        <?php foreach ($featuredMedicines as $medicine): ?>
        <div class="medicine-card">
            <!-- Medicine Image -->
            <div class="medicine-image-wrapper">
                <img src="<?php echo $medicineClass->getImageUrl($medicine['image']); ?>" 
                     alt="<?php echo clean($medicine['name']); ?>"
                     loading="lazy"
                     onerror="this.src='<?php echo UPLOAD_URL; ?>medicines/default-medicine.png'">
                
                <?php if (isset($medicine['min_price']) && $medicine['min_price'] < 100): ?>
                    <div class="medicine-badge">💰 SALE</div>
                <?php endif; ?>
            </div>
            
            <!-- Medicine Content -->
            <div class="medicine-content">
                <h3 class="medicine-name"><?php echo clean($medicine['name']); ?></h3>
                
                <p class="medicine-generic">
                    <?php echo clean($medicine['generic_name']); ?>
                </p>
                
                <span class="medicine-category">
                    <?php echo clean($medicine['category_name']); ?>
                </span>
                
                <?php if (isset($medicine['min_price'])): ?>
                <div class="medicine-price">
                    <span class="medicine-price-label">From </span>
                    <?php echo formatPrice($medicine['min_price']); ?>
                </div>
                <?php endif; ?>
                
                <a href="<?php echo SITE_URL; ?>/customer/medicine-detail.php?id=<?php echo $medicine['id']; ?>" 
                   class="btn btn-primary btn-block btn-icon">
                    <i class="fas fa-eye"></i> VIEW DETAILS
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-4">
        <a href="<?php echo SITE_URL; ?>/customer/medicines.php" 
           class="btn btn-secondary btn-icon">
            <i class="fas fa-pills"></i> BROWSE ALL MEDICINES (<?php echo $stats['medicines']; ?>+)
        </a>
    </div>
</section>
<?php else: ?>
<section class="section fade-in">
    <div class="vintage-card text-center" style="padding: 3rem;">
        <h3 style="color: var(--text-medium); margin-bottom: 1rem; font-size: 1.5rem;">
            📦 No medicines available yet
        </h3>
        <p style="color: var(--text-light); font-size: 1rem;">
            Our pharmacy is being stocked. Please check back soon!
        </p>
    </div>
</section>
<?php endif; ?>

<!-- How QuickMed Works - Green Steps -->
<section class="section vintage-card fade-in">
    <h2 class="section-header">🔧 How QuickMed Works</h2>
    
    <div class="steps-grid">
        <div class="step-card">
            <span class="step-icon">🔍</span>
            <h4 class="step-title">1. SEARCH MEDICINE</h4>
            <p class="step-description">
                Find from <?php echo number_format($stats['medicines']); ?>+ quality medicines across multiple shops
            </p>
        </div>
        
        <div class="step-card">
            <span class="step-icon">🏪</span>
            <h4 class="step-title">2. COMPARE SHOPS</h4>
            <p class="step-description">
                Compare prices across <?php echo $stats['shops']; ?> verified pharmacy shops instantly
            </p>
        </div>
        
        <div class="step-card">
            <span class="step-icon">🛒</span>
            <h4 class="step-title">3. ADD TO CART</h4>
            <p class="step-description">
                Multi-shop cart support with best prices and fast checkout process
            </p>
        </div>
        
        <div class="step-card">
            <span class="step-icon">🚚</span>
            <h4 class="step-title">4. FAST DELIVERY</h4>
            <p class="step-description">
                <?php echo $stats['delivery_rate']; ?>% delivery success rate with home delivery option
            </p>
        </div>
    </div>
</section>

<!-- Latest Health News - Green Cards -->
<?php if (!empty($latestNews)): ?>
<section class="section fade-in">
    <h2 class="section-header">📰 Latest Health News & Updates</h2>
    
    <div class="news-grid">
        <?php foreach ($latestNews as $news): ?>
        <article class="news-card">
            <!-- News Image -->
            <?php if (!empty($news['image'])): ?>
            <img src="<?php echo $newsClass->getImageUrl($news['image']); ?>" 
                 alt="<?php echo clean($news['title']); ?>"
                 class="news-image"
                 loading="lazy"
                 onerror="this.src='<?php echo UPLOAD_URL; ?>news/default-news.png'">
            <?php else: ?>
            <div class="news-image" style="background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); display: flex; align-items: center; justify-content: center; font-size: 4rem; color: white;">
                📰
            </div>
            <?php endif; ?>
            
            <!-- News Content -->
            <div class="news-content">
                <h3 class="news-title"><?php echo clean($news['title']); ?></h3>
                
                <p class="news-excerpt">
                    <?php 
                    $content = strip_tags($news['content']);
                    echo substr(clean($content), 0, 150) . '...'; 
                    ?>
                </p>
                
                <div class="news-meta">
                    <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($news['created_at'])); ?>
                    <?php if (isset($news['author_name'])): ?>
                        <span style="margin-left: 1rem;">
                            <i class="fas fa-user"></i> <?php echo clean($news['author_name']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <a href="<?php echo SITE_URL; ?>/news-detail.php?id=<?php echo $news['id']; ?>" 
                   class="btn btn-primary btn-block btn-icon">
                    <i class="fas fa-book-open"></i> READ FULL ARTICLE
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Quick Actions for Customers - Green CTA -->
<?php if ($currentUser && $currentUser['role_name'] === 'customer'): ?>
<section class="section fade-in">
    <div class="vintage-card" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-dark)); color: white; text-align: center; padding: 2.5rem; border-left: 6px solid var(--accent-gold);">
        <h3 style="color: white; font-size: 1.75rem; margin-bottom: 1rem; font-weight: 800; text-transform: uppercase;">
            👋 WELCOME BACK, <?php echo strtoupper(clean($currentUser['full_name'])); ?>!
        </h3>
        <p style="color: var(--light-green); margin-bottom: 2rem; font-size: 1.125rem;">
            Ready to order your medicines? Browse our collection now!
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo SITE_URL; ?>/customer/medicines.php" class="btn btn-secondary btn-icon">
                <i class="fas fa-pills"></i> BROWSE MEDICINES
            </a>
            <a href="<?php echo SITE_URL; ?>/customer/cart.php" class="btn btn-outline btn-icon" style="border-color: white; color: white;">
                <i class="fas fa-shopping-cart"></i> VIEW CART
            </a>
            <a href="<?php echo SITE_URL; ?>/customer/orders.php" class="btn btn-outline btn-icon" style="border-color: white; color: white;">
                <i class="fas fa-box"></i> MY ORDERS
            </a>
        </div>
    </div>
</section>
<?php elseif (!$currentUser): ?>
<!-- Guest Call to Action - Green -->
<section class="section fade-in">
    <div class="vintage-card" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-dark)); color: white; text-align: center; padding: 2.5rem; border-left: 6px solid var(--accent-gold);">
        <h3 style="color: white; font-size: 1.75rem; margin-bottom: 1rem; font-weight: 800; text-transform: uppercase;">
            🎉 JOIN QUICKMED TODAY!
        </h3>
        <p style="color: var(--light-green); margin-bottom: 2rem; font-size: 1.125rem;">
            Get <strong><?php echo SIGNUP_BONUS_POINTS; ?> bonus points</strong> on registration • Compare prices • Fast delivery
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-secondary btn-icon">
                <i class="fas fa-user-plus"></i> REGISTER NOW
            </a>
            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-outline btn-icon" style="border-color: white; color: white;">
                <i class="fas fa-sign-in-alt"></i> LOGIN
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>