<?php
session_start();
require '../config/db.php';
require '../includes/mail_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action = $_POST['action'];
        $u_id = (int)$_POST['user_id'];
        
        if ($action === 'activate') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            if ($stmt->execute([$u_id])) {
                $message = "Користувача підтверджено.";
                
                $user = $pdo->query("SELECT username, full_name FROM users WHERE id = $u_id")->fetch();
                if ($user && filter_var($user['username'], FILTER_VALIDATE_EMAIL)) {
                    sendNotificationEmail($user['username'], $user['full_name']);
                }
            }

        } elseif ($action === 'delete') {
            try {
                $pdo->beginTransaction();

                $pdo->prepare("DELETE FROM user_answers WHERE user_id = ?")->execute([$u_id]);

                $pdo->prepare("DELETE FROM results WHERE user_id = ?")->execute([$u_id]);

                $pdo->prepare("DELETE FROM olympiads WHERE created_by = ?")->execute([$u_id]);

                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$u_id]);

                $pdo->commit();
                $message = "Користувача успішно видалено.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Помилка видалення: " . $e->getMessage();
            }
        }
    }

    if (isset($_POST['create_specialist'])) {
        $sp_username = trim($_POST['sp_username']);
        $sp_fullname = trim($_POST['sp_fullname']);
        $sp_password = $_POST['sp_password'];
        
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$sp_username]);
        
        if ($check->rowCount() > 0) {
            $error = "Користувач з таким логіном вже існує!";
        } else {
            $pass = password_hash($sp_password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, full_name, password, role, status) VALUES (?, ?, ?, 'specialist', 'active')";
            if ($pdo->prepare($sql)->execute([$sp_username, $sp_fullname, $pass])) {
                $message = "Фахівця створено!";
            }
        }
    }
}

$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' AND role = 'student' ORDER BY id DESC")->fetchAll();
$specialists = $pdo->query("SELECT * FROM users WHERE role = 'specialist' ORDER BY id DESC")->fetchAll();
$active_students = $pdo->query("SELECT * FROM users WHERE status = 'active' AND role = 'student' ORDER BY full_name ASC")->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4 mb-5">
    <h2>Управління користувачами</h2>

    <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-white">Додати Фахівця</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="sp_username" class="form-control" placeholder="Логін (Email)" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="sp_fullname" class="form-control" placeholder="ПІБ" required>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="password" name="sp_password" class="form-control" placeholder="Пароль" required>
                        <button type="submit" name="create_specialist" class="btn btn-primary">Створити</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if(count($pending_users) > 0): ?>
    <div class="card mb-4 border-warning shadow-sm">
        <div class="card-header bg-warning text-dark fw-bold">
            <i class="bi bi-hourglass-split"></i> Очікують підтвердження
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>ПІБ</th><th>Логін</th><th>Дії</th></tr></thead>
                <tbody>
                    <?php foreach($pending_users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-lg"></i> Підтвердити
                                </button>
                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Відхилити та видалити заявку?')">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-light border text-center text-muted mb-4">Нових заявок немає.</div>
    <?php endif; ?>

    <hr class="my-4">

    <div class="row">
        <div class="col-md-6 mb-3">
            <h4><i class="bi bi-person-workspace"></i> Фахівці</h4>
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-dark"><tr><th>ПІБ</th><th>Логін</th><th>Дії</th></tr></thead>
                        <tbody>
                            <?php foreach($specialists as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['full_name']) ?></td>
                                <td><?= htmlspecialchars($s['username']) ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Видалити фахівця? Всі його питання до змагань теж будуть видалені!')">Видалити</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <h4><i class="bi bi-mortarboard"></i> Активні студенти</h4>
            <div class="card">
                <div class="card-body p-0">
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-dark"><tr><th>ПІБ</th><th>Логін</th><th>Дії</th></tr></thead>
                            <tbody>
                                <?php foreach($active_students as $st): ?>
                                <tr>
                                    <td><?= htmlspecialchars($st['full_name']) ?></td>
                                    <td><?= htmlspecialchars($st['username']) ?></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2 justify-content-end">
                                            <input type="hidden" name="user_id" value="<?= $st['id'] ?>">
                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Ви точно хочете видалити цього студента? Всі його результати будуть втрачені!')">
                                                <i class="bi bi-trash"></i> Видалити
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>