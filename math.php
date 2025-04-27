<?php
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-calculator"></i> Math Fun</h1>
    <p>Practice your math skills with these fun exercises!</p>
</div>

<div class="score-display">
    <div class="score">
        <span>Score: </span>
        <span id="score">0</span>
    </div>
    <div class="score">
        <span>Correct: </span>
        <span id="correct">0</span>
    </div>
    <div class="score">
        <span>Incorrect: </span>
        <span id="incorrect">0</span>
    </div>
</div>

<div class="math-container">
    <div class="math-tabs">
        <button class="tab-btn active" data-tab="addition">Addition</button>
        <button class="tab-btn" data-tab="subtraction">Subtraction</button>
        <button class="tab-btn" data-tab="multiplication">Multiplication</button>
    </div>
    
    <div class="math-content">
        <!-- Addition -->
        <div class="tab-content active" id="addition">
            <div class="math-problem animate">
                <div class="problem-container">
                    <span id="add-num1">5</span>
                    <span>+</span>
                    <span id="add-num2">3</span>
                    <span>=</span>
                    <input type="number" id="add-answer" class="answer-input">
                </div>
                <button id="add-check" class="btn btn-primary">Check Answer</button>
                <div class="result-message" id="add-result"></div>
            </div>
            
            <div class="difficulty-selector">
                <p>Difficulty:</p>
                <button class="difficulty-btn active" data-difficulty="easy" data-type="addition">Easy (1-10)</button>
                <button class="difficulty-btn" data-difficulty="medium" data-type="addition">Medium (1-50)</button>
                <button class="difficulty-btn" data-difficulty="hard" data-type="addition">Hard (1-100)</button>
            </div>
        </div>
        
        <!-- Subtraction -->
        <div class="tab-content" id="subtraction">
            <div class="math-problem animate">
                <div class="problem-container">
                    <span id="sub-num1">8</span>
                    <span>-</span>
                    <span id="sub-num2">5</span>
                    <span>=</span>
                    <input type="number" id="sub-answer" class="answer-input">
                </div>
                <button id="sub-check" class="btn btn-primary">Check Answer</button>
                <div class="result-message" id="sub-result"></div>
            </div>
            
            <div class="difficulty-selector">
                <p>Difficulty:</p>
                <button class="difficulty-btn active" data-difficulty="easy" data-type="subtraction">Easy (1-10)</button>
                <button class="difficulty-btn" data-difficulty="medium" data-type="subtraction">Medium (1-50)</button>
                <button class="difficulty-btn" data-difficulty="hard" data-type="subtraction">Hard (1-100)</button>
            </div>
        </div>
        
        <!-- Multiplication -->
        <div class="tab-content" id="multiplication">
            <div class="math-problem animate">
                <div class="problem-container">
                    <span id="mul-num1">2</span>
                    <span>×</span>
                    <span id="mul-num2">3</span>
                    <span>=</span>
                    <input type="number" id="mul-answer" class="answer-input">
                </div>
                <button id="mul-check" class="btn btn-primary">Check Answer</button>
                <div class="result-message" id="mul-result"></div>
            </div>
            
            <div class="difficulty-selector">
                <p>Difficulty:</p>
                <button class="difficulty-btn active" data-difficulty="easy" data-type="multiplication">Easy (1-5)</button>
                <button class="difficulty-btn" data-difficulty="medium" data-type="multiplication">Medium (1-10)</button>
                <button class="difficulty-btn" data-difficulty="hard" data-type="multiplication">Hard (1-12)</button>
            </div>
        </div>
    </div>
</div>

