<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php"); exit;
}

$result_id = (int)$_GET['id'];

$stmt_res = $pdo->prepare("SELECT * FROM results WHERE id = ?");
$stmt_res->execute([$result_id]);
$result = $stmt_res->fetch();

if (!$result) die("Результат не знайдено.");

$user_id = $result['user_id'];
$olympiad_id = $result['olympiad_id'];

$stmt_olymp = $pdo->prepare("SELECT title FROM olympiads WHERE id = ?");
$stmt_olymp->execute([$olympiad_id]);
$olymp = $stmt_olymp->fetch();

$stmt_user = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

$stmt_ua = $pdo->prepare("SELECT * FROM user_answers WHERE user_id = ? AND olympiad_id = ?");
$stmt_ua->execute([$user_id, $olympiad_id]);
$user_answers_raw = $stmt_ua->fetchAll();

$user_answers = [];
foreach ($user_answers_raw as $ua) {
    $q_id = $ua['question_id'];
    if (!isset($user_answers[$q_id])) {
        $user_answers[$q_id] = [
            'options' => [],
            'text' => $ua['answer_text'],
            'points' => (float)$ua['points_awarded']
        ];
    }
    if ($ua['option_id']) {
        $user_answers[$q_id]['options'][] = $ua['option_id'];
    }
}

$stmt_q = $pdo->prepare("SELECT * FROM questions WHERE olympiad_id = ?");
$stmt_q->execute([$olympiad_id]);
$questions = $stmt_q->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Детальний звіт учасника</h2>
            <p class="text-muted mb-0"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['username']) ?>)</p>
        </div>
        <a href="olympiad_report.php?id=<?= $olympiad_id ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Назад до таблиці
        </a>
    </div>

    <div class="card shadow-sm border-primary mb-4">
        <div class="card-body text-center bg-body-tertiary">
            <h5 class="text-muted mb-2">Загальний бал за змагання: <strong><?= htmlspecialchars($olymp['title']) ?></strong></h5>
            <h1 class="display-4 text-primary fw-bold"><?= (float)$result['total_score'] ?></h1>
            <p class="text-muted mb-0">Час здачі: <?= date('d.m.Y H:i', strtotime($result['finished_at'])) ?></p>
        </div>
    </div>

    <?php foreach($questions as $index => $q): ?>
        <?php 
            $q_id = $q['id'];
            $u_ans = isset($user_answers[$q_id]) ? $user_answers[$q_id] : ['options' => [], 'text' => '', 'points' => 0];
            $is_fully_correct = ($u_ans['points'] == $q['points']);
            $card_border = $u_ans['points'] > 0 ? ($is_fully_correct ? 'border-success' : 'border-warning') : 'border-danger';
        ?>
        <div class="card mb-4 <?= $card_border ?> shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent">
                <strong>Питання <?= $index + 1 ?></strong>
                <span class="badge <?= $u_ans['points'] > 0 ? 'bg-success' : 'bg-danger' ?> fs-6">
                    Отримано: <?= $u_ans['points'] ?> / <?= $q['points'] ?> балів
                </span>
            </div>
            
            <div class="card-body">
                <p class="mb-3 fw-bold fs-5"><?= nl2br(htmlspecialchars($q['question_text'])) ?></p>

                <?php if($q['question_type'] == 'text'): ?>
                    <div class="mb-2">
                        <strong class="text-muted">Відповідь учасника:</strong><br>
                        <div class="p-2 border rounded <?= $u_ans['points'] > 0 ? 'bg-success-subtle border-success' : 'bg-danger-subtle border-danger' ?>">
                            <?= htmlspecialchars($u_ans['text'] ?: '— (немає відповіді) —') ?>
                        </div>
                    </div>
                    
                    <?php 
                        $stmt_opt = $pdo->prepare("SELECT option_text FROM options WHERE question_id = ? AND is_correct = 1");
                        $stmt_opt->execute([$q_id]);
                        $correct_text = $stmt_opt->fetchColumn();
                    ?>
                    <div class="mt-2 text-success">
                        <strong><i class="bi bi-check-circle-fill"></i> Правильна відповідь:</strong> 
                        <?= htmlspecialchars($correct_text) ?>
                    </div>

                <?php else: ?>
                    <?php 
                        $stmt_opt = $pdo->prepare("SELECT * FROM options WHERE question_id = ?");
                        $stmt_opt->execute([$q_id]);
                        $options = $stmt_opt->fetchAll();
                    ?>
                    <ul class="list-group list-group-flush border rounded">
                        <?php foreach($options as $opt): ?>
                            <?php 
                                $is_selected = in_array($opt['id'], $u_ans['options']);
                                $is_correct = $opt['is_correct'];
                                
                                $bg_class = '';
                                $icon = '';
                                
                                if ($is_selected) {
                                    if ($is_correct) {
                                        $bg_class = 'list-group-item-success fw-bold';
                                        $icon = '<i class="bi bi-check-circle-fill text-success ms-2" title="Правильний вибір"></i>';
                                    } else {
                                        $bg_class = 'list-group-item-danger text-decoration-line-through';
                                        $icon = '<i class="bi bi-x-circle-fill text-danger ms-2" title="Помилковий вибір"></i>';
                                    }
                                } elseif ($is_correct) {
                                    $bg_class = 'list-group-item-warning fw-bold border-warning';
                                    $icon = '<i class="bi bi-arrow-left-circle-fill text-warning ms-2" title="Правильний варіант"></i>';
                                }
                            ?>
                            <li class="list-group-item <?= $bg_class ?> d-flex justify-content-between align-items-center bg-body-tertiary">
                                <div>
                                    <?php if($is_selected): ?>
                                        <i class="bi bi-record-circle-fill text-primary me-2"></i>
                                    <?php else: ?>
                                        <i class="bi bi-circle me-2 text-muted"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($opt['option_text']) ?>
                                </div>
                                <?= $icon ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if($q['requires_manual_check']): ?>
                    <div class="mt-3 small text-muted">
                        <i class="bi bi-person-check-fill"></i> <em>Це питання перевірялося (або очікує перевірки) фахівцем.</em>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>