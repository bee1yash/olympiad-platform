<?php

require 'config/db.php'; 

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    
    $role = 'student'; 

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        $message = "Користувач з таким логіном вже існує!";
    } else {
       $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student'; 
        $status = 'pending'; 
        $sql = "INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$username, $password_hash, $full_name, $role, $status])) {
    $message = "Реєстрація успішна! Ваш акаунт очікує підтвердження адміністратором. Ви не зможете увійти, доки вас не активують.";
    } else {
    $message = "Помилка реєстрації!";
}
    }
}

?>
<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mt-5">
            <div class="card-header text-center">
                <h4>Реєстрація учасника</h4>
            </div>
            <div class="card-body">

                <?php if(!empty($message)): ?>
                    <div class="alert alert-danger"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Логін (Username)</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Повне ім'я (ПІБ)</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Зареєструватися</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <small>Вже є акаунт? <a href="login.php">Увійти</a></small>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>