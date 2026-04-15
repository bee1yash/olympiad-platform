<?php
session_start();
require '../config/db.php';

if ($_SESSION['role'] !== 'specialist') { header("Location: ../login.php"); exit; }

$result_id = (int)$_GET['result_id'];
$message = '';

// 1. Отримуємо дані результату на самому початку
$stmt = $pdo->prepare("SELECT * FROM results WHERE id = ?");
$stmt->execute([$result_id]);
$result = $stmt->fetch();
if (!$result) die("Роботу не знайдено");

$user_id = $result['user_id'];
$olympiad_id = $result['olympiad_id'];
// Перевіряємо, чи є замок (якщо раптом колонки ще немає, буде false)
$is_already_checked = isset($result['is_checked']) ? (bool)$result['is_checked'] : false;

// 2. Обробка форми
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['points'])) {
    if ($is_already_checked) {
        $message = "Помилка: Ця робота вже була перевірена раніше. Оцінки заблоковано.";
    } else {
        $new_points = $_POST['points'];
        
        foreach ($new_points as $q_id => $pts) {
            $sql_upd = "UPDATE user_answers SET points_awarded = ? WHERE user_id = ? AND olympiad_id = ? AND question_id = ?";
            $pdo->prepare($sql_upd)->execute([$pts, $user_id, $olympiad_id, $q_id]);
        }

        $stmt_calc = $pdo->prepare("SELECT SUM(points_awarded) FROM user_answers WHERE user_id = ? AND olympiad_id = ?");
        $stmt_calc->execute([$user_id, $olympiad_id]);
        $total_score = $stmt_calc->fetchColumn() ?: 0;

        // ОНОВЛЕННЯ: Тепер ми також ставимо is_checked = 1
        $pdo->prepare("UPDATE results SET total_score = ?, is_checked = 1 WHERE id = ?")->execute([$total_score, $result_id]);
        
        $stmt_info = $pdo->prepare("
            SELECT u.full_name, u.username AS email, o.title 
            FROM users u 
            JOIN olympiads o ON o.id = ? 
            WHERE u.id = ?
        ");
        $stmt_info->execute([$olympiad_id, $user_id]);
        $info = $stmt_info->fetch();

        $stmt_max = $pdo->prepare("SELECT SUM(points) FROM questions WHERE olympiad_id = ?");
        $stmt_max->execute([$olympiad_id]);
        $max_points = $stmt_max->fetchColumn() ?: 0;

        require_once '../includes/mail_config.php';
        
        $mail_status = sendOlympiadResultEmail($info['email'], $info['full_name'], $info['title'], $total_score, $max_points);
        
        if ($mail_status === true) {
            $message = "Оцінки успішно оновлено! Загальний бал: {$total_score}. Лист з результатом відправлено студенту.";
        } else {
            $message = "Оцінки збережено (Бал: {$total_score}), але сталася помилка відправки листа: " . $mail_status;
        }
        
        // Блокуємо інтерфейс для поточного перегляду (щоб кнопка одразу зникла)
        $is_already_checked = true;
    }
}

// 3. Отримуємо питання та відповіді
$sql_qa = "SELECT q.id as q_id, q.question_text, q.question_type, q.points as max_points, q.requires_manual_check,
                  ua.answer_text, ua.points_awarded, ua.option_id,
                  o.option_text as selected_option_text
           FROM questions q
           LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.user_id = ?
           LEFT JOIN options o ON ua.option_id = o.id
           WHERE q.olympiad_id = ?";
$stmt_qa = $pdo->prepare($sql_qa);
$stmt_qa->execute([$user_id, $olympiad_id]);
$qa_list = $stmt_qa->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Перевірка роботи №<?= $result_id ?></h3>
        <a href="submissions.php?id=<?= $olympiad_id ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Назад</a>
    </div>

    <?php if($message): ?>
        <div class="alert <?= strpos($message, 'Помилка') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show" role="alert">
            <i class="bi <?= strpos($message, 'Помилка') !== false ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' ?>"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($is_already_checked): ?>
        <div class="alert alert-warning shadow-sm border-warning">
            <i class="bi bi-lock-fill"></i> <strong>Ця робота вже перевірена.</strong> Оцінки є остаточними та не підлягають змінам. Лист із результатом уже надіслано студенту.
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="user_id" value="<?= $user_id ?>">
        <input type="hidden" name="olympiad_id" value="<?= $olympiad_id ?>">

        <?php foreach($qa_list as $index => $item): ?>
            <?php 
                $was_manual = (bool)$item['requires_manual_check']; 
                $is_editable = $was_manual && !$is_already_checked; // Можна редагувати, якщо ще не перевірено
            ?>
            <div class="card mb-3 <?= $is_editable ? 'border-primary' : 'border-secondary opacity-75' ?>">
                <div class="card-header d-flex justify-content-between align-items-center <?= $is_editable ? 'bg-primary text-white' : 'bg-body-tertiary' ?>">
                    <div>
                        <strong>Питання <?= $index + 1 ?></strong>
                        <span class="ms-2 badge <?= $is_editable ? 'bg-body-tertiary text-primary' : 'bg-secondary' ?> border">
                            <?= $item['question_type'] ?>
                        </span>
                    </div>
                    <?php if($was_manual && !$is_already_checked): ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill"></i> Потребує перевірки</span>
                    <?php elseif($was_manual && $is_already_checked): ?>
                        <span class="badge bg-info text-dark"><i class="bi bi-check2-all"></i> Перевірено фахівцем</span>
                    <?php else: ?>
                        <span class="badge bg-success"><i class="bi bi-robot"></i> Перевірено автоматично</span>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    <p class="mb-3"><?= nl2br(htmlspecialchars($item['question_text'])) ?></p>
                    
                    <div class="p-3 bg-body-tertiary border rounded mb-3">
                        <strong class="text-muted">Відповідь студента:</strong><br>
                        <?php if($item['question_type'] == 'text'): ?>
                            <span class="fst-italic fs-5"><?= htmlspecialchars($item['answer_text'] ?? 'Немає відповіді') ?></span>
                        <?php else: ?>
                            <span class="fs-5"><?= htmlspecialchars($item['selected_option_text'] ?? 'Не обрано') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="row align-items-center bg-body-tertiary p-2 rounded">
                        <div class="col-auto">
                            <label class="col-form-label fw-bold">Отриманий бал:</label>
                        </div>
                        <div class="col-auto">
                            <?php if($is_editable): ?>
                                <input type="number" step="0.5" name="points[<?= $item['q_id'] ?>]" 
                                       class="form-control border-primary fw-bold text-primary" style="width: 100px;"
                                       value="<?= $item['points_awarded'] ?>" 
                                       max="<?= $item['max_points'] ?>" min="0">
                            <?php else: ?>
                                <span class="fs-4 fw-bold text-success"><?= (float)$item['points_awarded'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <span class="text-muted">з макс. <?= $item['max_points'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(!$is_already_checked): ?>
            <div class="sticky-bottom bg-body-tertiary p-3 border-top shadow-lg rounded">
                <button type="submit" class="btn btn-success btn-lg w-100"><i class="bi bi-save"></i> Зберегти оцінки</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php include '../includes/footer.php'; ?>