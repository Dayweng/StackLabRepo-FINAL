<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';
//#endregion

//#region UPDATE STUDENT
$id        = (int)  ($_POST['id']       ?? 0);
$full_name = trim(   $_POST['full_name'] ?? '');
$email     = trim(   $_POST['email']    ?? '') ?: null;
$phone     = trim(   $_POST['phone']    ?? '') ?: null;
$address   = trim(   $_POST['address']  ?? '') ?: null;

if ($id && $full_name) {
    $pdo->prepare('UPDATE students SET full_name=?, email=?, phone=?, address=? WHERE id=?')
        ->execute([$full_name, $email, $phone, $address, $id]);
}
header('Location: ../dashboard.php?tab=students');
exit;
//#endregion
