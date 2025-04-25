<?php
require_once '../includes/file_upload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        // Handle animal image upload
        $image_upload = handleFileUpload($_FILES['image'], 'image');
        if (!$image_upload['success']) {
            $error = $image_upload['message'];
        }
        
        // Handle audio file upload
        $audio_upload = handleFileUpload($_FILES['audio_file'], 'audio');
        if (!$audio_upload['success']) {
            $error = $audio_upload['message'];
        }
        
        if (!isset($error)) {
            $query = "INSERT INTO animals (name, image, audio_file, description) 
                     VALUES (:name, :image, :audio_file, :description)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':image', $image_upload['filename']);
            $stmt->bindParam(':audio_file', $audio_upload['filename']);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
        }
    }
}
?>

<div class="section-container">
    <h2>Animals Management</h2>
    
    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Add New Animal Form -->
    <div class="form-container">
        <h3>Add New Animal</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Animal Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="image">Animal Image:</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>
            <div class="form-group">
                <label for="audio_file">Animal Name Audio:</label>
                <input type="file" id="audio_file" name="audio_file" accept="audio/*" required>
            </div>
            <button type="submit" name="add" class="btn">Add Animal</button>
        </form>
    </div>

    <!-- Existing Animals Table -->
    <div class="table-container">
        <h3>Existing Animals</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Audio</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($content['animals'] as $animal): ?>
                    <tr>
                        <td><?php echo $animal['id']; ?></td>
                        <td><?php echo $animal['name']; ?></td>
                        <td><?php echo $animal['description']; ?></td>
                        <td>
                            <img src="../assets/images/<?php echo $animal['image']; ?>" 
                                 alt="<?php echo $animal['name']; ?>" 
                                 style="max-width: 100px;">
                        </td>
                        <td>
                            <audio controls>
                                <source src="../assets/audio/<?php echo $animal['audio_file']; ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="table" value="animals">
                                <input type="hidden" name="id" value="<?php echo $animal['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div> 