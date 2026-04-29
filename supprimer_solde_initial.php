<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur']);

if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Erreur CSRF');
}

mysqli_begin_transaction($conn);
try {
    // Supprimer les entrées caisse liées
    $stmt = mysqli_prepare($conn, "DELETE c FROM caisse c INNER JOIN transactions t ON t.id = c.transaction_id WHERE t.categorie = 'solde_initial'");
    mysqli_stmt_execute($stmt);
    // Supprimer les transactions
    $stmt2 = mysqli_prepare($conn, "DELETE FROM transactions WHERE categorie = 'solde_initial'");
    mysqli_stmt_execute($stmt2);
    mysqli_commit($conn);
    $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Solde initial supprimé.'];
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
}
header('Location: tresorerie.php');
exit;