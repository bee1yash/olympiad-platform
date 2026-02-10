<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'specialist') { header("Location: ../login.php"); exit; }

$olympiad_id = (int)$_GET['id'];
$olymp = $pdo->prepare("SELECT title FROM olympiads WHERE id = ?");
$olymp->execute([$olympiad_id]);
$olymp_title = $olymp->fetchColumn();

$sql = "SELECT r.* FROM results r WHERE r.olympiad_id = ? AND r.finished_at IS NOT NULL ORDER BY r.finished_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$olympiad_id]);
$submissions = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Кабінет</a></li>
            <li class="breadcrumb-item active">Перевірка: <?= htmlspecialchars($olymp_title) ?></li>
        </ol>
    </nav>

    <h3>Роботи учасників (Анонімно)</h3>
    <p class="text-muted">Імена студентів приховані для об'єктивного оцінювання.</p>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID Роботи</th>
                        <th>Дата здачі</th>
                        <th>Поточний бал</th>
                        <th>Дія</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($submissions as $index => $sub): ?>
                    <tr>
                        <td>
                            <strong>Робота №<?= $sub['id'] ?></strong>
                            <span class="badge bg-secondary ms-2">Анонім</span>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($sub['finished_at'])) ?></td>
                        <td><span class="badge bg-primary fs-6"><?= $sub['total_score'] ?></span></td>
                        <td>
                            <a href="check_work.php?result_id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-eye"></i> Перевірити / Змінити бал
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>