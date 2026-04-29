<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur']); // Seul admin peut supprimer

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: articles.php');
    exit;
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Erreur CSRF');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Vérifier existence article
$stmt = mysqli_prepare($conn, "SELECT id FROM articles WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) == 0) {
    header('Location: articles.php?error=notfound');
    exit;
}

// Vérifier s'il y a des ventes liées
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as nb FROM details_vente WHERE article_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$nb = mysqli_fetch_assoc($result)['nb'];
if ($nb > 0) {
    header('Location: articles.php?error=has_sales');
    exit;
}

// Suppression
$stmt = mysqli_prepare($conn, "DELETE FROM articles WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
if (mysqli_stmt_execute($stmt)) {
    header('Location: articles.php?success=deleted');
} else {
    header('Location: articles.php?error=delete_failed');
}
exit;