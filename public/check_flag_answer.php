<?php
session_start();
header('Content-Type: application/json');

if (!isset($_POST['answer']) || !isset($_SESSION['correct_flag']) || !isset($_SESSION['flag_audio'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$userAnswer = $_POST['answer'];
$correctAnswer = $_SESSION['correct_flag'];
$audioFile = $_SESSION['flag_audio'];

$response = [
    'correct' => strtolower($userAnswer) === strtolower($correctAnswer),
    'country' => $correctAnswer,
    'audio' => $audioFile
];

echo json_encode($response); 