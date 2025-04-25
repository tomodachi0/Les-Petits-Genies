<?php
require_once '../includes/file_upload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        
        // Handle flag image upload
        $flag_upload = handleFileUpload($_FILES['flag_image'], 'image');
        if (!$flag_upload['success']) {
            $error = $flag_upload['message'];
        }
        
        // Handle audio file upload
        $audio_upload = handleFileUpload($_FILES['audio_file'], 'audio');
        if (!$audio_upload['success']) {
            $error = $audio_upload['message'];
        }
        
        if (!isset($error)) {
            $query = "INSERT INTO countries (name, flag_image, audio_file) 
                     VALUES (:name, :flag_image, :audio_file)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':flag_image', $flag_upload['filename']);
            $stmt->bindParam(':audio_file', $audio_upload['filename']);
            $stmt->execute();
        }
    }
}
?>

<div class="section-container">
    <h2>Countries & Flags Management</h2>
    
    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Add New Country Form -->
    <div class="form-container">
        <h3>Add New Country</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Country Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="flag_image">Flag Image:</label>
                <input type="file" id="flag_image" name="flag_image" accept="image/*" required>
            </div>
            <div class="form-group">
                <label for="audio_file">Country Name Audio:</label>
                <input type="file" id="audio_file" name="audio_file" accept="audio/*" required>
            </div>
            <button type="submit" name="add" class="btn">Add Country</button>
        </form>
    </div>

    <!-- Existing Countries Table -->
    <div class="table-container">
        <h3>Existing Countries</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Flag</th>
                    <th>Audio</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($content['countries'] as $country): ?>
                    <tr>
                        <td><?php echo $country['id']; ?></td>
                        <td><?php echo $country['name']; ?></td>
                        <td>
                            <img src="../assets/images/<?php echo $country['flag_image']; ?>" 
                                 alt="<?php echo $country['name']; ?> flag" 
                                 style="max-width: 100px;">
                        </td>
                        <td>
                            <audio controls>
                                <source src="../assets/audio/<?php echo $country['audio_file']; ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="table" value="countries">
                                <input type="hidden" name="id" value="<?php echo $country['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div> 