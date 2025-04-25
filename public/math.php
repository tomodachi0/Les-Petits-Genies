<?php
require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch all math exercises from the database
$query = "SELECT * FROM math_exercises ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Math Games</h1>
    
    <div class="row">
        <?php foreach ($exercises as $exercise): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo ucfirst($exercise['operation_type']); ?></h5>
                        <p class="card-text">
                            <?php echo $exercise['number1'] . ' ' . 
                                ($exercise['operation_type'] === 'addition' ? '+' : 
                                ($exercise['operation_type'] === 'subtraction' ? '-' : '×')) . 
                                ' ' . $exercise['number2'] . ' = ?'; ?>
                        </p>
                        <button class="btn btn-primary show-answer" 
                                data-answer="<?php echo $exercise['correct_answer']; ?>">
                            Show Answer
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const answerButtons = document.querySelectorAll('.show-answer');
    
    answerButtons.forEach(button => {
        button.addEventListener('click', function() {
            const answer = this.getAttribute('data-answer');
            this.textContent = answer;
            this.classList.remove('btn-primary');
            this.classList.add('btn-success');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>