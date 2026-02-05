<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php"); exit;
}

$user_id = $_SESSION['user_id'];
$olympiad_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM olympiads WHERE id = ?");
$stmt->execute([$olympiad_id]);
$olympiad = $stmt->fetch();

$stmt_res = $pdo->prepare("SELECT started_at, finished_at FROM results WHERE user_id = ? AND olympiad_id = ?");
$stmt_res->execute([$user_id, $olympiad_id]);
$result = $stmt_res->fetch();

if ($result && $result['finished_at']) {
    die("Ви вже завершили цю олімпіаду.");
}
if (!$result) {
    header("Location: start_test.php?id=" . $olympiad_id); exit;
}


$start_time = strtotime($result['started_at']);

$time_limit_seconds = $olympiad['time_limit_minutes'] * 60;
$personal_deadline = $start_time + $time_limit_seconds;

$global_deadline = strtotime($olympiad['end_time']);

$real_deadline = min($personal_deadline, $global_deadline);

$now = time();

$seconds_left = $real_deadline - $now;

if ($seconds_left <= 0) {
    header("Location: submit_test.php?id=$olympiad_id&auto=1");
    exit;
}

$stmt_q = $pdo->prepare("SELECT * FROM questions WHERE olympiad_id = ?");
$stmt_q->execute([$olympiad_id]);
$questions = $stmt_q->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="sticky-top bg-warning text-dark p-2 text-center fw-bold shadow-sm mb-4" id="timer-bar">
    Залишилось часу: <span id="timer" class="fs-4">00:00</span>
</div>

<div class="container">
    <h2><?= htmlspecialchars($olympiad['title']) ?></h2>
    
    <form id="testForm" action="submit_test.php" method="POST">
        <input type="hidden" name="olympiad_id" value="<?= $olympiad_id ?>">

        <?php foreach($questions as $index => $q): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Питання <?= $index + 1 ?></h5>
                    <p class="card-text"><?= nl2br(htmlspecialchars($q['question_text'])) ?></p>
                    
                    <?php if($q['question_type'] == 'text'): ?>
                        <input type="text" name="answers[<?= $q['id'] ?>]" class="form-control" placeholder="Введіть відповідь">
                    
                    <?php else:  ?>
                        <?php 
                        $stmt_opt = $pdo->prepare("SELECT * FROM options WHERE question_id = ?");
                        $stmt_opt->execute([$q['id']]);
                        $options = $stmt_opt->fetchAll();
                        ?>
                        
                        <?php foreach($options as $opt): ?>
                            <div class="form-check">
                                <?php if($q['question_type'] == 'single'): ?>
                                    <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt['id'] ?>">
                                <?php else: ?>
                                    <input class="form-check-input" type="checkbox" name="answers[<?= $q['id'] ?>][]" value="<?= $opt['id'] ?>">
                                <?php endif; ?>
                                <label class="form-check-label"><?= htmlspecialchars($opt['option_text']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-grid gap-2 mb-5">
            <button type="submit" class="btn btn-success btn-lg">Завершити тест</button>
        </div>
    </form>
</div>

<script>
    let timeLeft = <?= $seconds_left ?>;
    const timerDisplay = document.getElementById('timer');
    const timerBar = document.getElementById('timer-bar');

    const countdown = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(countdown);
            timerDisplay.innerText = "ЧАС ВИЙШОВ!";
            timerBar.classList.replace('bg-warning', 'bg-danger');
            timerBar.classList.add('text-white');
            
            document.getElementById('testForm').submit();
        } else {
            let hours = Math.floor(timeLeft / 3600);
            let minutes = Math.floor((timeLeft % 3600) / 60);
            let seconds = timeLeft % 60;

            let display = 
                (hours > 0 ? (hours < 10 ? "0" + hours : hours) + ":" : "") +
                (minutes < 10 ? "0" + minutes : minutes) + ":" +
                (seconds < 10 ? "0" + seconds : seconds);
            
            timerDisplay.innerText = display;
            timeLeft--;
            
            if (timeLeft < 60) {
                timerBar.classList.remove('bg-warning');
                timerBar.classList.add('bg-danger', 'text-white');
            }
        }
    }, 1000);
</script>

<?php include '../includes/footer.php'; ?>