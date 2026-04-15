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

$sql = "SELECT r.id as result_id, u.full_name, u.username, r.started_at, r.finished_at, r.total_score 
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

<style>
@media print {
    @page { 
        margin: 0; 
        size: auto;
    }
    body { 
        margin: 1.5cm; 
    }
    .container {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
    }
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
    .card-header {
        border-bottom: 1px solid #dee2e6 !important;
    }
}
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-trophy-fill text-warning d-print-none"></i> Звіт: <?= htmlspecialchars($olympiad['title']) ?></h2>
            <p class="text-muted mb-0">Максимально можливий бал: <strong><?= $max_points ?></strong></p>
        </div>
        
        <div class="d-print-none"> 
            <a href="export_csv.php?id=<?= $olympiad_id ?>" class="btn btn-success me-2">
                <i class="bi bi-filetype-csv"></i> Завантажити CSV
            </a>
            <button onclick="window.print()" class="btn btn-danger me-2">
                <i class="bi bi-filetype-pdf"></i> Зберегти як PDF
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-primary mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-list-ol"></i> Таблиця</h5>
        </div>
        <div class="card-body p-0">
            <?php if(count($results) > 0): ?>
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-active">
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>ПІБ учасника</th>
                            <th>Логін</th>
                            <th>Початок</th>
                            <th>Завершення</th>
                            <th class="text-center text-primary fs-5">Бал</th>
                            <th class="text-center d-print-none">Дії</th>
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
                                <td class="text-center d-print-none">
                                    <a href="user_result.php?id=<?= $row['result_id'] ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i> Деталі
                                    </a>
                                </td>
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
    <div class="card shadow-sm border-warning d-print-none">
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