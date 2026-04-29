<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Location: ventes.php');
    exit;
}

$id = (int)$_POST['id'];

// Vérifier que la vente existe et est validée
$stmt = mysqli_prepare($conn, "SELECT statut FROM ventes WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$vente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$vente || $vente['statut'] !== 'validée') {
    header('Location: ventes.php?error=annulation_impossible');
    exit;
}

mysqli_begin_transaction($conn);
try {
    // Remettre le stock pour chaque détail
    $stmt = mysqli_prepare($conn, "SELECT article_id, quantite FROM details_vente WHERE vente_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $details = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    foreach ($details as $d) {
        $stmt2 = mysqli_prepare($conn, "UPDATE articles SET quantite = quantite + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt2, "ii", $d['quantite'], $d['article_id']);
        mysqli_stmt_execute($stmt2);
    }

    // Marquer la vente comme annulée
    $stmt3 = mysqli_prepare($conn, "UPDATE ventes SET statut = 'annulée' WHERE id = ?");
    mysqli_stmt_bind_param($stmt3, "i", $id);
    mysqli_stmt_execute($stmt3);

    mysqli_commit($conn);
    $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Vente annulée et stock restitué.'];
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Erreur lors de l\'annulation.'];
}

header('Location: ventes.php');
exit;