<?php
require_once 'includes/header.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch all jobs from the database
$query = "SELECT * FROM jobs ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Learn About Different Jobs</h1>
    
    <div class="row">
        <?php foreach ($jobs as $job): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if (!empty($job['image_path'])): ?>
                        <img src="assets/images/jobs/<?php echo htmlspecialchars($job['image_path']); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($job['job_name']); ?>">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($job['job_name']); ?></h5>
                        
                        <?php if (!empty($job['audio_path'])): ?>
                            <button class="btn btn-primary play-audio" 
                                    data-audio="assets/audio/jobs/<?php echo htmlspecialchars($job['audio_path']); ?>">
                                <i class="fas fa-volume-up"></i> Listen
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const audioElements = document.querySelectorAll('.play-audio');
    let currentAudio = null;

    audioElements.forEach(button => {
        button.addEventListener('click', function() {
            const audioPath = this.getAttribute('data-audio');
            
            // Stop any currently playing audio
            if (currentAudio) {
                currentAudio.pause();
                currentAudio.currentTime = 0;
            }
            
            // Play the new audio
            currentAudio = new Audio(audioPath);
            currentAudio.play();
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 