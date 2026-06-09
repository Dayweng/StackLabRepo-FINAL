<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';
//#endregion

//#region UPDATE ENROLLMENT
$id         = (int) ($_POST['id']         ?? 0);
$student_id = (int) ($_POST['student_id'] ?? 0);
$course_id  = (int) ($_POST['course_id']  ?? 0);
$start_date = trim($_POST['start_date']   ?? '');
$status     = $_POST['status'] ?? 'active';

if ($id && $student_id && $course_id && $start_date) {
    $pdo->prepare('UPDATE enrollments SET student_id=?, course_id=?, start_date=?, status=? WHERE id=?')
        ->execute([$student_id, $course_id, $start_date, $status, $id]);
}
header('Location: ../dashboard.php?tab=enrollments');
exit;
//#endregion
