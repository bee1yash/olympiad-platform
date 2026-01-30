<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'specialist') {
    header("Location: ../login.php"); exit;
}
?>

<?php include '../includes/header.php'; ?>
<div class="container mt-4">
    <h1>Кабінет Фахівця</h1>
    <p>Вітаємо, колего <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>!</p>
    
    <div class="alert alert-info">
        Тут будуть інструменти для створення тестів та перевірки робіт учасників.
    </div>
</div>
<?php include '../includes/footer.php'; ?>