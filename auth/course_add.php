<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';
//#endregion

//#region INSERT COURSE
$title       = trim($_POST['title']           ?? '');
$topic       = trim($_POST['topic']           ?? '') ?: null;
$price       = (float) ($_POST['price_per_month'] ?? 0);
$description = trim($_POST['description']     ?? '') ?: null;

if ($title) {
    $pdo->prepare('INSERT INTO courses (title, topic, price_per_month, description) VALUES (?, ?, ?, ?)')
        ->execute([$title, $topic, $price, $description]);
}
header('Location: ../dashboard.php?tab=courses');
exit;
//#endregion
