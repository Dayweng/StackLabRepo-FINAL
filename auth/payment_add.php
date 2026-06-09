<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.html'); exit; }
require_once '../db.php';
//#endregion

//#region INPUT
$enrollment_id = (int)   ($_POST['enrollment_id'] ?? 0);
$amount        = (float) ($_POST['amount']        ?? 0);
$payment_date  = trim(    $_POST['payment_date']  ?? '');
$notes         = trim(    $_POST['notes']         ?? '') ?: null;
//#endregion

//#region INSERT + UPDATE BILLING
if ($enrollment_id && $amount > 0 && $payment_date) {
    $row = $pdo->prepare('SELECT student_id FROM enrollments WHERE id = ?');
    $row->execute([$enrollment_id]);
    $student_id = (int) $row->fetchColumn();

    if ($student_id) {
        $pdo->prepare('INSERT INTO payments (student_id, enrollment_id, amount, payment_date, notes) VALUES (?, ?, ?, ?, ?)')
            ->execute([$student_id, $enrollment_id, $amount, $payment_date, $notes]);

        $pdo->prepare('UPDATE student_total_payment SET total_paid = total_paid + ? WHERE student_id = ?')
            ->execute([$amount, $student_id]);
    }
}
header('Location: ../dashboard.php?tab=payments');
exit;
//#endregion
