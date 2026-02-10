<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'specialist') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$olympiad_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT title FROM olympiads WHERE id = ?");
$stmt->execute([$olympiad_id]);
$olympiad = $stmt->fetch();

if (!$olympiad) {
    die("Олімпіаду не знайдено.");
}

$stmt_q = $pdo->prepare("SELECT * FROM questions WHERE olympiad_id = ? ORDER BY id ASC");
$stmt_q->execute([$olympiad_id]);
$questions = $stmt_q->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Олімпіади</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($olympiad['title']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Питання: <?= htmlspecialchars($olympiad['title']) ?></h3>
        <a href="add_question.php?olympiad_id=<?= $olympiad_id ?>" class="btn btn-primary">
            + Додати питання
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if(count($questions) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Питання</th>
                            <th>Тип</th>
                            <th>Бали</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($questions as $q): ?>
                            <tr>
                                <td><?= $q['id'] ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($q['question_text'], 0, 50, "...")) ?></td>
                                <td>
                                    <?php 
                                        if($q['question_type'] == 'single') echo '<span class="badge bg-success">Один з багатьох</span>';
                                        elseif($q['question_type'] == 'multiple') echo '<span class="badge bg-warning text-dark">Багато з багатьох</span>';
                                        else echo '<span class="badge bg-secondary">Текст</span>';
                                    ?>
                                </td>
                                <td><?= $q['points'] ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-danger">Видалити</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted">Питань поки немає. Додайте перше!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>