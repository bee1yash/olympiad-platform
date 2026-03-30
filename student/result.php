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
$olymp = $stmt->fetch();

if (!$olymp) {
    die("Змагання не знайдено.");
}

$stmt_res = $pdo->prepare("SELECT * FROM results WHERE user_id = ? AND olympiad_id = ? AND finished_at IS NOT NULL");
$stmt_res->execute([$user_id, $olympiad_id]);
$result = $stmt_res->fetch();

if (!$result) {
    die("Ви ще не завершили це змагання або результатів немає.");
}

$show_answers = (bool)$olymp['show_answers'];

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
        <h2>Детальний звіт: <?= htmlspecialchars($olymp['title']) ?></h2>
        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> На головну</a>
    </div>

    <div class="card shadow-sm border-primary mb-4">
        <div class="card-body text-center bg-light">
            <h4 class="text-muted mb-2">Ваш загальний бал</h4>
            <h1 class="display-4 text-primary fw-bold"><?= $result['total_score'] ?></h1>
            <p class="text-muted mb-0">Час здачі: <?= date('d.m.Y H:i', strtotime($result['finished_at'])) ?></p>
        </div>
    </div>

    <?php if(!$show_answers): ?>
        <div class="alert alert-info border-info">
            <i class="bi bi-info-circle-fill"></i> Адміністратор приховав правильні відповіді для цього змагання. Ви можете бачити лише свої відповіді та отримані бали.
        </div>
    <?php endif; ?>

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
                    <?= $u_ans['points'] ?> / <?= $q['points'] ?> балів
                </span>
            </div>
            
            <div class="card-body">
                <p class="mb-3 fw-bold fs-5"><?= nl2br(htmlspecialchars($q['question_text'])) ?></p>

                <?php if($q['question_type'] == 'text'): ?>
                    <div class="mb-2">
                        <strong>Ваша відповідь:</strong><br>
                        <div class="p-2 border rounded <?= $u_ans['points'] > 0 ? 'bg-success-subtle border-success' : 'bg-danger-subtle border-danger' ?>">
                            <?= htmlspecialchars($u_ans['text'] ?: '— (немає відповіді) —') ?>
                        </div>
                    </div>
                    
                    <?php if($show_answers): ?>
                        <?php 
                            $stmt_opt = $pdo->prepare("SELECT option_text FROM options WHERE question_id = ? AND is_correct = 1");
                            $stmt_opt->execute([$q_id]);
                            $correct_text = $stmt_opt->fetchColumn();
                        ?>
                        <div class="mt-2 text-success">
                            <strong><i class="bi bi-check-circle-fill"></i> Правильна відповідь:</strong> 
                            <?= htmlspecialchars($correct_text) ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <?php 
                        $stmt_opt = $pdo->prepare("SELECT * FROM options WHERE question_id = ?");
                        $stmt_opt->execute([$q_id]);
                        $options = $stmt_opt->fetchAll();
                    ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($options as $opt): ?>
                            <?php 
                                $is_selected_by_user = in_array($opt['id'], $u_ans['options']);
                                $is_correct_option = $opt['is_correct'];
                                
                                $bg_class = '';
                                $icon = '';
                                
                                if ($is_selected_by_user) {
                                    if ($is_correct_option) {
                                        $bg_class = 'list-group-item-success fw-bold'; 
                                        $icon = '<i class="bi bi-check-circle-fill text-success ms-2"></i>';
                                    } else {
                                        $bg_class = 'list-group-item-danger text-decoration-line-through'; 
                                        $icon = '<i class="bi bi-x-circle-fill text-danger ms-2"></i>';
                                    }
                                } elseif ($show_answers && $is_correct_option) {
                                    $bg_class = 'list-group-item-warning fw-bold border-warning';
                                    $icon = '<i class="bi bi-arrow-left-circle-fill text-warning ms-2" title="Це правильна відповідь"></i>';
                                }
                            ?>
                            <li class="list-group-item <?= $bg_class ?> d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if($is_selected_by_user): ?>
                                        <i class="bi bi-record-circle-fill text-primary me-2" title="Ваш вибір"></i>
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
                        <i class="bi bi-person-check-fill"></i> <em>Це питання перевірялося (або буде перевірено) фахівцем вручну.</em>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
</div>

<?php include '../includes/footer.php'; ?>