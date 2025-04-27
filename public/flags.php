<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';


try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM flags");
    $flags = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching flags: " . $e->getMessage());
    $flags = [];
}


if (!empty($flags)) {
    shuffle($flags);
}
?>

<div class="page-header">
    <h1><i class="fas fa-flag"></i>Les drapeaux </h1>
    <p>Apprend les drapeaux des pays !</p>
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
                <h3>C'est le drapeau de quel pays?</h3>
                <div class="options-container" id="options-container">
                   
                </div>
                <div class="result-message" id="flag-result"></div>
            </div>
            
            <button id="next-flag" class="btn btn-primary">drapeau suivant</button>
        </div>
        
        <div class="flags-info">
    <h2>Le savais-tu ?</h2>
    <div class="info-cards">
        <div class="info-card animate">
            <h3>Couleurs des drapeaux</h3>
            <p>Les couleurs des drapeaux ont souvent des significations particulières. Le rouge peut symboliser le courage ou le sang versé pour l'indépendance, le blanc représente souvent la paix, et le bleu peut représenter la liberté ou le ciel.</p>
        </div>
        
        <div class="info-card animate">
            <h3>Formes des drapeaux</h3>
            <p>La plupart des drapeaux nationaux sont rectangulaires, mais le Népal possède le seul drapeau national non rectangulaire — il est en forme de deux triangles !</p>
        </div>
        
        <div class="info-card animate">
            <h3>Symboles sur les drapeaux</h3>
            <p>Les symboles sur les drapeaux ont des significations spéciales. Les étoiles peuvent représenter des États ou des provinces, les animaux peuvent symboliser la force, et les plantes peuvent illustrer ce qui pousse dans le pays.</p>
        </div>
    </div>
</div>

        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
              
                const flags = <?php echo json_encode($flags); ?>;
                let currentFlagIndex = 0;
                let score = 0;
                let correct = 0;
                let incorrect = 0;
                
           
                const flagImage = document.getElementById('current-flag');
                const flagAudio = document.getElementById('flag-audio');
                const playButton = document.getElementById('play-audio');
                const optionsContainer = document.getElementById('options-container');
                const resultMessage = document.getElementById('flag-result');
                const nextButton = document.getElementById('next-flag');
                const scoreDisplay = document.getElementById('score');
                const correctDisplay = document.getElementById('correct');
                const incorrectDisplay = document.getElementById('incorrect');
                
             
                flags.forEach(flag => {
                    const img = new Image();
                    img.src = '../' + flag.image_path;
                });
                
                
                function updateScore() {
                    scoreDisplay.textContent = score;
                    correctDisplay.textContent = correct;
                    incorrectDisplay.textContent = incorrect;
                }
                
                function getRandomOptions(correctAnswer) {
                    
                    const otherFlags = flags.filter(flag => flag.country_name !== correctAnswer);
                    
                  
                    for (let i = otherFlags.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [otherFlags[i], otherFlags[j]] = [otherFlags[j], otherFlags[i]];
                    }
                    
                   
                    const randomOptions = otherFlags.slice(0, 3);
                    
                   
                    randomOptions.push({ country_name: correctAnswer });
                    
                    
                    for (let i = randomOptions.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [randomOptions[i], randomOptions[j]] = [randomOptions[j], randomOptions[i]];
                    }
                    
                    return randomOptions;
                }
                
             
                function displayFlag(index) {
                    const flag = flags[index];
                    
                   
                    flagImage.src = '../' + flag.image_path;
                    flagImage.alt = flag.country_name + ' Flag';
                    
                    
                    flagAudio.src = '../' + flag.audio_path;

                    
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
                    
                  
                    resultMessage.textContent = '';
                    resultMessage.className = 'result-message';
                    
                  
                    enableOptions(true);
                }
                
           
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
                    
                    
                    const options = optionsContainer.querySelectorAll('.option-btn');
                    options.forEach(option => {
                        if (option.dataset.country === correctCountry) {
                            option.classList.add('correct');
                        } else if (option.dataset.country === selectedCountry) {
                            option.classList.add('incorrect');
                        }
                    });
                    
                    
                    enableOptions(false);
                }
                
                
                function enableOptions(enable) {
                    const options = optionsContainer.querySelectorAll('.option-btn');
                    options.forEach(option => {
                        option.disabled = !enable;
                    });
                }
                
               
                nextButton.addEventListener('click', function() {
                    currentFlagIndex = (currentFlagIndex + 1) % flags.length;
                    displayFlag(currentFlagIndex);
                });
                
               
                playButton.addEventListener('click', function() {
                    flagAudio.play();
                });
                
                
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
