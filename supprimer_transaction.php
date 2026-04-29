<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur']);

if (!isset($_GET['id']) || !isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Location: tresorerie.php');
    exit;
}

$id = (int)$_GET['id'];

mysqli_begin_transaction($conn);
try {
    // Supprimer d'abord l'entrée dans caisse liée
    $stmt = mysqli_prepare($conn, "DELETE FROM caisse WHERE transaction_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    // Supprimer la transaction
    $stmt2 = mysqli_prepare($conn, "DELETE FROM transactions WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);

    mysqli_commit($conn);
    $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Transaction supprimée.'];
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Erreur lors de la suppression.'];
}
header('Location: tresorerie.php');
exit;