<?php
$pageTitle = 'News Detail';
require_once 'includes/header.php';
require_once 'classes/News.php';

$newsId = $_GET['id'] ?? 0;
$newsClass = new News();
$news = $newsClass->getNewsById($newsId);

if (!$news || $news['status'] !== 'published') {
    header('Location: ' . SITE_URL . '/404.php');
    exit;
}
?>

<div class="max-w-4xl mx-auto">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="<?php echo SITE_URL; ?>" class="text-blue-600">← Back to Home</a>
    </div>
    
    <!-- News Article -->
    <div class="bg-white border-2 border-gray-300 p-6">
        <!-- Title -->
        <h1 class="text-3xl font-bold mb-4"><?php echo clean($news['title']); ?></h1>
        
        <!-- Meta Info -->
        <div class="text-sm text-gray-600 mb-6 pb-4 border-b">
            <span>Published by <strong><?php echo clean($news['author_name']); ?></strong></span>
            <span class="mx-2">•</span>
            <span><?php echo date('F d, Y \a\t h:i A', strtotime($news['created_at'])); ?></span>
        </div>
        
        <!-- Featured Image -->
        <?php if ($news['image']): ?>
        <div class="mb-6">
            <img src="<?php echo $newsClass->getImageUrl($news['image']); ?>" 
                 alt="<?php echo clean($news['title']); ?>"
                 class="w-full max-h-96 object-cover border-2 border-gray-300">
        </div>
        <?php endif; ?>
        
        <!-- Content -->
        <div class="prose max-w-none">
            <div class="text-lg leading-relaxed whitespace-pre-line">
                <?php echo nl2br(clean($news['content'])); ?>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 pt-4 border-t">
            <div class="text-sm text-gray-600">
                <strong>Disclaimer:</strong> This information is for educational purposes only. 
                Always consult with a healthcare professional before making medical decisions.
            </div>
        </div>
    </div>
    
    <!-- Related News (Optional) -->
    <?php
    $relatedNews = $newsClass->getAllNews('published', 3);
    if (count($relatedNews) > 1):
    ?>
    <div class="bg-white border-2 border-gray-300 p-6 mt-4">
        <h3 class="text-xl font-bold mb-4">Other Health News</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($relatedNews as $related): ?>
                <?php if ($related['id'] != $newsId): ?>
                <a href="news-detail.php?id=<?php echo $related['id']; ?>" 
                   class="block border p-3 hover:bg-gray-50">
                    <?php if ($related['image']): ?>
                    <img src="<?php echo $newsClass->getImageUrl($related['image']); ?>" 
                         alt="<?php echo clean($related['title']); ?>"
                         class="w-full h-32 object-cover border mb-2">
                    <?php endif; ?>
                    <div class="font-bold text-sm"><?php echo clean($related['title']); ?></div>
                    <div class="text-xs text-gray-600 mt-1">
                        <?php echo date('M d, Y', strtotime($related['created_at'])); ?>
                    </div>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>