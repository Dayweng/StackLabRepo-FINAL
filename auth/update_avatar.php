<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';

$user_id    = $_SESSION['user_id'];
$upload_dir = __DIR__ . '/../uploads/avatars/';
$redirect   = '../dashboard.php?tab=profile';
//#endregion

//#region VALIDATE UPLOAD
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ' . $redirect . '&error=upload'); exit;
}

$file     = $_FILES['avatar'];
$allowed  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$max_size = 2 * 1024 * 1024;

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

if (!array_key_exists($mime, $allowed)) {
    header('Location: ' . $redirect . '&error=filetype'); exit;
}
if ($file['size'] > $max_size) {
    header('Location: ' . $redirect . '&error=filesize'); exit;
}
//#endregion

//#region MOVE FILE + SAVE PATH
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$stmt = $pdo->prepare('SELECT avatar FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$old_path = $stmt->fetchColumn();
if ($old_path) {
    $old_file = __DIR__ . '/../' . $old_path;
    if (file_exists($old_file)) unlink($old_file);
}

$ext      = $allowed[$mime];
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
$dest     = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    header('Location: ' . $redirect . '&error=upload'); exit;
}

$db_path = 'uploads/avatars/' . $filename;
$pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$db_path, $user_id]);

header('Location: ' . $redirect . '&success=avatar');
exit;
//#endregion
