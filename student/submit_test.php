<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

$user_id = $_SESSION['user_id'];
$olympiad_id = (int)$_POST['olympiad_id'];
$answers = isset($_POST['answers']) ? $_POST['answers'] : [];

$stmt_check = $pdo->prepare("SELECT id FROM results WHERE user_id = ? AND olympiad_id = ? AND finished_at IS NOT NULL");
$stmt_check->execute([$user_id, $olympiad_id]);
if ($stmt_check->fetch()) {
    die("Ви вже здали цю олімпіаду.");
}

$total_score = 0;

$stmt_q = $pdo->prepare("SELECT * FROM questions WHERE olympiad_id = ?");
$stmt_q->execute([$olympiad_id]);
$questions = $stmt_q->fetchAll();

foreach ($questions as $q) {
    $q_id = $q['id'];
    $points = $q['points'];
    $user_ans = isset($answers[$q_id]) ? $answers[$q_id] : null;
    $points_awarded = 0;
    
    $sql_save = "INSERT INTO user_answers (user_id, olympiad_id, question_id, answer_text, option_id, points_awarded) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_save = $pdo->prepare($sql_save);

    if ($q['question_type'] === 'single') {
        $option_id = (int)$user_ans;
        
        $stmt_correct = $pdo->prepare("SELECT is_correct FROM options WHERE id = ?");
        $stmt_correct->execute([$option_id]);
        $is_correct = $stmt_correct->fetchColumn();
        
        if ($is_correct) {
            $points_awarded = $points;
        }
        $stmt_save->execute([$user_id, $olympiad_id, $q_id, null, $option_id, $points_awarded]);

    } elseif ($q['question_type'] === 'multiple') {
        if (is_array($user_ans)) {
            foreach($user_ans as $opt_id) {
                 $stmt_save->execute([$user_id, $olympiad_id, $q_id, null, $opt_id, 0]);
            }
        }

    } elseif ($q['question_type'] === 'text') {
        $text_ans = trim($user_ans);
        
        $stmt_correct = $pdo->prepare("SELECT option_text FROM options WHERE question_id = ? AND is_correct = 1");
        $stmt_correct->execute([$q_id]);
        $correct_db = $stmt_correct->fetchColumn();

        if (mb_strtolower($text_ans) == mb_strtolower(trim($correct_db))) {
            $points_awarded = $points;
        }
        
        $stmt_save->execute([$user_id, $olympiad_id, $q_id, $text_ans, null, $points_awarded]);
    }
    
    $total_score += $points_awarded;
}

$stmt_update = $pdo->prepare("UPDATE results SET finished_at = NOW(), total_score = ? WHERE user_id = ? AND olympiad_id = ?");
$stmt_update->execute([$total_score, $user_id, $olympiad_id]);

if ($stmt_update->rowCount() == 0) {
    $pdo->prepare("INSERT INTO results (user_id, olympiad_id, started_at, finished_at, total_score) VALUES (?, ?, NOW(), NOW(), ?)")
        ->execute([$user_id, $olympiad_id, $total_score]);
}

header("Location: index.php");