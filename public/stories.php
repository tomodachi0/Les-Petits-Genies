<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';


try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM stories ORDER BY title");
    $stories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching stories: " . $e->getMessage());
    $stories = [];
}


$storiesPerPage = 6;
$totalStories = count($stories);
$totalPages = ceil($totalStories / $storiesPerPage);
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
$offset = ($currentPage - 1) * $storiesPerPage;
$currentStories = array_slice($stories, $offset, $storiesPerPage);
?>

<div class="page-header">
    <h1><i class="fas fa-book-reader"></i>Des histoires pour les enfants</h1>
    <p>Regarde et apprends avec notre collection d'histoires éducatives !</p>
</div>

<div class="stories-container">
    <?php if (empty($stories)): ?>
        <div class="alert alert-info">No stories available yet. Please check back later!</div>
    <?php else: ?>
        <div class="stories-grid">
            <?php
            function getYouTubeEmbedUrl($url) {
                
                if (preg_match('~^https?://(www\.)?youtube\.com/embed/([a-zA-Z0-9_-]+)~', $url, $matches)) {
                    return $url;
                }
                
                if (preg_match('~youtu\.be/([a-zA-Z0-9_-]+)~', $url, $matches)) {
                    return 'https://www.youtube.com/embed/' . $matches[1];
                }
               
                if (preg_match('~watch\?v=([a-zA-Z0-9_-]+)~', $url, $matches)) {
                    return 'https://www.youtube.com/embed/' . $matches[1];
                }
                
                return false;
            }
            ?>
            <?php foreach ($currentStories as $story): ?>
                <div class="story-card animate">
                    <div class="story-content">
                        <h2><?php echo htmlspecialchars($story['title']); ?></h2>
                        <p class="story-description"><?php echo htmlspecialchars($story['description']); ?></p>
                        <div class="video-container">
                            <?php $embedUrl = getYouTubeEmbedUrl($story['youtube_link']); ?>
                            <?php if ($embedUrl): ?>
                                <iframe
                                    src="<?php echo htmlspecialchars($embedUrl); ?>"
                                    title="<?php echo htmlspecialchars($story['title']); ?>"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    onerror="this.style.display='none'; this.parentNode.querySelector('.video-error').style.display='block';">
                                </iframe>
                                <div class="video-error" style="display:none; color:red;">
                                    Unable to load video. Please check the video link.
                                </div>
                            <?php else: ?>
                                <div class="video-error" style="color:red;">
                                    Invalid YouTube link.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>" class="btn btn-primary">&laquo; Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="btn <?php echo $i === $currentPage ? 'btn-secondary' : 'btn-primary'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>" class="btn btn-primary">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
