<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $time_limit = (int) $_POST['time_limit'];
    $created_by = $_SESSION['user_id'];
    $show_answers = isset($_POST['show_answers']) ? 1 : 0;
    $sql = "INSERT INTO olympiads (title, description, start_time, end_time, time_limit_minutes, created_by, show_answers) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$title, $description, $start_time, $end_time, $time_limit, $created_by, $show_answers])) {
        header("Location: index.php");
        exit;
    } else {
        $message = "Помилка при створенні олімпіади.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Нова олімпіада</h4>
                </div>
                <div class="card-body">
                    <?php if($message): ?>
                        <div class="alert alert-danger"><?= $message ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Назва олімпіади</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Опис</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Дата і час початку</label>
                                <input type="datetime-local" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Дата і час завершення</label>
                                <input type="datetime-local" name="end_time" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ліміт часу на проходження (хвилини)</label>
                            <input type="number" name="time_limit" class="form-control" placeholder="Наприклад: 60" required>
                        </div>
                        <div class="form-check form-switch mb-3 p-3 bg-light border rounded">
                            <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="show_answers" name="show_answers" value="1" <?= (isset($olympiad['show_answers']) && $olympiad['show_answers']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-success" for="show_answers">
                                <i class="bi bi-eye-fill"></i> Показувати правильні відповіді студентам після завершення
                            </label>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Скасувати</a>
                            <button type="submit" class="btn btn-success">Створити</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>