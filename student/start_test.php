<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php"); exit;
}

$user_id = $_SESSION['user_id'];
$olympiad_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT id FROM results WHERE user_id = ? AND olympiad_id = ?");
$stmt->execute([$user_id, $olympiad_id]);

if ($stmt->rowCount() == 0) {
    $sql = "INSERT INTO results (user_id, olympiad_id, started_at, total_score) VALUES (?, ?, NOW(), 0)";
    $pdo->prepare($sql)->execute([$user_id, $olympiad_id]);
}

header("Location: take_test.php?id=" . $olympiad_id);
exit;