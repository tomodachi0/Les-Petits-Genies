<?php
// Handle form submission for adding new job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_job'])) {
    $job_name = $_POST['job_name'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../uploads/jobs/images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $image_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = 'uploads/jobs/images/' . $image_name;
        }
    }
    
    // Handle audio upload
    $audio_path = '';
    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === 0) {
        $upload_dir = '../uploads/jobs/audio/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $audio_name = time() . '_' . basename($_FILES['audio']['name']);
        $target_path = $upload_dir . $audio_name;
        
        if (move_uploaded_file($_FILES['audio']['tmp_name'], $target_path)) {
            $audio_path = 'uploads/jobs/audio/' . $audio_name;
        }
    }
    
    if ($image_path && $audio_path) {
        $sql = "INSERT INTO jobs (job_name, image_path, audio_path) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $job_name, $image_path, $audio_path);
        $stmt->execute();
    }
}
?>

<h2>Manage Jobs</h2>

<!-- Add new job form -->
<div class="add-form">
    <h3>Add New Job</h3>
    <form method="POST" enctype="multipart/form-data">
        <div style="margin-bottom: 15px;">
            <label for="job_name">Job Name:</label><br>
            <input type="text" id="job_name" name="job_name" required style="width: 100%; padding: 8px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="image">Job Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*" required>
            <p style="color: #666; font-size: 0.9em;">Recommended size: 300x300 pixels</p>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="audio">Job Name Audio:</label><br>
            <input type="file" id="audio" name="audio" accept="audio/*" required>
            <p style="color: #666; font-size: 0.9em;">Accepted formats: MP3, WAV</p>
        </div>

        <button type="submit" name="add_job" class="btn" style="background-color: #28a745;">Add Job</button>
    </form>
</div>

<!-- Display existing jobs -->
<table>
    <thead>
        <tr>
            <th>Job Name</th>
            <th>Image</th>
            <th>Audio</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($content && $content->num_rows > 0) {
            while ($row = $content->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['job_name']) . "</td>";
                echo "<td><img src='" . htmlspecialchars($row['image_path']) . "' height='50' alt='Job Image'></td>";
                echo "<td><audio controls src='" . htmlspecialchars($row['audio_path']) . "'></audio></td>";
                echo "<td>
                        <form method='POST' style='display: inline;'>
                            <input type='hidden' name='id' value='" . $row['id'] . "'>
                            <input type='hidden' name='section' value='jobs'>
                            <button type='submit' name='delete' class='btn' onclick='return confirm(\"Are you sure?\");'>Delete</button>
                        </form>
                    </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No jobs found</td></tr>";
        }
        ?>
    </tbody>
</table> 