<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tresorerie.php');
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Erreur CSRF');
}

$especes = (float)$_POST['especes'];
$banque  = (float)$_POST['banque'];
$date_initial = $_POST['date_initial'] ?? date('Y-m-d');

// Vérifier qu'un solde initial n'existe pas déjà
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as nb FROM transactions WHERE categorie = 'solde_initial'");
mysqli_stmt_execute($stmt);
$existe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['nb'];
if ($existe > 0) {
    $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Un solde initial a déjà été défini. Supprimez-le si vous voulez le modifier.'];
    header('Location: tresorerie.php');
    exit;
}

mysqli_begin_transaction($conn);
try {
    // Transaction pour espèces
    if ($especes > 0) {
        $stmt1 = mysqli_prepare($conn, "INSERT INTO transactions (type, categorie, montant, description, date_transaction, reference_type, created_by) VALUES ('recette', 'solde_initial', ?, 'Solde initial espèces', ?, 'initial', ?)");
        mysqli_stmt_bind_param($stmt1, "dsi", $especes, $date_initial, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt1);
        $trans_id1 = mysqli_insert_id($conn);

        // Opération caisse
        $stmt_caisse = mysqli_prepare($conn, "INSERT INTO caisse (date_operation, libelle, type, montant, mode, description, transaction_id, created_by) VALUES (?, 'Solde initial espèces', 'depot', ?, 'especes', 'Solde initial', ?, ?)");
        mysqli_stmt_bind_param($stmt_caisse, "sdii", $date_initial, $especes, $trans_id1, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt_caisse);
    }

    // Transaction pour banque
    if ($banque > 0) {
        $stmt2 = mysqli_prepare($conn, "INSERT INTO transactions (type, categorie, montant, description, date_transaction, reference_type, created_by) VALUES ('recette', 'solde_initial', ?, 'Solde initial banque', ?, 'initial', ?)");
        mysqli_stmt_bind_param($stmt2, "dsi", $banque, $date_initial, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt2);
        $trans_id2 = mysqli_insert_id($conn);

        $stmt_caisse2 = mysqli_prepare($conn, "INSERT INTO caisse (date_operation, libelle, type, montant, mode, description, transaction_id, created_by) VALUES (?, 'Solde initial banque', 'depot', ?, 'banque', 'Solde initial', ?, ?)");
        mysqli_stmt_bind_param($stmt_caisse2, "sdii", $date_initial, $banque, $trans_id2, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt_caisse2);
    }

    mysqli_commit($conn);
    $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Solde initial enregistré avec succès.'];
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Erreur : ' . $e->getMessage()];
}

header('Location: tresorerie.php');
exit;