<?php

session_start(); 
require 'config/db.php';

$message = '';
$submitted_username = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $submitted_username = htmlspecialchars($username);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    
    if ($user['status'] === 'pending') {
        $message = "Ваш акаунт ще не підтверджено адміністратором.";
    } elseif ($user['status'] === 'banned') {
        $message = "Ваш акаунт заблоковано. Зверніться до адміністратора.";
    } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        if ($user['role'] === 'admin') {
            header("Location: admin/index.php");
        } elseif ($user['role'] === 'specialist') {
            header("Location: specialist/index.php"); 
        } else {
            header("Location: student/index.php");
        }
        exit;
    }
} else {
    $message = "Невірний логін або пароль!";
}
}
?>
<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card mt-5">
            <div class="card-header text-center">
                <h4>Вхід у систему</h4>
            </div>
            <div class="card-body">
                
                <?php if(!empty($message)): ?>
                    <div class="alert alert-danger"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Логін</label>
                        <input type="text" name="username" class="form-control" value="<?= $submitted_username ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Увійти</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <small>Немає акаунту? <a href="register.php">Зареєструватися</a></small>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>