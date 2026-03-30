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
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_question'])) {
    $q_id = (int)$_POST['question_id'];
    
    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM user_answers WHERE question_id = ?")->execute([$q_id]);
        
        $pdo->prepare("DELETE FROM options WHERE question_id = ?")->execute([$q_id]);
        
        $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$q_id]);

        $pdo->commit();
        $message = "Питання успішно видалено.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Помилка видалення: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT title FROM olympiads WHERE id = ?");
$stmt->execute([$olympiad_id]);
$olympiad = $stmt->fetch();

if (!$olympiad) {
    die("Змагання не знайдено.");
}

$stmt_q = $pdo->prepare("SELECT * FROM questions WHERE olympiad_id = ? ORDER BY id ASC");
$stmt_q->execute([$olympiad_id]);
$questions = $stmt_q->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Змагання</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($olympiad['title']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Питання: <?= htmlspecialchars($olympiad['title']) ?></h3>
        <a href="add_question.php?olympiad_id=<?= $olympiad_id ?>" class="btn btn-primary">
            + Додати питання
        </a>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if(count($questions) > 0): ?>
                <table class="table table-hover align-middle">
                    <thead class="table-active">
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
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Ви впевнені? Це видалить питання та всі відповіді до нього!');">
                                        <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                        <button type="submit" name="delete_question" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Видалити
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted mb-0">Питань поки немає. Додайте перше!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>