<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'specialist') {
    header("Location: ../login.php"); exit;
}

$olympiad_id = isset($_GET['olympiad_id']) ? (int)$_GET['olympiad_id'] : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $q_text = trim($_POST['question_text']);
    $q_type = $_POST['question_type'];
    $points = (int)$_POST['points'];
    $olympiad_id = (int)$_POST['olympiad_id'];

    $stmt_event = $pdo->prepare("SELECT event_type FROM olympiads WHERE id = ?");
    $stmt_event->execute([$olympiad_id]);
    
    $event_type = $stmt_event->fetchColumn();
    if ($event_type === 'contest') {
        $requires_manual_check = 1;
    } else {
        $requires_manual_check = isset($_POST['requires_manual_check']) ? 1 : 0;
    }

    $sql = "INSERT INTO questions (olympiad_id, question_text, question_type, points, requires_manual_check) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$olympiad_id, $q_text, $q_type, $points, $requires_manual_check])) {
        $question_id = $pdo->lastInsertId();

        if ($q_type === 'text') {
            $correct_text = trim($_POST['correct_text_answer']);
            if (!empty($correct_text)) {
                $sql_opt = "INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, 1)";
                $pdo->prepare($sql_opt)->execute([$question_id, $correct_text]);
            }
        } else {
            if (!empty($_POST['options'])) {
                $options = $_POST['options'];
                
                $raw_correct = isset($_POST['is_correct']) ? $_POST['is_correct'] : [];
                $is_correct_arr = is_array($raw_correct) ? $raw_correct : [$raw_correct];

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

$stmt_current_event = $pdo->prepare("SELECT event_type FROM olympiads WHERE id = ?");
$stmt_current_event->execute([$olympiad_id]);
$current_event_type = $stmt_current_event->fetchColumn() ?: 'olympiad';
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Додавання питання</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="olympiad_id" value="<?= $olympiad_id ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Текст питання</label>
                            <textarea name="question_text" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Тип питання</label>
                                <select name="question_type" id="q_type" class="form-select" onchange="toggleOptions()">
                                    <option value="single">Один з багатьох (Radio)</option>
                                    <option value="multiple">Багато з багатьох (Checkbox)</option>
                                    <option value="text">Текстова відповідь</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Бали за питання</label>
                                <input type="number" name="points" class="form-control" value="1" min="1">
                            </div>
                        </div>

                        <?php if ($current_event_type !== 'contest'): ?>
                            <div class="form-check form-switch mb-4 p-3 bg-body-tertiary border rounded">
                                <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="manual_check" name="requires_manual_check" value="1">
                                <label class="form-check-label fw-bold text-info" for="manual_check">
                                    <i class="bi bi-person-check-fill"></i> Потрібна додаткова перевірка фахівцем
                                </label>
                                <small class="d-block text-muted ms-5 mt-1">Якщо увімкнено, ви зможете змінити бал за це питання вручну після здачі тесту.</small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-4 border-info">
                                <i class="bi bi-info-circle-fill"></i> <strong>Це завдання конкурсу.</strong> 
                                <br><small>Відповідь на це питання обов'язково потребуватиме вашої ручної перевірки та оцінювання після того, як учасник здасть роботу.</small>
                            </div>
                        <?php endif; ?>

                        <div id="options_block" class="bg-body-tertiary p-3 border rounded mb-3">
                            <label class="form-label fw-bold">Варіанти відповідей:</label>
                            <small class="text-muted d-block mb-2" id="hint_text">
                                Введіть варіанти та виберіть правильний.
                            </small>
                            
                            <?php for($i=0; $i<4; $i++): ?>
                            <div class="input-group mb-2">
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0 correct-selection" 
                                           type="radio" 
                                           name="is_correct" 
                                           value="<?= $i ?>">
                                </div>
                                <input type="text" name="options[<?= $i ?>]" class="form-control" placeholder="Варіант <?= $i+1 ?>">
                            </div>
                            <?php endfor; ?>
                        </div>

                        <div id="text_answer_block" class="bg-body-tertiary p-3 border rounded mb-3" style="display:none;">
                            <label class="form-label fw-bold">Правильна відповідь:</label>
                            <small class="text-muted d-block mb-2">Введіть точну відповідь (регістр не важливий).</small>
                            <input type="text" name="correct_text_answer" class="form-control" placeholder="Наприклад: Київ">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="questions.php?id=<?= $olympiad_id ?>" class="btn btn-secondary">Назад</a>
                            <button type="submit" class="btn btn-info text-white">Зберегти питання</button>
                        </div>
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
    var hintText = document.getElementById('hint_text');
    
    var inputs = document.querySelectorAll('.correct-selection');

    if (type === 'text') {
        optionsBlock.style.display = 'none';
        textBlock.style.display = 'block';
    } else {
        optionsBlock.style.display = 'block';
        textBlock.style.display = 'none';

        inputs.forEach(function(input) {
            if (type === 'single') {
                input.type = 'radio';
                input.name = 'is_correct'; 
                hintText.innerText = "Оберіть одну правильну відповідь.";
            } else {
                input.type = 'checkbox';
                input.name = 'is_correct[]';
                hintText.innerText = "Оберіть декілька правильних відповідей.";
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleOptions();
});
</script>

<?php include '../includes/footer.php'; ?>