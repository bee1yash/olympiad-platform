<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$olympiad_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM olympiads WHERE id = ?");
$stmt->execute([$olympiad_id]);
$olympiad = $stmt->fetch();

if (!$olympiad) die("Олімпіаду не знайдено");

$stmt_res = $pdo->prepare("SELECT * FROM results WHERE user_id = ? AND olympiad_id = ?");
$stmt_res->execute([$user_id, $olympiad_id]);
$result = $stmt_res->fetch();

if (!$result) {
    $stmt_insert = $pdo->prepare("INSERT INTO results (user_id, olympiad_id, started_at) VALUES (?, ?, NOW())");
    $stmt_insert->execute([$user_id, $olympiad_id]);
} else {
    if ($result['finished_at']) {
        die("Ви вже склали цей тест.");
    }
}

$stmt_q = $pdo->prepare("SELECT * FROM questions WHERE olympiad_id = ?");
$stmt_q->execute([$olympiad_id]);
$questions = $stmt_q->fetchAll();

?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4 mb-5">
    <div class="sticky-top bg-white p-3 shadow-sm border-bottom mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h4><?= htmlspecialchars($olympiad['title']) ?></h4>
            <div class="text-danger fw-bold">
                Час пішов! Успіхів!
            </div>
        </div>
    </div>

    <form action="submit_test.php" method="POST">
        <input type="hidden" name="olympiad_id" value="<?= $olympiad_id ?>">

        <?php foreach($questions as $index => $q): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Питання №<?= $index + 1 ?></strong>
                    <span class="badge bg-info float-end"><?= $q['points'] ?> балів</span>
                </div>
                <div class="card-body">
                    <p class="card-text lead"><?= nl2br(htmlspecialchars($q['question_text'])) ?></p>

                    <?php if($q['question_type'] == 'text'): ?>
                        <textarea name="answers[<?= $q['id'] ?>]" class="form-control" rows="3" placeholder="Ваша відповідь..."></textarea>
                    
                    <?php else: ?>
                        <?php
                            $stmt_opt = $pdo->prepare("SELECT * FROM options WHERE question_id = ?");
                            $stmt_opt->execute([$q['id']]);
                            $options = $stmt_opt->fetchAll();
                            
                            $inputType = ($q['question_type'] == 'single') ? 'radio' : 'checkbox';
                            $inputName = "answers[{$q['id']}]" . ($inputType == 'checkbox' ? '[]' : '');
                        ?>
                        
                        <div class="list-group">
                            <?php foreach($options as $opt): ?>
                                <label class="list-group-item list-group-item-action">
                                    <input class="form-check-input me-2" 
                                           type="<?= $inputType ?>" 
                                           name="<?= $inputName ?>" 
                                           value="<?= $opt['id'] ?>">
                                    <?= htmlspecialchars($opt['option_text']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Ви впевнені, що хочете завершити тест?');">
                Завершити та здати роботу
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>