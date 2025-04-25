<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get random country from database
function getRandomCountry($db) {
    try {
        $query = "SELECT id, name, flag_image, audio_file FROM countries ORDER BY RAND() LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getRandomCountry: " . $e->getMessage());
        return null;
    }
}

// Get incorrect options for multiple choice
function getIncorrectOptions($db, $correctId) {
    try {
        $query = "SELECT name FROM countries WHERE id != :id ORDER BY RAND() LIMIT 3";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $correctId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Database error in getIncorrectOptions: " . $e->getMessage());
        return [];
    }
}

// Get current country and options
$currentCountry = getRandomCountry($db);
if (!$currentCountry) {
    die("Error: Could not load country data. Please try again later.");
}

$correctAnswer = $currentCountry['name'];
$incorrectOptions = getIncorrectOptions($db, $currentCountry['id']);

// Combine all options and shuffle
$allOptions = array_merge([$correctAnswer], $incorrectOptions);
shuffle($allOptions);

// Store correct answer in session for AJAX verification
$_SESSION['correct_flag'] = $correctAnswer;
$_SESSION['flag_audio'] = $currentCountry['audio_file'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>World Flags Quiz</title>
    <style>
        body {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background-color: #e6f7ff;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #4a86e8;
            margin-bottom: 30px;
        }
        .flag-container {
            margin-bottom: 20px;
        }
        .flag-image {
            max-width: 300px;
            border: 2px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .options-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        .option-button {
            background-color: #4a86e8;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            width: 200px;
        }
        .option-button:hover {
            background-color: #3a76d8;
            transform: scale(1.05);
        }
        .feedback {
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
            min-height: 30px;
        }
        .correct {
            color: green;
        }
        .incorrect {
            color: red;
        }
        .next-button {
            background-color: #ff9800;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 20px;
            display: none;
        }
        .next-button:hover {
            background-color: #e88a00;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>World Flags Quiz</h1>
        
        <div class="flag-container">
            <img src="../assets/images/flags/<?php echo htmlspecialchars($currentCountry['flag_image']); ?>" 
                 alt="Flag" 
                 class="flag-image">
        </div>
        
        <h2>Which country does this flag belong to?</h2>
        
        <div class="options-container">
            <?php foreach ($allOptions as $option): ?>
                <button class="option-button" onclick="checkAnswer('<?php echo htmlspecialchars($option); ?>')">
                    <?php echo htmlspecialchars($option); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <div class="feedback" id="feedback"></div>
        
        <button class="next-button" id="next-button" onclick="loadNewQuestion()">Next Flag</button>
    </div>

    <script>
        // Audio element for playing country name
        const audioElement = new Audio();
        
        // Check if answer is correct
        function checkAnswer(selectedOption) {
            // Disable all buttons after selection
            const buttons = document.querySelectorAll('.option-button');
            buttons.forEach(button => {
                button.disabled = true;
                // Highlight the selected button
                if (button.textContent === selectedOption) {
                    button.style.backgroundColor = '#ff9800';
                }
            });
            
            // AJAX request to check answer
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_flag_answer.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    const feedbackElement = document.getElementById('feedback');
                    
                    if (response.correct) {
                        feedbackElement.textContent = 'Correct! This is the flag of ' + response.country;
                        feedbackElement.className = 'feedback correct';
                        
                        // Play audio of country name
                        audioElement.src = '../assets/audio/flags/' + response.audio;
                        audioElement.play();
                    } else {
                        feedbackElement.textContent = 'Incorrect. Try again!';
                        feedbackElement.className = 'feedback incorrect';
                        
                        // Re-enable buttons for another try
                        buttons.forEach(button => {
                            button.disabled = false;
                            if (button.textContent === selectedOption) {
                                button.style.backgroundColor = '#4a86e8';
                            }
                        });
                    }
                    
                    // Show next button if correct
                    if (response.correct) {
                        document.getElementById('next-button').style.display = 'inline-block';
                    }
                }
            };
            xhr.send('answer=' + encodeURIComponent(selectedOption));
        }
        
        // Load new question
        function loadNewQuestion() {
            window.location.reload();
        }
    </script>
</body>
</html>

<?php require_once '../includes/footer.php'; ?>