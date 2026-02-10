<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'specialist') {
    header("Location: ../login.php"); exit;
}

$stmt = $pdo->query("SELECT * FROM olympiads ORDER BY start_time DESC");
$olympiads = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-briefcase"></i> Кабінет Фахівця</h2>
        <span class="text-muted">Ви не можете створювати олімпіади, але можете керувати питаннями.</span>
    </div>

    <div class="row">
        <?php foreach($olympiads as $olymp): ?>
            <?php 
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM results WHERE olympiad_id = ? AND finished_at IS NOT NULL");
                $stmt_count->execute([$olymp['id']]);
                $submission_count = $stmt_count->fetchColumn();
            ?>

            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-start border-4 border-info">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($olymp['title']) ?></h5>
                        <p class="card-text text-truncate"><?= htmlspecialchars($olymp['description']) ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted"><i class="bi bi-people"></i> Здано робіт: <strong><?= $submission_count ?></strong></small>
                            
                            <div class="btn-group">
                                <a href="questions.php?id=<?= $olymp['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-list-check"></i> Питання
                                </a>
                                
                                <a href="submissions.php?id=<?= $olymp['id'] ?>" class="btn btn-success btn-sm">
                                    <i class="bi bi-check2-all"></i> Перевірка
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>