<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action = $_POST['action'];
        $u_id = (int)$_POST['user_id'];
        
        if ($action === 'activate') $new_status = 'active';
        elseif ($action === 'ban') $new_status = 'banned';
        elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$u_id]);
            header("Location: users.php"); exit;
        }

        if (isset($new_status)) {
            $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new_status, $u_id]);
        }
    }

    if (isset($_POST['create_specialist'])) {
        $username = trim($_POST['sp_username']);
        $fullname = trim($_POST['sp_fullname']);
        $password = password_hash($_POST['sp_password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, 'specialist', 'active')";
        try {
            $pdo->prepare($sql)->execute([$username, $password, $fullname]);
        } catch (PDOException $e) {
            $error = "Помилка: Логін вже зайнятий.";
        }
    }
}

$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' AND role = 'student'")->fetchAll();
$active_students = $pdo->query("SELECT * FROM users WHERE status = 'active' AND role = 'student'")->fetchAll();
$specialists = $pdo->query("SELECT * FROM users WHERE role = 'specialist'")->fetchAll();
$banned_users = $pdo->query("SELECT * FROM users WHERE status = 'banned'")->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <h2>Управління користувачами</h2>
    
    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-white">Додати Фахівця (Журі/Викладач)</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="sp_username" class="form-control" placeholder="Логін" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="sp_fullname" class="form-control" placeholder="ПІБ" required>
                </div>
                <div class="col-md-3">
                    <input type="password" name="sp_password" class="form-control" placeholder="Пароль" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="create_specialist" class="btn btn-primary w-100">Створити</button>
                </div>
            </form>
            <?php if(isset($error)) echo "<p class='text-danger mt-2'>$error</p>"; ?>
        </div>
    </div>

    <?php if(count($pending_users) > 0): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark fw-bold">Очікують підтвердження</div>
        <div class="card-body">
            <table class="table table-sm">
                <thead><tr><th>ПІБ</th><th>Логін</th><th>Дії</th></tr></thead>
                <tbody>
                    <?php foreach($pending_users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">Підтвердити</button>
                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Видалити заявку?')">Відхилити</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <h4>Фахівці системи</h4>
    <table class="table table-bordered">
        <thead><tr><th>ID</th><th>ПІБ</th><th>Логін</th><th>Дії</th></tr></thead>
        <tbody>
            <?php foreach($specialists as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['username']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Видалити</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-4">Активні учасники</h4>
    <table class="table table-striped">
        <thead><tr><th>ПІБ</th><th>Логін</th><th>Дії</th></tr></thead>
        <tbody>
            <?php foreach($active_students as $st): ?>
            <tr>
                <td><?= htmlspecialchars($st['full_name']) ?></td>
                <td><?= htmlspecialchars($st['username']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $st['id'] ?>">
                        <button type="submit" name="action" value="ban" class="btn btn-sm btn-danger">Забанити</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if(count($banned_users) > 0): ?>
        <h4 class="mt-4 text-danger">Заблоковані</h4>
        <table class="table table-dark">
            <?php foreach($banned_users as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b['full_name']) ?></td>
                <td>
                     <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $b['id'] ?>">
                        <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">Розбанити</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>