<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// Get all flags from database
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM flags");
    $flags = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching flags: " . $e->getMessage());
    $flags = [];
}

// Shuffle flags to randomize
if (!empty($flags)) {
    shuffle($flags);
}
?>

<div class="page-header">
    <h1><i class="fas fa-flag"></i> World Flags</h1>
    <p>Learn about different countries and their flags!</p>
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

<div class="flags-container">
    <?php if (empty($flags)): ?>
        <div class="alert alert-info">No flags available yet. Please check back later!</div>
    <?php else: ?>
        <div class="flag-game animate">
            <div class="flag-display">
                <img id="current-flag" src="" alt="Flag">
                <button id="play-audio" class="btn btn-circle">
                    <i class="fas fa-volume-up"></i>
                </button>
                <audio id="flag-audio" src=""></audio>
            </div>
            
            <div class="flag-question">
                <h3>Which country does this flag belong to?</h3>
                <div class="options-container" id="options-container">
                    <!-- Options will be inserted here by JavaScript -->
                </div>
                <div class="result-message" id="flag-result"></div>
            </div>
            
            <button id="next-flag" class="btn btn-primary">Next Flag</button>
        </div>
        
        <div class="flags-info">
            <h2>Did you know?</h2>
            <div class="info-cards">
                <div class="info-card animate">
                    <h3>Flag Colors</h3>
                    <p>The colors in flags often have special meanings. Red can symbolize courage or blood shed for independence, white often represents peace, and blue might stand for freedom or the sky.</p>
                </div>
                
                <div class="info-card animate">
                    <h3>Flag Shapes</h3>
                    <p>Most country flags are rectangular, but Nepal has the only non-rectangular national flag - it's shaped like two triangles!</p>
                </div>
                
                <div class="info-card animate">
                    <h3>Flag Symbols</h3>
                    <p>Symbols on flags have special meanings. Stars might represent states or provinces, animals can represent strength, and plants might show what grows in that country.</p>
                </div>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Flag data from PHP
                const flags = <?php echo json_encode($flags); ?>;
                let currentFlagIndex = 0;
                let score = 0;
                let correct = 0;
                let incorrect = 0;
                
                // DOM elements
                const flagImage = document.getElementById('current-flag');
                const flagAudio = document.getElementById('flag-audio');
                const playButton = document.getElementById('play-audio');
                const optionsContainer = document.getElementById('options-container');
                const resultMessage = document.getElementById('flag-result');
                const nextButton = document.getElementById('next-flag');
                const scoreDisplay = document.getElementById('score');
                const correctDisplay = document.getElementById('correct');
                const incorrectDisplay = document.getElementById('incorrect');
                
                // Preload images for better performance
                flags.forEach(flag => {
                    const img = new Image();
                    img.src = '../' + flag.image_path;
                });
                
                // Update score display
                function updateScore() {
                    scoreDisplay.textContent = score;
                    correctDisplay.textContent = correct;
                    incorrectDisplay.textContent = incorrect;
                }
                
                // Get random options
                function getRandomOptions(correctAnswer) {
                    // Copy flags array and remove correct answer
                    const otherFlags = flags.filter(flag => flag.country_name !== correctAnswer);
                    
                    // Shuffle other flags
                    for (let i = otherFlags.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [otherFlags[i], otherFlags[j]] = [otherFlags[j], otherFlags[i]];
                    }
                    
                    // Take 3 random flags
                    const randomOptions = otherFlags.slice(0, 3);
                    
                    // Add correct answer
                    randomOptions.push({ country_name: correctAnswer });
                    
                    // Shuffle options
                    for (let i = randomOptions.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [randomOptions[i], randomOptions[j]] = [randomOptions[j], randomOptions[i]];
                    }
                    
                    return randomOptions;
                }
                
                // Display a flag and options
                function displayFlag(index) {
                    const flag = flags[index];
                    
                    // Update flag image
                    flagImage.src = '../' + flag.image_path;
                    flagImage.alt = flag.country_name + ' Flag';
                    
                    // Update audio
                    flagAudio.src = '../' + flag.audio_path;
                    
                    // Generate options
                    const options = getRandomOptions(flag.country_name);
                    optionsContainer.innerHTML = '';
                    
                    options.forEach(option => {
                        const button = document.createElement('button');
                        button.textContent = option.country_name;
                        button.classList.add('option-btn');
                        button.dataset.country = option.country_name;
                        
                        button.addEventListener('click', function() {
                            checkAnswer(this.dataset.country);
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
                function checkAnswer(selectedCountry) {
                    const correctCountry = flags[currentFlagIndex].country_name;
                    
                    if (selectedCountry === correctCountry) {
                        resultMessage.textContent = 'Correct! 🎉';
                        resultMessage.className = 'result-message success';
                        score += 10;
                        correct++;
                    } else {
                        resultMessage.textContent = 'Incorrect! The answer is ' + correctCountry;
                        resultMessage.className = 'result-message error';
                        incorrect++;
                        if (score > 0) score -= 5;
                    }
                    
                    updateScore();
                    
                    // Highlight correct answer
                    const options = optionsContainer.querySelectorAll('.option-btn');
                    options.forEach(option => {
                        if (option.dataset.country === correctCountry) {
                            option.classList.add('correct');
                        } else if (option.dataset.country === selectedCountry) {
                            option.classList.add('incorrect');
                        }
                    });
                    
                    // Disable options after answer
                    enableOptions(false);
                }
                
                // Enable or disable option buttons
                function enableOptions(enable) {
                    const options = optionsContainer.querySelectorAll('.option-btn');
                    options.forEach(option => {
                        option.disabled = !enable;
                    });
                }
                
                // Next flag button
                nextButton.addEventListener('click', function() {
                    currentFlagIndex = (currentFlagIndex + 1) % flags.length;
                    displayFlag(currentFlagIndex);
                });
                
                // Play audio button
                playButton.addEventListener('click', function() {
                    flagAudio.play();
                });
                
                // Initialize with first flag
                if (flags.length > 0) {
                    displayFlag(0);
                }
            });
        </script>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?> 