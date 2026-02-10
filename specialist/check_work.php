<?php
session_start();
require '../config/db.php';

if ($_SESSION['role'] !== 'specialist') { header("Location: ../login.php"); exit; }

$result_id = (int)$_GET['result_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_points = $_POST['points']; 
    $total_score = 0;

    foreach ($new_points as $q_id => $pts) {
        $user_id = $_POST['user_id'];
        $olymp_id = $_POST['olympiad_id'];
        
        $sql_upd = "UPDATE user_answers SET points_awarded = ? WHERE user_id = ? AND olympiad_id = ? AND question_id = ?";
        $pdo->prepare($sql_upd)->execute([$pts, $user_id, $olymp_id, $q_id]);
        
        $total_score += $pts;
    }

    $pdo->prepare("UPDATE results SET total_score = ? WHERE id = ?")->execute([$total_score, $result_id]);
    $message = "Оцінку оновлено!";
}

$stmt = $pdo->prepare("SELECT * FROM results WHERE id = ?");
$stmt->execute([$result_id]);
$result = $stmt->fetch();
$user_id = $result['user_id'];
$olympiad_id = $result['olympiad_id'];

$sql_qa = "SELECT q.id as q_id, q.question_text, q.question_type, q.points as max_points, 
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
    <div class="d-flex justify-content-between align-items-center">
        <h3>Перевірка роботи №<?= $result_id ?></h3>
        <a href="submissions.php?id=<?= $olympiad_id ?>" class="btn btn-secondary">Назад до списку</a>
    </div>

    <?php if(isset($message)): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="user_id" value="<?= $user_id ?>">
        <input type="hidden" name="olympiad_id" value="<?= $olympiad_id ?>">

        <?php foreach($qa_list as $index => $item): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <strong>Питання <?= $index + 1 ?> (Макс: <?= $item['max_points'] ?> балів)</strong>
                    <span class="badge bg-light text-dark border"><?= $item['question_type'] ?></span>
                </div>
                <div class="card-body">
                    <p class="mb-2"><?= nl2br(htmlspecialchars($item['question_text'])) ?></p>
                    
                    <div class="p-3 bg-light border rounded mb-3">
                        <strong>Відповідь студента:</strong><br>
                        <?php if($item['question_type'] == 'text'): ?>
                            <span class="fst-italic text-primary"><?= htmlspecialchars($item['answer_text'] ?? 'Немає відповіді') ?></span>
                        <?php else: ?>
                            <span><?= htmlspecialchars($item['selected_option_text'] ?? 'Не обрано') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-auto">
                            <label class="col-form-label fw-bold">Оцінка:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" step="0.5" name="points[<?= $item['q_id'] ?>]" 
                                   class="form-control" style="width: 100px;"
                                   value="<?= $item['points_awarded'] ?>" 
                                   max="<?= $item['max_points'] ?>" min="0">
                        </div>
                        <div class="col-auto">
                            <span class="text-muted">з <?= $item['max_points'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="sticky-bottom bg-white p-3 border-top shadow-lg">
            <button type="submit" class="btn btn-success btn-lg w-100">Зберегти оцінки</button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>