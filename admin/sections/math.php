<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $operation_type = $_POST['operation_type'];
        $num1 = $_POST['num1'];
        $num2 = $_POST['num2'];
        $correct_answer = $_POST['correct_answer'];
        
        $query = "INSERT INTO math_exercises (operation_type, num1, num2, correct_answer) 
                 VALUES (:operation_type, :num1, :num2, :correct_answer)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':operation_type', $operation_type);
        $stmt->bindParam(':num1', $num1);
        $stmt->bindParam(':num2', $num2);
        $stmt->bindParam(':correct_answer', $correct_answer);
        $stmt->execute();
    }
}
?>

<div class="section-container">
    <h2>Math Exercises Management</h2>
    
    <!-- Add New Exercise Form -->
    <div class="form-container">
        <h3>Add New Exercise</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="operation_type">Operation Type:</label>
                <select name="operation_type" id="operation_type" required>
                    <option value="addition">Addition</option>
                    <option value="subtraction">Subtraction</option>
                    <option value="multiplication">Multiplication</option>
                </select>
            </div>
            <div class="form-group">
                <label for="num1">First Number:</label>
                <input type="number" id="num1" name="num1" required>
            </div>
            <div class="form-group">
                <label for="num2">Second Number:</label>
                <input type="number" id="num2" name="num2" required>
            </div>
            <div class="form-group">
                <label for="correct_answer">Correct Answer:</label>
                <input type="number" id="correct_answer" name="correct_answer" required>
            </div>
            <button type="submit" name="add" class="btn">Add Exercise</button>
        </form>
    </div>

    <!-- Existing Exercises Table -->
    <div class="table-container">
        <h3>Existing Exercises</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Operation</th>
                    <th>Numbers</th>
                    <th>Answer</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($content['math_exercises'] as $exercise): ?>
                    <tr>
                        <td><?php echo $exercise['id']; ?></td>
                        <td><?php echo ucfirst($exercise['operation_type']); ?></td>
                        <td><?php echo $exercise['num1'] . ' ' . $exercise['operation_type'] . ' ' . $exercise['num2']; ?></td>
                        <td><?php echo $exercise['correct_answer']; ?></td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="table" value="math_exercises">
                                <input type="hidden" name="id" value="<?php echo $exercise['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div> 