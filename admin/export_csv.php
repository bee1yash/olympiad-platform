<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php"); exit;
}

$olympiad_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT title FROM olympiads WHERE id = ?");
$stmt->execute([$olympiad_id]);
$olympiad = $stmt->fetch();

if (!$olympiad) {
    die("Змагання не знайдено.");
}

$sql = "SELECT u.full_name, u.username, r.started_at, r.finished_at, r.total_score 
        FROM results r
        JOIN users u ON r.user_id = u.id
        WHERE r.olympiad_id = ? AND r.finished_at IS NOT NULL
        ORDER BY r.total_score DESC, r.finished_at ASC";
$stmt_res = $pdo->prepare($sql);
$stmt_res->execute([$olympiad_id]);
$results = $stmt_res->fetchAll(PDO::FETCH_ASSOC);

$safe_title = preg_replace('/[^a-zA-Zа-яА-Я0-9_іІїЇєЄ]/u', '_', $olympiad['title']);
$filename = "Export_" . $safe_title . "_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fputs($output, "\xEF\xBB\xBF");

fputcsv($output, ['Місце', 'ПІБ учасника', 'Логін', 'Початок', 'Завершення', 'Бал'], ';');

$rank = 1;
foreach ($results as $row) {
    fputcsv($output, [
        $rank++,
        $row['full_name'],
        $row['username'],
        date('d.m.Y H:i', strtotime($row['started_at'])),
        date('d.m.Y H:i', strtotime($row['finished_at'])),
        (float)$row['total_score']
    ], ';');
}

fclose($output);
exit;
?>