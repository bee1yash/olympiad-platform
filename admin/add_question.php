<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

$olympiad_id = isset($_GET['olympiad_id']) ? (int)$_GET['olympiad_id'] : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $q_text = trim($_POST['question_text']);
    $q_type = $_POST['question_type'];
    $points = (int)$_POST['points'];
    $olympiad_id = (int)$_POST['olympiad_id'];

    $sql = "INSERT INTO questions (olympiad_id, question_text, question_type, points) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$olympiad_id, $q_text, $q_type, $points])) {
        $question_id = $pdo->lastInsertId();

        if ($q_type === 'text') {
            $correct_text = trim($_POST['correct_text_answer']);
            if (!empty($correct_text)) {
                $sql_opt = "INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, 1)";
                $stmt_opt = $pdo->prepare($sql_opt);
                $stmt_opt->execute([$question_id, $correct_text]);
            }
        } else {
            // --- СТАРА ЧАСТИНА ДЛЯ ТЕСТІВ ---
            if (!empty($_POST['options'])) {
                $options = $_POST['options'];
                $is_correct_arr = isset($_POST['is_correct']) ? $_POST['is_correct'] : [];
                $sql_opt = "INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
                $stmt_opt = $pdo->prepare($sql_opt);

                foreach ($options as $index => $opt_text) {
                    if (trim($opt_text) !== '') {
                        $is_correct = in_array($index, $is_correct_arr) ? 1 : 0;
                        $stmt_opt->execute([$question_id, $opt_text, $is_correct]);
                    }
                }
            }
        }

        header("Location: questions.php?id=" . $olympiad_id);
        exit;
    } else {
        $message = "Помилка при збереженні питання.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Додавання питання</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="olympiad_id" value="<?= $olympiad_id ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Текст питання</label>
                            <textarea name="question_text" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Тип питання</label>
                                <select name="question_type" id="q_type" class="form-select" onchange="toggleOptions()">
                                    <option value="single">Один з багатьох (Тест)</option>
                                    <option value="multiple">Багато з багатьох (Тест)</option>
                                    <option value="text">Текстова відповідь</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Бали за питання</label>
                                <input type="number" name="points" class="form-control" value="1" min="1">
                            </div>
                        </div>

                        <div id="options_block" class="bg-light p-3 border rounded mb-3">
                            <label class="form-label fw-bold">Варіанти відповідей:</label>
                            <small class="text-muted d-block mb-2">Введіть варіанти та поставте галочку біля правильних.</small>
                            <?php for($i=0; $i<4; $i++): ?>
                            <div class="input-group mb-2">
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0" type="checkbox" name="is_correct[]" value="<?= $i ?>">
                                </div>
                                <input type="text" name="options[<?= $i ?>]" class="form-control" placeholder="Варіант <?= $i+1 ?>">
                            </div>
                            <?php endfor; ?>
                        </div>

                        <div id="text_answer_block" class="bg-light p-3 border rounded mb-3" style="display:none;">
                            <label class="form-label fw-bold">Правильна відповідь:</label>
                            <small class="text-muted d-block mb-2">Введіть точну відповідь для автоматичної перевірки (регістр не важливий).</small>
                            <input type="text" name="correct_text_answer" class="form-control" placeholder="Наприклад: 1991 або Київ">
                        </div>

                        <button type="submit" class="btn btn-success">Зберегти питання</button>
                        <a href="questions.php?id=<?= $olympiad_id ?>" class="btn btn-secondary">Назад</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleOptions() {
    var type = document.getElementById('q_type').value;
    var optionsBlock = document.getElementById('options_block');
    var textBlock = document.getElementById('text_answer_block');
    
    if (type === 'text') {
        optionsBlock.style.display = 'none';
        textBlock.style.display = 'block';
    } else {
        optionsBlock.style.display = 'block';
        textBlock.style.display = 'none';
    }
}
toggleOptions();
</script>

<?php include '../includes/footer.php'; ?>