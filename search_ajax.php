<?php
/**
 * search_ajax.php
 * Endpoint AJAX pour la recherche globale dans le header
 * Retourne JSON : { articles, ventes, fournisseurs }
 */
session_start();
require_once 'config.php';
require_once 'database.php';

// Sécurité : doit être connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['articles' => [], 'ventes' => [], 'fournisseurs' => []]);
    exit;
}

$like  = '%' . $q . '%';
$result = ['articles' => [], 'ventes' => [], 'fournisseurs' => []];

// ===== ARTICLES =====
$stmt = mysqli_prepare($conn, "
    SELECT id, nom, categorie, prix_vente, quantite, stock_minimum
    FROM articles
    WHERE nom LIKE ? OR categorie LIKE ?
    ORDER BY nom
    LIMIT 5
");
mysqli_stmt_bind_param($stmt, "ss", $like, $like);
mysqli_stmt_execute($stmt);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
foreach ($rows as $r) {
    $r['prix_vente_fmt'] = number_format($r['prix_vente'], 0, ',', ' ') . ' FCFA';
    $result['articles'][] = $r;
}

// ===== VENTES =====
// Les vendeurs ne voient que leurs propres ventes
$venteWhere = $_SESSION['role'] === 'vendeur' ? "AND v.utilisateur_id = " . (int)$_SESSION['user_id'] : '';
$stmt = mysqli_prepare($conn, "
    SELECT v.id, v.client_nom, v.montant_total, v.date_vente, v.statut
    FROM ventes v
    WHERE (v.client_nom LIKE ? OR CAST(v.id AS CHAR) LIKE ?)
    $venteWhere
    ORDER BY v.date_vente DESC
    LIMIT 4
");
mysqli_stmt_bind_param($stmt, "ss", $like, $like);
mysqli_stmt_execute($stmt);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
foreach ($rows as $r) {
    $r['date_fmt']   = date('d/m/Y', strtotime($r['date_vente']));
    $r['montant_fmt'] = number_format($r['montant_total'], 0, ',', ' ') . ' FCFA';
    $result['ventes'][] = $r;
}

// ===== FOURNISSEURS (admin/gestionnaire seulement) =====
if (in_array($_SESSION['role'], ['administrateur', 'gestionnaire'])) {
    $stmt = mysqli_prepare($conn, "
        SELECT id, nom, telephone, email
        FROM fournisseurs
        WHERE nom LIKE ? OR telephone LIKE ? OR email LIKE ?
        ORDER BY nom
        LIMIT 3
    ");
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result['fournisseurs'] = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);