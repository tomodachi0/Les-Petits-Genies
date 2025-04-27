<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// Get all animals from database
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM animals");
    $animals = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching animals: " . $e->getMessage());
    $animals = [];
}

// Shuffle animals to randomize
if (!empty($animals)) {
    shuffle($animals);
}
?>

<div class="page-header">
    <h1><i class="fas fa-paw"></i>Le Quiz des animaux</h1>
    <p>Découvre différents animaux et leurs sons !</p>
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

<div class="animals-container">
    <?php if (empty($animals)): ?>
        <div class="alert alert-info">No animals available yet. Please check back later!</div>
    <?php else: ?>
        <div class="animal-game animate">
            <div class="animal-display">
                <img id="current-animal" src="" alt="Animal">
                <button id="play-audio" class="btn btn-circle">
                    <i class="fas fa-volume-up"></i>
                </button>
                <audio id="animal-audio" src=""></audio>
            </div>
            
            <div class="animal-question">
                <h3>C'est quel animal?</h3>
                <div class="options-container" id="options-container">
                    <!-- Options will be inserted here by JavaScript -->
                </div>
                <div class="result-message" id="animal-result"></div>
            </div>
            
            <button id="next-animal" class="btn btn-primary">animal suivant</button>
        </div>
        
        <div class="animals-info">
        <h2>Le savais-tu ?</h2>
<div class="info-cards">
    <div class="info-card animate">
        <h3>Sons des animaux</h3>
        <p>Les différents animaux produisent des sons uniques pour communiquer. Ces sons les aident à exprimer leurs émotions, à avertir des dangers ou à trouver des partenaires !</p>
    </div>
    
    <div class="info-card animate">
        <h3>Habitats des animaux</h3>
        <p>Les animaux vivent dans différents endroits appelés habitats. Certains vivent dans les forêts, d'autres dans les océans, et certains même dans les déserts !</p>
    </div>
    
    <div class="info-card animate">
        <h3>Anecdotes sur les animaux</h3>
        <p>Chaque animal est spécial et possède des capacités extraordinaires. Certains peuvent voler, d'autres plonger profondément dans l'océan, et certains peuvent même changer de couleur !</p>
    </div>
</div>

            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Animal data from PHP
                const animals = <?php echo json_encode($animals); ?>;
                let currentAnimalIndex = 0;
                let score = 0;
                let correct = 0;
                let incorrect = 0;
                
                // DOM elements
                const animalImage = document.getElementById('current-animal');
                const animalAudio = document.getElementById('animal-audio');
                const playButton = document.getElementById('play-audio');
                const optionsContainer = document.getElementById('options-container');
                const resultMessage = document.getElementById('animal-result');
                const nextButton = document.getElementById('next-animal');
                const scoreDisplay = document.getElementById('score');
                const correctDisplay = document.getElementById('correct');
                const incorrectDisplay = document.getElementById('incorrect');
                
                // Preload images for better performance
                animals.forEach(animal => {
                    const img = new Image();
                    img.src = '../' + animal.image_path;
                });
                
                // Update score display
                function updateScore() {
                    scoreDisplay.textContent = score;
                    correctDisplay.textContent = correct;
                    incorrectDisplay.textContent = incorrect;
                }
                
                // Get random options
                function getRandomOptions(correctAnswer) {
                    // Copy animals array and remove correct answer
                    const otherAnimals = animals.filter(animal => animal.animal_name !== correctAnswer);
                    
                    // Shuffle other animals
                    for (let i = otherAnimals.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [otherAnimals[i], otherAnimals[j]] = [otherAnimals[j], otherAnimals[i]];
                    }
                    
                    // Take 3 random animals
                    const randomOptions = otherAnimals.slice(0, 3);
                    
                    // Add correct answer
                    randomOptions.push({ animal_name: correctAnswer });
                    
                    // Shuffle options
                    for (let i = randomOptions.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [randomOptions[i], randomOptions[j]] = [randomOptions[j], randomOptions[i]];
                    }
                    
                    return randomOptions;
                }
                
                // Display an animal and options
                function displayAnimal(index) {
                    const animal = animals[index];
                    
                    // Update animal image
                    animalImage.src = '../' + animal.image_path;
                    animalImage.alt = animal.animal_name;
                    
                    // Update audio
                    animalAudio.src = '../' + animal.audio_path;
                    
                    // Generate options
                    const options = getRandomOptions(animal.animal_name);
                    optionsContainer.innerHTML = '';
                    
                    options.forEach(option => {
                        const button = document.createElement('button');
                        button.textContent = option.animal_name;
                        button.classList.add('option-btn');
                        button.dataset.animal = option.animal_name;
                        
                        button.addEventListener('click', function() {
                            checkAnswer(this.dataset.animal);
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
                function checkAnswer(selectedAnimal) {
                    const correctAnimal = animals[currentAnimalIndex].animal_name;
                    
                    if (selectedAnimal === correctAnimal) {
                        resultMessage.textContent = 'Correct! 🎉';
                        resultMessage.className = 'result-message success';
                        score += 10;
                        correct++;
                    } else {
                        resultMessage.textContent = 'Incorrect! The answer is ' + correctAnimal;
                        resultMessage.className = 'result-message error';
                        incorrect++;
                        if (score > 0) score -= 5;
                    }
                    
                    updateScore();
                    
                    // Highlight correct answer and disable options
                    const options = optionsContainer.querySelectorAll('.option-btn');
                    options.forEach(option => {
                        if (option.dataset.animal === correctAnimal) {
                            option.classList.add('correct');
                        } else if (option.dataset.animal === selectedAnimal) {
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
                    animalAudio.play();
                });
                
                nextButton.addEventListener('click', function() {
                    currentAnimalIndex = (currentAnimalIndex + 1) % animals.length;
                    displayAnimal(currentAnimalIndex);
                });
                
                // Start the game
                displayAnimal(currentAnimalIndex);
            });
        </script>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
