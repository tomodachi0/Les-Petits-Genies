<?php
session_start();
header('Content-Type: application/json');

if (!isset($_POST['answer']) || !isset($_SESSION['correct_animal']) || !isset($_SESSION['animal_audio'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$userAnswer = $_POST['answer'];
$correctAnswer = $_SESSION['correct_animal'];
$audioFile = $_SESSION['animal_audio'];

$response = [
    'correct' => strtolower($userAnswer) === strtolower($correctAnswer),
    'animal' => $correctAnswer,
    'audio' => $audioFile
];

echo json_encode($response); 