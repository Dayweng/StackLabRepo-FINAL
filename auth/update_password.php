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

//#region VALIDATE INPUT
$current = $_POST['current_password']  ?? '';
$new     = $_POST['new_password']      ?? '';
$confirm = $_POST['confirm_password']  ?? '';

if (!$current || !$new || !$confirm) {
    header('Location: ../dashboard.php?tab=profile&error=empty');
    exit;
}
if ($new !== $confirm) {
    header('Location: ../dashboard.php?tab=profile&error=mismatch');
    exit;
}
if (strlen($new) < 6) {
    header('Location: ../dashboard.php?tab=profile&error=short');
    exit;
}
//#endregion

//#region VERIFY + UPDATE PASSWORD
$stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($current, $user['password'])) {
    header('Location: ../dashboard.php?tab=profile&error=wrongpass');
    exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
$stmt->execute([$hash, $_SESSION['user_id']]);

header('Location: ../dashboard.php?tab=profile&success=1');
exit;
//#endregion
