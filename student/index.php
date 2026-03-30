<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit;
}

$user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

$sql = "SELECT o.*, r.started_at, r.finished_at
        FROM olympiads o 
        LEFT JOIN results r ON o.id = r.olympiad_id AND r.user_id = ?
        ORDER BY o.start_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$olympiads = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <h2>Вітаємо, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h2>
    <p class="text-muted">Оберіть змагання для проходження.</p>

    <div class="row">
        <?php foreach($olympiads as $olymp): ?>
            <?php
                $status = '';
                $btn_class = 'btn-primary';
                $btn_text = 'Розпочати тест';
                $disabled = '';
                $link = "start_test.php?id=" . $olymp['id'];
                $onclick = "return confirm('Розпочати тест? Час піде одразу!')";

                if (!empty($olymp['finished_at'])) {
                    $status = '<span class="badge bg-success">Пройдено</span>';
                    $btn_text = 'Переглянути результат';
                    $btn_class = 'btn-secondary';
                    $link = "result.php?id=" . $olymp['id']; 
                    $onclick = ""; 
                
                } elseif (!empty($olymp['started_at'])) {
                    $status = '<span class="badge bg-warning text-dark">У процесі</span>';
                    $btn_text = 'Продовжити';
                    $btn_class = 'btn-warning';
                    $link = "take_test.php?id=" . $olymp['id']; 
                    $onclick = "";

                } elseif ($current_time < $olymp['start_time']) {
                    $status = '<span class="badge bg-info text-dark">Ще не почалась</span>';
                    $btn_text = 'Чекайте початку';
                    $btn_class = 'btn-secondary';
                    $disabled = 'disabled';
                    $onclick = "";
                } elseif ($current_time > $olymp['end_time']) {
                    $status = '<span class="badge bg-danger">Завершено</span>';
                    $btn_text = 'Час вичерпано';
                    $btn_class = 'btn-secondary';
                    $disabled = 'disabled';
                    $onclick = "";
                } else {
                    $status = '<span class="badge bg-primary">Активна</span>';
                }
            ?>

            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= htmlspecialchars($olymp['title']) ?></h5>
                        <?= $status ?>
                    </div>
                    <div class="card-body">
                        <p><?= htmlspecialchars($olymp['description']) ?></p>
                        <ul class="list-unstyled text-muted small">
                            <li><i class="bi bi-calendar-event"></i> <strong>Початок:</strong> <?= date('d.m.Y H:i', strtotime($olymp['start_time'])) ?></li>
                            <li><i class="bi bi-calendar-x"></i> <strong>Кінець:</strong> <?= date('d.m.Y H:i', strtotime($olymp['end_time'])) ?></li>
                            <li><i class="bi bi-clock"></i> <strong>Ліміт:</strong> <?= $olymp['time_limit_minutes'] ?> хв.</li>
                        </ul>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <a href="<?= $link ?>" class="btn <?= $btn_class ?> w-100 <?= $disabled ?>" onclick="<?= $onclick ?>">
                            <?= $btn_text ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if(empty($olympiads)): ?>
            <div class="col-12">
                <div class="alert alert-info">Наразі немає доступних змагань.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>