<div class="math-tips">
    <h2>Math Tips</h2>
    <div class="tips-container">
        <div class="tip animate">
            <h3>Addition Tips</h3>
            <p>Start by counting on your fingers for small numbers. For larger numbers, try to break them down into tens and ones.</p>
        </div>
        
        <div class="tip animate">
            <h3>Subtraction Tips</h3>
            <p>Think of subtraction as "how many more to get from the smaller to the larger number" or count backwards.</p>
        </div>
        
        <div class="tip animate">
            <h3>Multiplication Tips</h3>
            <p>Multiplication is repeated addition. For example, 3 × 4 means 3 + 3 + 3 + 3 or 4 + 4 + 4.</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Track score
        let score = 0;
        let correct = 0;
        let incorrect = 0;
        
        // Store current problems
        const problems = {
            addition: { num1: 0, num2: 0 },
            subtraction: { num1: 0, num2: 0 },
            multiplication: { num1: 0, num2: 0 }
        };
        
        // Store difficulty ranges
        const difficulties = {
            addition: {
                easy: { min: 1, max: 10 },
                medium: { min: 1, max: 50 },
                hard: { min: 1, max: 100 }
            },
            subtraction: {
                easy: { min: 1, max: 10 },
                medium: { min: 1, max: 50 },
                hard: { min: 1, max: 100 }
            },
            multiplication: {
                easy: { min: 1, max: 5 },
                medium: { min: 1, max: 10 },
                hard: { min: 1, max: 12 }
            }
        };
        
        // Current difficulty
        const currentDifficulty = {
            addition: 'easy',
            subtraction: 'easy',
            multiplication: 'easy'
        };
        
        // Generate a random number within range
        function getRandomNumber(min, max) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
        }
        
        // Generate new problems
        function generateProblem(type) {
            const difficulty = currentDifficulty[type];
            const range = difficulties[type][difficulty];
            
            if (type === 'addition') {
                problems.addition.num1 = getRandomNumber(range.min, range.max);
                problems.addition.num2 = getRandomNumber(range.min, range.max);
                document.getElementById('add-num1').textContent = problems.addition.num1;
                document.getElementById('add-num2').textContent = problems.addition.num2;
                document.getElementById('add-answer').value = '';
                document.getElementById('add-result').textContent = '';
                document.getElementById('add-result').className = 'result-message';
                
            } else if (type === 'subtraction') {
                let num1 = getRandomNumber(range.min, range.max);
                let num2 = getRandomNumber(range.min, range.max);
                
                // Ensure num1 is greater than or equal to num2 for positive results
                if (num1 < num2) {
                    [num1, num2] = [num2, num1];
                }
                
                problems.subtraction.num1 = num1;
                problems.subtraction.num2 = num2;
                document.getElementById('sub-num1').textContent = problems.subtraction.num1;
                document.getElementById('sub-num2').textContent = problems.subtraction.num2;
                document.getElementById('sub-answer').value = '';
                document.getElementById('sub-result').textContent = '';
                document.getElementById('sub-result').className = 'result-message';
                
            } else if (type === 'multiplication') {
                problems.multiplication.num1 = getRandomNumber(range.min, range.max);
                problems.multiplication.num2 = getRandomNumber(range.min, range.max);
                document.getElementById('mul-num1').textContent = problems.multiplication.num1;
                document.getElementById('mul-num2').textContent = problems.multiplication.num2;
                document.getElementById('mul-answer').value = '';
                document.getElementById('mul-result').textContent = '';
                document.getElementById('mul-result').className = 'result-message';
            }
        }
        
        // Update score display
        function updateScore() {
            document.getElementById('score').textContent = score;
            document.getElementById('correct').textContent = correct;
            document.getElementById('incorrect').textContent = incorrect;
        }
        
        // Check answers
        function checkAnswer(type) {
            let userAnswer, correctAnswer, resultElement;
            
            if (type === 'addition') {
                userAnswer = parseInt(document.getElementById('add-answer').value);
                correctAnswer = problems.addition.num1 + problems.addition.num2;
                resultElement = document.getElementById('add-result');
            } else if (type === 'subtraction') {
                userAnswer = parseInt(document.getElementById('sub-answer').value);
                correctAnswer = problems.subtraction.num1 - problems.subtraction.num2;
                resultElement = document.getElementById('sub-result');
            } else if (type === 'multiplication') {
                userAnswer = parseInt(document.getElementById('mul-answer').value);
                correctAnswer = problems.multiplication.num1 * problems.multiplication.num2;
                resultElement = document.getElementById('mul-result');
            }
            
            if (isNaN(userAnswer)) {
                resultElement.textContent = 'Please enter a number!';
                resultElement.className = 'result-message error';
                return;
            }
            
            if (userAnswer === correctAnswer) {
                resultElement.textContent = 'Correct! 🎉';
                resultElement.className = 'result-message success';
                score += 10;
                correct++;
                // Generate a new problem after correct answer
                setTimeout(() => {
                    generateProblem(type);
                }, 1000);
            } else {
                resultElement.textContent = 'Incorrect! The answer is ' + correctAnswer;
                resultElement.className = 'result-message error';
                incorrect++;
                if (score > 0) score -= 5;
            }
            
            updateScore();
        }
        
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        // Difficulty selector
        document.querySelectorAll('.difficulty-btn').forEach(button => {
            button.addEventListener('click', function() {
                const type = this.dataset.type;
                const difficulty = this.dataset.difficulty;
                
                // Update active difficulty button
                document.querySelectorAll(`.difficulty-btn[data-type="${type}"]`).forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Update current difficulty and generate new problem
                currentDifficulty[type] = difficulty;
                generateProblem(type);
            });
        });
        
        // Check answer buttons
        document.getElementById('add-check').addEventListener('click', () => checkAnswer('addition'));
        document.getElementById('sub-check').addEventListener('click', () => checkAnswer('subtraction'));
        document.getElementById('mul-check').addEventListener('click', () => checkAnswer('multiplication'));
        
        // Enter key for answer inputs
        document.getElementById('add-answer').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') checkAnswer('addition');
        });
        document.getElementById('sub-answer').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') checkAnswer('subtraction');
        });
        document.getElementById('mul-answer').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') checkAnswer('multiplication');
        });
        
        // Initialize with problems
        generateProblem('addition');
        generateProblem('subtraction');
        generateProblem('multiplication');
    });
</script>

<?php
require_once '../includes/footer.php';
?> 