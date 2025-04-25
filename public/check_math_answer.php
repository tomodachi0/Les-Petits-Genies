<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['answer']) || !isset($data['correct_answer'])) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$user_answer = (int)$data['answer'];
$correct_answer = (int)$data['correct_answer'];

// Check if answer is correct
$is_correct = $user_answer === $correct_answer;

// Return response
echo json_encode([
    'correct' => $is_correct,
    'correct_answer' => $correct_answer
]); 