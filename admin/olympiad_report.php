<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php"); exit;
}

$olympiad_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM olympiads WHERE id = ?");
$stmt->execute([$olympiad_id]);
$olympiad = $stmt->fetch();

if (!$olympiad) {
    die("Змагання не знайдено.");
}

$stmt_max = $pdo->prepare("SELECT SUM(points) FROM questions WHERE olympiad_id = ?");
$stmt_max->execute([$olympiad_id]);
$max_points = $stmt_max->fetchColumn() ?: 0;

$sql = "SELECT u.full_name, u.username, r.started_at, r.finished_at, r.total_score 
        FROM results r
        JOIN users u ON r.user_id = u.id
        WHERE r.olympiad_id = ? AND r.finished_at IS NOT NULL
        ORDER BY r.total_score DESC, r.finished_at ASC";
$stmt_res = $pdo->prepare($sql);
$stmt_res->execute([$olympiad_id]);
$results = $stmt_res->fetchAll();

$sql_in_progress = "SELECT u.full_name, u.username, r.started_at 
                    FROM results r
                    JOIN users u ON r.user_id = u.id
                    WHERE r.olympiad_id = ? AND r.finished_at IS NULL
                    ORDER BY r.started_at DESC";
$stmt_prog = $pdo->prepare($sql_in_progress);
$stmt_prog->execute([$olympiad_id]);
$in_progress = $stmt_prog->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-trophy-fill text-warning"></i> Звіт: <?= htmlspecialchars($olympiad['title']) ?></h2>
            <p class="text-muted mb-0">Максимально можливий бал: <strong><?= $max_points ?></strong></p>
        </div>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Назад до списку</a>
    </div>

    <div class="card shadow-sm border-primary mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-list-ol"></i> Турнірна таблиця (Завершили)</h5>
        </div>
        <div class="card-body p-0">
            <?php if(count($results) > 0): ?>
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>ПІБ учасника</th>
                            <th>Логін</th>
                            <th>Початок</th>
                            <th>Завершення</th>
                            <th class="text-center text-primary fs-5">Бал</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $index => $row): ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?= $index + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($row['started_at'])) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($row['finished_at'])) ?></td>
                                <td class="text-center fw-bold fs-5 text-success"><?= (float)$row['total_score'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-4 text-center text-muted">
                    Жоден учасник ще не завершив це змагання.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if(count($in_progress) > 0): ?>
    <div class="card shadow-sm border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> В процесі виконання (або не здали роботу)</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ПІБ учасника</th>
                        <th>Логін</th>
                        <th>Час початку</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($in_progress as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($row['started_at'])) ?></td>
                            <td><span class="badge bg-secondary">Тестується</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>