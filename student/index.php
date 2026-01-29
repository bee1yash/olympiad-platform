<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit;
}

$user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM results r WHERE r.olympiad_id = o.id AND r.user_id = ?) as is_passed
        FROM olympiads o 
        ORDER BY o.start_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$olympiads = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <h2>Вітаємо, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h2>
    <p class="text-muted">Оберіть олімпіаду для проходження.</p>

    <div class="row">
        <?php foreach($olympiads as $olymp): ?>
            <?php
                $status = '';
                $btn_class = 'btn-primary';
                $btn_text = 'Розпочати тест';
                $disabled = '';
                $link = "take_test.php?id=" . $olymp['id'];

                if ($olymp['is_passed'] > 0) {
                    $status = '<span class="badge bg-success">Пройдено</span>';
                    $btn_text = 'Переглянути результат';
                    $btn_class = 'btn-secondary';
                    $link = "result.php?id=" . $olymp['id'];
                } elseif ($current_time < $olymp['start_time']) {
                    $status = '<span class="badge bg-warning text-dark">Ще не почалась</span>';
                    $btn_text = 'Чекайте початку';
                    $btn_class = 'btn-secondary';
                    $disabled = 'disabled';
                } elseif ($current_time > $olymp['end_time']) {
                    $status = '<span class="badge bg-danger">Завершено</span>';
                    $btn_text = 'Час вичерпано';
                    $btn_class = 'btn-secondary';
                    $disabled = 'disabled';
                } else {
                    $status = '<span class="badge bg-primary">Активна</span>';
                }
            ?>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= htmlspecialchars($olymp['title']) ?></h5>
                        <?= $status ?>
                    </div>
                    <div class="card-body">
                        <p><?= htmlspecialchars($olymp['description']) ?></p>
                        <ul class="list-unstyled">
                            <li><strong>Початок:</strong> <?= $olymp['start_time'] ?></li>
                            <li><strong>Кінець:</strong> <?= $olymp['end_time'] ?></li>
                            <li><strong>Час на тест:</strong> <?= $olymp['time_limit_minutes'] ?> хв.</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="<?= $link ?>" class="btn <?= $btn_class ?> w-100 <?= $disabled ?>">
                            <?= $btn_text ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if(empty($olympiads)): ?>
            <p>Наразі немає доступних олімпіад.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>