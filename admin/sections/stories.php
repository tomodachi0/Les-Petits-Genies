<?php
require_once '../includes/file_upload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $category = $_POST['category'];
        
        // Handle audio file upload
        $audio_upload = handleFileUpload($_FILES['audio_file'], 'audio');
        if (!$audio_upload['success']) {
            $error = $audio_upload['message'];
        }
        
        if (!isset($error)) {
            $query = "INSERT INTO stories (title, content, audio_file, category) 
                     VALUES (:title, :content, :audio_file, :category)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':audio_file', $audio_upload['filename']);
            $stmt->bindParam(':category', $category);
            $stmt->execute();
        }
    }
}

// Handle form submission for adding new story
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_story'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $youtube_url = $_POST['youtube_url'];
    $thumbnail_path = $_POST['thumbnail_path'] ?? null;

    $sql = "INSERT INTO stories (title, description, youtube_url, thumbnail_path) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $title, $description, $youtube_url, $thumbnail_path);
    $stmt->execute();
}
?>

<div class="section-container">
    <h2>Stories & Music Management</h2>
    
    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Add New Story Form -->
    <div class="form-container">
        <h3>Add New Story/Music</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" required>
                    <option value="story">Story</option>
                    <option value="song">Song</option>
                    <option value="poem">Poem</option>
                </select>
            </div>
            <div class="form-group">
                <label for="content">Content/Description:</label>
                <textarea id="content" name="content" rows="5" required></textarea>
            </div>
            <div class="form-group">
                <label for="audio_file">Audio File:</label>
                <input type="file" id="audio_file" name="audio_file" accept="audio/*" required>
            </div>
            <button type="submit" name="add" class="btn">Add Story/Music</button>
        </form>
    </div>

    <!-- Existing Stories Table -->
    <div class="table-container">
        <h3>Existing Stories & Music</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Content</th>
                    <th>Audio</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($content['stories'] as $story): ?>
                    <tr>
                        <td><?php echo $story['id']; ?></td>
                        <td><?php echo $story['title']; ?></td>
                        <td><?php echo ucfirst($story['category']); ?></td>
                        <td><?php echo substr($story['content'], 0, 100) . '...'; ?></td>
                        <td>
                            <audio controls>
                                <source src="../assets/audio/<?php echo $story['audio_file']; ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="table" value="stories">
                                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add new story form -->
    <div class="add-form">
        <h3>Add New Story</h3>
        <form method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 15px;">
                <label for="title">Title:</label><br>
                <input type="text" id="title" name="title" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="description">Description:</label><br>
                <textarea id="description" name="description" rows="4" style="width: 100%; padding: 8px;"></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="youtube_url">YouTube URL:</label><br>
                <input type="url" id="youtube_url" name="youtube_url" required style="width: 100%; padding: 8px;"
                       placeholder="https://www.youtube.com/watch?v=...">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="thumbnail_path">Thumbnail URL (optional):</label><br>
                <input type="url" id="thumbnail_path" name="thumbnail_path" style="width: 100%; padding: 8px;"
                       placeholder="https://example.com/thumbnail.jpg">
            </div>

            <button type="submit" name="add_story" class="btn" style="background-color: #28a745;">Add Story</button>
        </form>
    </div>

    <!-- Display existing stories -->
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>YouTube URL</th>
                <th>Thumbnail</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($content && $content->num_rows > 0) {
                while ($row = $content->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                    echo "<td><a href='" . htmlspecialchars($row['youtube_url']) . "' target='_blank'>View Video</a></td>";
                    echo "<td>" . ($row['thumbnail_path'] ? "<img src='" . htmlspecialchars($row['thumbnail_path']) . "' height='50'>" : "No thumbnail") . "</td>";
                    echo "<td>
                            <form method='POST' style='display: inline;'>
                                <input type='hidden' name='id' value='" . $row['id'] . "'>
                                <input type='hidden' name='section' value='stories'>
                                <button type='submit' name='delete' class='btn' onclick='return confirm(\"Are you sure?\");'>Delete</button>
                            </form>
                        </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No stories found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div> 