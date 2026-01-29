<?php

session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php"); exit;
}

$user_id = $_SESSION['user_id'];
$olympiad_id = (int)$_POST['olympiad_id'];
$user_answers = isset($_POST['answers']) ? $_POST['answers'] : [];

$stmt_q = $pdo->prepare("SELECT * FROM questions WHERE olympiad_id = ?");
$stmt_q->execute([$olympiad_id]);
$questions = $stmt_q->fetchAll();

$total_score = 0;

foreach ($questions as $q) {
    $q_id = $q['id'];
    $points = $q['points'];
    $type = $q['question_type'];

    if (!isset($user_answers[$q_id])) {
        continue;
    }

    $user_ans = $user_answers[$q_id]; 

    if ($type === 'single') {
        $stmt_check = $pdo->prepare("SELECT is_correct FROM options WHERE id = ?");
        $stmt_check->execute([$user_ans]);
        $is_correct = $stmt_check->fetchColumn();

        if ($is_correct) {
            $total_score += $points;
        }

    } elseif ($type === 'multiple') {
        $stmt_correct = $pdo->prepare("SELECT id FROM options WHERE question_id = ? AND is_correct = 1");
        $stmt_correct->execute([$q_id]);
        $correct_options_db = $stmt_correct->fetchAll(PDO::FETCH_COLUMN); 

        if (is_array($user_ans)) {
            sort($user_ans);
            sort($correct_options_db);

            if ($user_ans == $correct_options_db) {
                $total_score += $points;
            }
        }

    } elseif ($type === 'text') {
        
        $stmt_correct = $pdo->prepare("SELECT option_text FROM options WHERE question_id = ? AND is_correct = 1 LIMIT 1");
        $stmt_correct->execute([$q_id]);
        $correct_answer_db = $stmt_correct->fetchColumn(); 

        $user_clean = mb_strtolower(trim($user_ans));
        $db_clean = mb_strtolower(trim($correct_answer_db));

        if ($correct_answer_db && $user_clean == $db_clean) {
            $total_score += $points;
        }
    }
}

$finished_at = date('Y-m-d H:i:s');

$sql_update = "UPDATE results 
               SET total_score = ?, finished_at = ? 
               WHERE user_id = ? AND olympiad_id = ?";
$stmt_update = $pdo->prepare($sql_update);
$stmt_update->execute([$total_score, $finished_at, $user_id, $olympiad_id]);

header("Location: result.php?id=" . $olympiad_id);
exit;
?>