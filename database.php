<?php
/**
 * Connexion à la base de données avec mysqli
 * Fournit la variable $conn utilisable dans toutes les pages
 */

require_once 'config.php';

// Connexion
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
/**
 * Récupère la liste de tous les fournisseurs triés par nom
 * @return array Liste des fournisseurs (tableau associatif)
 */
function getFournisseurs() {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT id, nom FROM fournisseurs ORDER BY nom");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}
// Vérification
if (!$conn) {
    die('Erreur de connexion : ' . mysqli_connect_error());
}

// Définir le charset
if (!mysqli_set_charset($conn, DB_CHARSET)) {
    die('Erreur de chargement du charset utf8mb4 : ' . mysqli_error($conn));
}

// Pour éviter les problèmes de timezone (optionnel)
mysqli_query($conn, "SET time_zone = '+01:00'");

// Note : $conn est maintenant disponible globalement