<?php

session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php"); exit;
}

$olympiad_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM results WHERE user_id = ? AND olympiad_id = ?");
$stmt->execute([$user_id, $olympiad_id]);
$my_result = $stmt->fetch();

if (!$my_result || !$my_result['finished_at']) {
    die("Результат не знайдено або тест ще не завершено.");
}

$stmt_olymp = $pdo->prepare("SELECT title FROM olympiads WHERE id = ?");
$stmt_olymp->execute([$olympiad_id]);
$olympiad = $stmt_olymp->fetch();

$sql_leaderboard = "
    SELECT u.full_name, r.total_score, r.finished_at
    FROM results r
    JOIN users u ON r.user_id = u.id
    WHERE r.olympiad_id = ? AND r.finished_at IS NOT NULL
    ORDER BY r.total_score DESC, r.finished_at ASC
";
$stmt_lb = $pdo->prepare($sql_leaderboard);
$stmt_lb->execute([$olympiad_id]);
$leaderboard = $stmt_lb->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    
    <div class="card text-center mb-5 border-primary">
        <div class="card-header bg-primary text-white">
            <h4>Результати: <?= htmlspecialchars($olympiad['title']) ?></h4>
        </div>
        <div class="card-body">
            <h1 class="display-4 fw-bold text-primary"><?= $my_result['total_score'] ?> балів</h1>
            <p class="card-text">Тест завершено: <?= $my_result['finished_at'] ?></p>
            <a href="index.php" class="btn btn-outline-primary">Повернутися в кабінет</a>
        </div>
    </div>

    <h3 class="mb-3"><i class="bi bi-trophy"></i> Рейтингова таблиця учасників</h3>
    <table class="table table-striped table-hover shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Учасник</th>
                <th>Бали</th>
                <th>Час здачі</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($leaderboard as $index => $row): ?>
                <tr class="<?= ($row['full_name'] == $_SESSION['full_name']) ? 'table-warning fw-bold' : '' ?>">
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= $row['total_score'] ?></td>
                    <td><?= $row['finished_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>