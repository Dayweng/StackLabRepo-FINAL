<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';
//#endregion

//#region INSERT STUDENT
$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email']    ?? '') ?: null;
$phone     = trim($_POST['phone']    ?? '') ?: null;
$address   = trim($_POST['address']  ?? '') ?: null;

if ($full_name) {
    $pdo->prepare('INSERT INTO students (full_name, email, phone, address) VALUES (?, ?, ?, ?)')
        ->execute([$full_name, $email, $phone, $address]);

    $sid = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO student_total_payment (student_id, total_billed, total_paid) VALUES (?, 0, 0)')
        ->execute([$sid]);
}
header('Location: ../dashboard.php?tab=students');
exit;
//#endregion
