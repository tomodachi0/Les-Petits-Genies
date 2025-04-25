<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// Fetch stories from database
$sql = "SELECT * FROM stories ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids Stories - Educational Videos</title>
    <style>
        .stories-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .story-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .story-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .story-card:hover {
            transform: translateY(-5px);
        }

        .story-thumbnail {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .story-content {
            padding: 15px;
        }

        .story-title {
            font-size: 1.2em;
            margin: 0 0 10px 0;
            color: #333;
        }

        .story-description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
        }

        .watch-button {
            display: inline-block;
            background: #ff0000;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background 0.3s ease;
        }

        .watch-button:hover {
            background: #cc0000;
        }

        .video-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }

        .close-modal {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <div class="stories-container">
        <h1>Educational Stories</h1>
        
        <div class="story-grid">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $thumbnail = $row['thumbnail_path'] ? $row['thumbnail_path'] : 'default-thumbnail.jpg';
                    ?>
                    <div class="story-card">
                        <img src="<?php echo htmlspecialchars($thumbnail); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="story-thumbnail">
                        <div class="story-content">
                            <h3 class="story-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p class="story-description"><?php echo htmlspecialchars($row['description']); ?></p>
                            <a href="#" class="watch-button" data-video="<?php echo htmlspecialchars($row['youtube_url']); ?>">Watch Story</a>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p>No stories available at the moment.</p>";
            }
            ?>
        </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" class="video-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="video-container">
                <iframe id="videoFrame" frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('videoModal');
        const videoFrame = document.getElementById('videoFrame');
        const closeBtn = document.querySelector('.close-modal');
        const watchButtons = document.querySelectorAll('.watch-button');

        // Function to extract YouTube video ID from URL
        function getYouTubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        // Add click event to all watch buttons
        watchButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const videoUrl = button.dataset.video;
                const videoId = getYouTubeId(videoUrl);
                if (videoId) {
                    videoFrame.src = `https://www.youtube.com/embed/${videoId}`;
                    modal.style.display = 'block';
                }
            });
        });

        // Close modal
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            videoFrame.src = '';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                videoFrame.src = '';
            }
        });
    </script>
</body>
</html> 