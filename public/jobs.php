<?php
// Database connection
require_once '../includes/db.php';
require_once '../includes/header.php';

// Get random job from database
function getRandomJob($conn) {
    $sql = "SELECT id, job_name, image_path, audio_path FROM jobs ORDER BY RAND() LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Get incorrect options for multiple choice
function getIncorrectOptions($conn, $correctId) {
    $sql = "SELECT job_name FROM jobs WHERE id != $correctId ORDER BY RAND() LIMIT 3";
    $result = $conn->query($sql);
    
    $options = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $options[] = $row['job_name'];
        }
    }
    
    return $options;
}

// Get current job and options
$currentJob = getRandomJob($conn);
$correctAnswer = $currentJob['job_name'];
$incorrectOptions = getIncorrectOptions($conn, $currentJob['id']);

// Combine all options and shuffle
$allOptions = array_merge([$correctAnswer], $incorrectOptions);
shuffle($allOptions);

// Store correct answer in session for AJAX verification
session_start();
$_SESSION['correct_job'] = $correctAnswer;
$_SESSION['job_audio'] = $currentJob['audio_path'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs Quiz</title>
    <style>
        body {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background-color: #f0f8e8;
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
            color: #4caf50;
            margin-bottom: 30px;
        }
        .job-container {
            margin-bottom: 20px;
        }
        .job-image {
            max-width: 300px;
            max-height: 300px;
            border: 2px solid #ddd;
            border-radius: 10px;
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
            background-color: #4caf50;
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
            background-color: #45a049;
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
        .job-sound-button {
            background-color: #9c27b0;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .job-sound-button:hover {
            background-color: #7b1fa2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Jobs Quiz</h1>
        
        <div class="job-container">
            <img src="<?php echo htmlspecialchars($currentJob['image_path']); ?>" alt="Job" class="job-image">
        </div>
        
        <button class="job-sound-button" onclick="playJobSound()">Hear Job Name</button>
        
        <h2>What job is this?</h2>
        
        <div class="options-container">
            <?php foreach ($allOptions as $option): ?>
                <button class="option-button" onclick="checkAnswer('<?php echo htmlspecialchars($option); ?>')"><?php echo htmlspecialchars($option); ?></button>
            <?php endforeach; ?>
        </div>
        
        <div class="feedback" id="feedback"></div>
        
        <button class="next-button" id="next-button" onclick="loadNewQuestion()">Next Job</button>
    </div>

    <script>
        // Audio element for playing job sounds
        const audioElement = new Audio();
        
        // Play job sound
        function playJobSound() {
            audioElement.src = '<?php echo htmlspecialchars($currentJob['audio_path']); ?>';
            audioElement.play();
        }
        
        // Check if answer is correct
        function checkAnswer(selectedOption) {
            // Disable all buttons after selection
            const buttons = document.querySelectorAll('.option-button');
            buttons.forEach(button => {
                button.disabled = true;
                if (button.textContent === selectedOption) {
                    button.style.backgroundColor = '#ff9800';
                }
            });
            
            // AJAX request to check answer
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_job_answer.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    const feedbackElement = document.getElementById('feedback');
                    
                    if (response.correct) {
                        feedbackElement.textContent = 'Correct! This is a ' + response.job;
                        feedbackElement.className = 'feedback correct';
                        
                        // Play audio of job name
                        audioElement.src = response.audio;
                        audioElement.play();
                    } else {
                        feedbackElement.textContent = 'Incorrect. Try again!';
                        feedbackElement.className = 'feedback incorrect';
                        
                        // Re-enable buttons for another try
                        buttons.forEach(button => {
                            button.disabled = false;
                            if (button.textContent === selectedOption) {
                                button.style.backgroundColor = '#4caf50';
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