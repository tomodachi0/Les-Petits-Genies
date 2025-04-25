<?php
function handleFileUpload($file, $type) {
    $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
    $allowed_audio_types = ['audio/mpeg', 'audio/wav', 'audio/mp3'];
    
    $upload_dir = '../assets/';
    $file_type = $file['type'];
    
    // Validate file type
    if ($type === 'image' && !in_array($file_type, $allowed_image_types)) {
        return ['success' => false, 'message' => 'Invalid image file type'];
    }
    if ($type === 'audio' && !in_array($file_type, $allowed_audio_types)) {
        return ['success' => false, 'message' => 'Invalid audio file type'];
    }
    
    // Create directory if it doesn't exist
    $target_dir = $upload_dir . ($type === 'image' ? 'images/' : 'audio/');
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => $target_file
        ];
    } else {
        return ['success' => false, 'message' => 'Error uploading file'];
    }
}
?> 