<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php?tab=profile');
    exit;
}
//#endregion

//#region VERIFY PASSWORD
$password = $_POST['confirm_delete_password'] ?? '';

$stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: ../dashboard.php?tab=profile&error=wrongdelete');
    exit;
}
//#endregion

//#region DELETE + REDIRECT
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);

session_destroy();
header('Location: ../login.html?deleted=1');
exit;
//#endregion
