<?php
$pageTitle = 'News Management';
require_once '../includes/header.php';
require_once '../classes/News.php';
requireRole('admin');

$newsClass = new News();

// Handle news operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    if (isset($_POST['add_news'])) {
        $data = [
            'title' => $_POST['title'],
            'content' => $_POST['content'],
            'published_by' => $_SESSION['user_id'],
            'status' => $_POST['status']
        ];
        
        $result = $newsClass->createNews($data, $_FILES['image'] ?? null);
        
        if ($result['success']) {
            logAudit($_SESSION['user_id'], 'news_created', "News #{$result['id']} created");
            setFlash('success', 'News added successfully');
        } else {
            setFlash('error', $result['message']);
        }
        redirect('/admin/news.php');
    }
    
    if (isset($_POST['delete_news'])) {
        $newsId = $_POST['news_id'];
        $newsClass->deleteNews($newsId);
        logAudit($_SESSION['user_id'], 'news_deleted', "News #$newsId deleted");
        setFlash('success', 'News deleted');
        redirect('/admin/news.php');
    }
}

// Get all news
$allNews = $newsClass->getAllNews(null); // Get all status
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold mb-4">Health News Management</h2>
    <button onclick="showAddModal()" class="px-6 py-3 bg-green-600 text-white font-bold">
        + ADD NEWS
    </button>
</div>

<div class="bg-white border-2 border-gray-300 p-6">
    <?php foreach ($allNews as $news): ?>
    <div class="border-b-2 pb-4 mb-4 last:border-b-0">
        <div class="flex gap-4 mb-2">
            <?php if ($news['image']): ?>
            <img src="<?php echo $newsClass->getImageUrl($news['image']); ?>" 
                 alt="<?php echo clean($news['title']); ?>"
                 class="w-32 h-32 object-cover border">
            <?php endif; ?>
            
            <div class="flex-1">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="text-xl font-bold"><?php echo clean($news['title']); ?></h3>
                        <div class="text-sm text-gray-600">
                            By <?php echo clean($news['author_name']); ?> on <?php echo date('M d, Y', strtotime($news['created_at'])); ?>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <span class="px-2 py-1 text-xs <?php echo $news['status'] === 'published' ? 'bg-green-100' : 'bg-gray-100'; ?>">
                            <?php echo strtoupper($news['status']); ?>
                        </span>
                        <form method="POST" onsubmit="return confirm('Delete this news?')">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="delete_news" value="1">
                            <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                            <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs">DELETE</button>
                        </form>
                    </div>
                </div>
                <p class="text-gray-700"><?php echo nl2br(clean(substr($news['content'], 0, 200))); ?>...</p>
                <a href="<?php echo SITE_URL; ?>/news-detail.php?id=<?php echo $news['id']; ?>" 
                   class="text-blue-600 text-sm mt-2 inline-block">Read Full Article →</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add News Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-2xl w-full m-4 max-h-screen overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">Add Health News</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="add_news" value="1">
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">News Image</label>
                <input type="file" name="image" accept="image/*" class="w-full p-2 border-2 border-gray-400">
                <div class="text-sm text-gray-600 mt-1">Optional. JPG, PNG, GIF, WEBP (Max 5MB)</div>
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Title *</label>
                <input type="text" name="title" required class="w-full p-2 border-2 border-gray-400">
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Content *</label>
                <textarea name="content" required rows="8" class="w-full p-2 border-2 border-gray-400"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Status *</label>
                <select name="status" required class="w-full p-2 border-2 border-gray-400">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="flex-1 p-3 bg-green-600 text-white font-bold">PUBLISH</button>
                <button type="button" onclick="closeAddModal()" class="flex-1 p-3 bg-gray-400 text-white font-bold">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?>