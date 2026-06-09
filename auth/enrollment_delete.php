<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';
//#endregion

//#region INPUT
$id         = (int) ($_POST['id']         ?? 0);
$student_id = (int) ($_POST['student_id'] ?? 0);
$course_id  = (int) ($_POST['course_id']  ?? 0);
//#endregion

//#region DELETE + BILLING ADJUSTMENT
if ($id) {
    $pdo->prepare('DELETE FROM enrollments WHERE id = ?')->execute([$id]);

    if ($student_id && $course_id) {
        $price = $pdo->prepare('SELECT price_per_month FROM courses WHERE id = ?');
        $price->execute([$course_id]);
        $p = (float) $price->fetchColumn();

        $pdo->prepare('UPDATE student_total_payment SET total_billed = GREATEST(0, total_billed - ?) WHERE student_id = ?')
            ->execute([$p, $student_id]);
    }
}
header('Location: ../dashboard.php?tab=enrollments');
exit;
//#endregion
