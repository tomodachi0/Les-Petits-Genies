<?php
session_start();
header('Content-Type: application/json');

$response = array(
    'correct' => false,
    'job' => '',
    'audio' => ''
);

if (isset($_POST['answer']) && isset($_SESSION['correct_job'])) {
    if ($_POST['answer'] === $_SESSION['correct_job']) {
        $response['correct'] = true;
        $response['job'] = $_SESSION['correct_job'];
        $response['audio'] = $_SESSION['job_audio'];
    }
}

echo json_encode($response); 