<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

$id = (int)$_GET['id'];

$stmt_olymp = $pdo->prepare("SELECT title FROM olympiads WHERE id = ?");
$stmt_olymp->execute([$id]);
$olympiad = $stmt_olymp->fetch();

$sql = "SELECT u.full_name, u.username, r.total_score, r.started_at, r.finished_at 
        FROM results r
        JOIN users u ON r.user_id = u.id
        WHERE r.olympiad_id = ?
        ORDER BY r.total_score DESC, r.finished_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$results = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Змагання</a></li>
            <li class="breadcrumb-item active">Результати</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Результати: <?= htmlspecialchars($olympiad['title']) ?></h3>
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> Друкувати / PDF
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if(count($results) > 0): ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Місце</th>
                            <th>ПІБ Студента</th>
                            <th>Логін</th>
                            <th>Бали</th>
                            <th>Початок</th>
                            <th>Завершення</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td class="fw-bold fs-5 text-center"><?= $row['total_score'] ?></td>
                                <td><?= $row['started_at'] ?></td>
                                <td><?= $row['finished_at'] ?? '<span class="text-danger">Ще пише...</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted py-4">Ще ніхто не проходив це змагання.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>