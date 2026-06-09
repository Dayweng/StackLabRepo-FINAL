<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';
//#endregion

//#region UPDATE COURSE
$id          = (int)   ($_POST['id']               ?? 0);
$title       = trim(    $_POST['title']             ?? '');
$topic       = trim(    $_POST['topic']             ?? '') ?: null;
$price       = (float) ($_POST['price_per_month']   ?? 0);
$description = trim(    $_POST['description']       ?? '') ?: null;

if ($id && $title) {
    $pdo->prepare('UPDATE courses SET title=?, topic=?, price_per_month=?, description=? WHERE id=?')
        ->execute([$title, $topic, $price, $description, $id]);
}
header('Location: ../dashboard.php?tab=courses');
exit;
//#endregion
