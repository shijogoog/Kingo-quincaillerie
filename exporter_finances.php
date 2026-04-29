<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);

// Paramètres de filtrage (mois, année)
$mois = isset($_GET['mois']) ? (int)$_GET['mois'] : date('m');
$annee = isset($_GET['annee']) ? (int)$_GET['annee'] : date('Y');
$type = $_GET['type'] ?? 'transactions'; // transactions, ventes, depenses

$date_debut = "$annee-$mois-01";
$date_fin = date('Y-m-t', strtotime($date_debut));

// Nom du fichier
$filename = "export_{$type}_{$annee}_{$mois}.csv";

// En-têtes HTTP pour forcer le téléchargement CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Créer le fichier en sortie
$output = fopen('php://output', 'w');

// Ajouter BOM UTF-8 pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type == 'transactions') {
    // En-têtes colonnes
    fputcsv($output, ['Date', 'Type', 'Catégorie', 'Description', 'Montant (FCFA)', 'Créé par']);
    
    $stmt = mysqli_prepare($conn, "
        SELECT t.date_transaction, t.type, t.categorie, t.description, t.montant, u.nom as created_by
        FROM transactions t
        LEFT JOIN users u ON u.id = t.created_by
        WHERE DATE(t.date_transaction) BETWEEN ? AND ?
        ORDER BY t.date_transaction DESC
    ");
    mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            date('d/m/Y', strtotime($row['date_transaction'])),
            ucfirst($row['type']),
            ucfirst(str_replace('_', ' ', $row['categorie'])),
            $row['description'],
            number_format($row['montant'], 0, ',', ' '),
            $row['created_by']
        ]);
    }
}
elseif ($type == 'ventes') {
    fputcsv($output, ['ID', 'Date', 'Client', 'Vendeur', 'Montant', 'Statut']);
    
    $stmt = mysqli_prepare($conn, "
        SELECT v.id, v.date_vente, v.client_nom, u.nom as vendeur, v.montant_total, v.statut
        FROM ventes v
        JOIN users u ON u.id = v.utilisateur_id
        WHERE DATE(v.date_vente) BETWEEN ? AND ?
        ORDER BY v.date_vente DESC
    ");
    mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id'],
            date('d/m/Y H:i', strtotime($row['date_vente'])),
            $row['client_nom'] ?: 'Anonyme',
            $row['vendeur'],
            number_format($row['montant_total'], 0, ',', ' '),
            $row['statut']
        ]);
    }
}
elseif ($type == 'depenses') {
    fputcsv($output, ['Date', 'Catégorie', 'Description', 'Montant']);
    
    $stmt = mysqli_prepare($conn, "
        SELECT date_transaction, categorie, description, montant
        FROM transactions
        WHERE type = 'depense' AND DATE(date_transaction) BETWEEN ? AND ?
        ORDER BY date_transaction DESC
    ");
    mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            date('d/m/Y', strtotime($row['date_transaction'])),
            ucfirst(str_replace('_', ' ', $row['categorie'])),
            $row['description'],
            number_format($row['montant'], 0, ',', ' ')
        ]);
    }
}

fclose($output);
exit;