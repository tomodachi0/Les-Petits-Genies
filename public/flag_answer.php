<?php
// check_flag_answer.php

session_start();

// Get the selected answer from POST request
$selectedAnswer = $_POST['answer'] ?? '';

// Get the correct answer from session
$correctAnswer = $_SESSION['correct_flag'] ?? '';
$audioFile = $_SESSION['flag_audio'] ?? '';

// Check if the answer is correct
$isCorrect = ($selectedAnswer === $correctAnswer);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'correct' => $isCorrect,
    'country' => $correctAnswer,
    'audio' => $audioFile
]);
?>