<?php

session_start();
require '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM olympiads ORDER BY id DESC");
$olympiads = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Панель Адміністратора</h2>
        <a href="create_olympiad.php" class="btn btn-success">
            + Створити олімпіаду
        </a>
        <a href="users.php" class="btn btn-primary mb-3">Управління користувачами</a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if(count($olympiads) > 0): ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва</th>
                            <th>Час початку</th>
                            <th>Ліміт (хв)</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($olympiads as $olympiad): ?>
                            <tr>
                                <td><?= $olympiad['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($olympiad['title']) ?></strong>
                                </td>
                                <td><?= $olympiad['start_time'] ?></td>
                                <td><?= $olympiad['time_limit_minutes'] ?></td>
                                <td>
                                    <a href="edit_olympiad.php?id=<?= $olympiad['id'] ?>" class="btn btn-sm btn-warning">Редагувати</a>
                                    <a href="#" class="btn btn-sm btn-danger">Видалити</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center">Поки що немає жодної олімпіади.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>