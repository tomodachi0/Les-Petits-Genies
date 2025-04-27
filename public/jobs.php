<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// Get all jobs from database
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM jobs");
    $jobs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching jobs: " . $e->getMessage());
    $jobs = [];
}

// Shuffle jobs to randomize
if (!empty($jobs)) {
    shuffle($jobs);
}
?>

<div class="page-header">
    <h1><i class="fas fa-briefcase"></i>Le quiz des métiers</h1>
    <p>apprend les différents métiers</p>
</div>

<div class="score-display">
    <div class="score">
        <span>Score: </span>
        <span id="score">0</span>
    </div>
    <div class="score">
        <span>Correct: </span>
        <span id="correct">0</span>
    </div>
    <div class="score">
        <span>Incorrect: </span>
        <span id="incorrect">0</span>
    </div>
</div>

<div class="jobs-container">
    <?php if (empty($jobs)): ?>
        <div class="alert alert-info">No jobs available yet. Please check back later!</div>
    <?php else: ?>
        <div class="job-game animate">
            <div class="job-display">
                <img id="current-job" src="" alt="Job">
                <button id="play-audio" class="btn btn-circle">
                    <i class="fas fa-volume-up"></i>
                </button>
                <audio id="job-audio" src=""></audio>
            </div>
            
            <div class="job-question">
                <h3>comment appelle-t-on ce métier?</h3>
                <div class="options-container" id="options-container">
                    <!-- Options will be inserted here by JavaScript -->
                </div>
                <div class="result-message" id="job-result"></div>
            </div>
            
            <button id="next-job" class="btn btn-primary">Next Job</button>
        </div>
        
        <div class="jobs-info">
    <h2>Le savais-tu ?</h2>
    <div class="info-cards">
        <div class="info-card animate">
            <h3>Différents métiers</h3>
            <p>Il existe de nombreux métiers dans le monde. Chaque métier aide à améliorer notre communauté d'une manière unique !</p>
        </div>
        
        <div class="info-card animate">
            <h3>Outils de travail</h3>
            <p>Chaque métier utilise des outils différents. Un médecin utilise un stéthoscope, un chef utilise des ustensiles de cuisine, et un enseignant utilise des livres et des marqueurs !</p>
        </div>
        
        <div class="info-card animate">
            <h3>Lieux de travail</h3>
            <p>On peut exercer un métier dans de nombreux endroits. Certaines personnes travaillent dans des bureaux, d'autres en plein air, et certaines même dans l'espace !</p>
        </div>
    </div>
</div>

        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Job data from PHP
                const jobs = <?php echo json_encode($jobs); ?>;
                let currentJobIndex = 0;
                let score = 0;
                let correct = 0;
                let incorrect = 0;
                
                // DOM elements
                const jobImage = document.getElementById('current-job');
                const jobAudio = document.getElementById('job-audio');
                const playButton = document.getElementById('play-audio');
                const optionsContainer = document.getElementById('options-container');
                const resultMessage = document.getElementById('job-result');
                const nextButton = document.getElementById('next-job');
                const scoreDisplay = document.getElementById('score');
                const correctDisplay = document.getElementById('correct');
                const incorrectDisplay = document.getElementById('incorrect');
                
                // Preload images for better performance
                jobs.forEach(job => {
                    const img = new Image();
                    img.src = '../' + job.image_path;
                });
                
                // Update score display
                function updateScore() {
                    scoreDisplay.textContent = score;
                    correctDisplay.textContent = correct;
                    incorrectDisplay.textContent = incorrect;
                }
                
                // Get random options
                function getRandomOptions(correctAnswer) {
                    // Copy jobs array and remove correct answer
                    const otherJobs = jobs.filter(job => job.job_name !== correctAnswer);
                    
                    // Shuffle other jobs
                    for (let i = otherJobs.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [otherJobs[i], otherJobs[j]] = [otherJobs[j], otherJobs[i]];
                    }
                    
                    // Take 3 random jobs
                    const randomOptions = otherJobs.slice(0, 3);
                    
                    // Add correct answer
                    randomOptions.push({ job_name: correctAnswer });
                    
                    // Shuffle options
                    for (let i = randomOptions.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [randomOptions[i], randomOptions[j]] = [randomOptions[j], randomOptions[i]];
                    }
                    
                    return randomOptions;
                }
                
                // Display a job and options
                function displayJob(index) {
                    const job = jobs[index];
                    
                    // Update job image
                    jobImage.src = '../' + job.image_path;
                    jobImage.alt = job.job_name;
                    
                    // Update audio
                    jobAudio.src = '../' + job.audio_path;
                    
                    // Generate options
                    const options = getRandomOptions(job.job_name);
                    optionsContainer.innerHTML = '';
                    
                    options.forEach(option => {
                        const button = document.createElement('button');
                        button.textContent = option.job_name;
                        button.classList.add('option-btn');
                        button.dataset.job = option.job_name;
                        
                        button.addEventListener('click', function() {
                            checkAnswer(this.dataset.job);
                        });
                        
                        optionsContainer.appendChild(button);
                    });
                    
                    // Reset result message
                    resultMessage.textContent = '';
                    resultMessage.className = 'result-message';
                    
                    // Enable options
                    enableOptions(true);
                }
                
                // Check the selected answer
                function checkAnswer(selectedJob) {
                    const correctJob = jobs[currentJobIndex].job_name;
                    
                    if (selectedJob === correctJob) {
                        resultMessage.textContent = 'Correct! 🎉';
                        resultMessage.className = 'result-message success';
                        score += 10;
                        correct++;
                    } else {
                        resultMessage.textContent = 'Incorrect! The answer is ' + correctJob;
                        resultMessage.className = 'result-message error';
                        incorrect++;
                        if (score > 0) score -= 5;
                    }
                    
                    updateScore();
                    
                    // Highlight correct answer and disable options
                    const options = optionsContainer.querySelectorAll('.option-btn');
                    options.forEach(option => {
                        if (option.dataset.job === correctJob) {
                            option.classList.add('correct');
                        } else if (option.dataset.job === selectedJob) {
                            option.classList.add('incorrect');
                        }
                    });
                    
                    enableOptions(false);
                }
                
                // Enable/disable option buttons
                function enableOptions(enable) {
                    const options = optionsContainer.querySelectorAll('.option-btn');
                    options.forEach(option => {
                        option.disabled = !enable;
                    });
                }
                
                // Event listeners
                playButton.addEventListener('click', function() {
                    jobAudio.play();
                });
                
                nextButton.addEventListener('click', function() {
                    currentJobIndex = (currentJobIndex + 1) % jobs.length;
                    displayJob(currentJobIndex);
                });
                
                // Start the game
                displayJob(currentJobIndex);
            });
        </script>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
