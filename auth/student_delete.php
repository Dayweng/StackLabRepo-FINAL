<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';
//#endregion

//#region DELETE STUDENT
$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
}
header('Location: ../dashboard.php?tab=students');
exit;
//#endregion
