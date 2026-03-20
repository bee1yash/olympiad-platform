<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_olympiad'])) {
    $olymp_id = (int)$_POST['olympiad_id'];
    
    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM user_answers WHERE olympiad_id = ?")->execute([$olymp_id]);
        
        $pdo->prepare("DELETE FROM results WHERE olympiad_id = ?")->execute([$olymp_id]);
        
        $pdo->prepare("DELETE FROM options WHERE question_id IN (SELECT id FROM questions WHERE olympiad_id = ?)")->execute([$olymp_id]);
        
        $pdo->prepare("DELETE FROM questions WHERE olympiad_id = ?")->execute([$olymp_id]);
        
        $pdo->prepare("DELETE FROM olympiads WHERE id = ?")->execute([$olymp_id]);

        $pdo->commit();
        $message = "Олімпіаду та всі пов'язані з нею дані успішно видалено.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Помилка видалення: " . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM olympiads ORDER BY id DESC");
$olympiads = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Панель Адміністратора</h2>
        <div>
            <a href="users.php" class="btn btn-primary me-2">Управління користувачами</a>
            <a href="create_olympiad.php" class="btn btn-success">
                + Створити олімпіаду
            </a>
        </div>
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
            <?php if(count($olympiads) > 0): ?>
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-active">
                        <tr>
                            <th>ID</th>
                            <th>Назва</th>
                            <th>Час початку</th>
                            <th>Ліміт (хв)</th>
                            <th class="text-center">Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($olympiads as $olympiad): ?>
                            <tr>
                            </td>
                                <td><?= $olympiad['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($olympiad['title']) ?></strong>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($olympiad['start_time'])) ?></td>
                                <td><?= $olympiad['time_limit_minutes'] ?></td>
                                <td class="text-end">
    <a href="olympiad_report.php?id=<?= $olympiad['id'] ?>" class="btn btn-sm btn-info text-white">
        <i class="bi bi-bar-chart-fill"></i> Звіт
    </a>
    
    <a href="edit_olympiad.php?id=<?= $olympiad['id'] ?>" class="btn btn-sm btn-warning">
        <i class="bi bi-pencil"></i> Редагувати
    </a>
    <form method="POST" class="d-inline" onsubmit="return confirm('Ви впевнені? Це безповоротно видалить олімпіаду, всі питання та результати студентів!');">
        <input type="hidden" name="olympiad_id" value="<?= $olympiad['id'] ?>">
        <button type="submit" name="delete_olympiad" class="btn btn-sm btn-danger">
            <i class="bi bi-trash"></i> Видалити
        </button>
    </form>
</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center mb-0">Поки що немає жодної олімпіади.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>