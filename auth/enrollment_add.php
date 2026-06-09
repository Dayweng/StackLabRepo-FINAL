<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';
//#endregion

//#region INPUT
$student_id = (int) ($_POST['student_id'] ?? 0);
$course_id  = (int) ($_POST['course_id']  ?? 0);
$start_date = trim($_POST['start_date']   ?? '');
$status     = $_POST['status'] ?? 'active';
//#endregion

//#region INSERT + UPDATE BILLING
if ($student_id && $course_id && $start_date) {
    $pdo->prepare('INSERT INTO enrollments (student_id, course_id, start_date, status) VALUES (?, ?, ?, ?)')
        ->execute([$student_id, $course_id, $start_date, $status]);

    $price = $pdo->prepare('SELECT price_per_month FROM courses WHERE id = ?');
    $price->execute([$course_id]);
    $p = (float) $price->fetchColumn();

    $pdo->prepare('UPDATE student_total_payment SET total_billed = total_billed + ? WHERE student_id = ?')
        ->execute([$p, $student_id]);
}
header('Location: ../dashboard.php?tab=enrollments');
exit;
//#endregion
