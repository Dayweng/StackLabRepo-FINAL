<?php
//#region GUARD
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.html');
    exit;
}
//#endregion

//#region INPUT
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    header('Location: ../login.html?error=empty');
    exit;
}
//#endregion

//#region AUTHENTICATE
$stmt = $pdo->prepare('SELECT id, full_name, email, password FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: ../login.html?error=invalid');
    exit;
}
//#endregion

//#region SESSION + REDIRECT
$_SESSION['user_id']   = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email']     = $user['email'];

header('Location: ../dashboard.php');
exit;
//#endregion
