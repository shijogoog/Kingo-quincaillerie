<?php
// Ce script peut être appelé automatiquement chaque jour (cron)
// ou affiché dans l'interface utilisateur.

session_start();
require_once 'config.php';
require_once 'database.php';

// Fonction pour envoyer un email (à configurer avec votre serveur)
function envoyerEmail($destinataire, $sujet, $message) {
    // Exemple avec mail() – à adapter (SMTP recommandé)
    $headers = "From: no-reply@quincastore.tg\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($destinataire, $sujet, $message, $headers);
}

// Vérifier les échéances dans les 7 prochains jours
$stmt = mysqli_prepare($conn, "
    SELECT * FROM rappels 
    WHERE effectue = 0 AND date_echeance BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY date_echeance ASC
");
mysqli_stmt_execute($stmt);
$echeances = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Récupérer les emails des administrateurs pour notification
$stmt2 = mysqli_prepare($conn, "SELECT email, nom FROM users WHERE role = 'administrateur' AND actif = 1");
mysqli_stmt_execute($stmt2);
$admins = mysqli_fetch_all(mysqli_stmt_get_result($stmt2), MYSQLI_ASSOC);

foreach ($echeances as $e) {
    $message = "<h3>Rappel de paiement</h3>
                <p><strong>Libellé :</strong> " . htmlspecialchars($e['libelle']) . "</p>
                <p><strong>Échéance :</strong> " . date('d/m/Y', strtotime($e['date_echeance'])) . "</p>
                <p><strong>Montant :</strong> " . number_format($e['montant'], 0, ',', ' ') . " FCFA</p>
                <p><strong>Description :</strong> " . nl2br(htmlspecialchars($e['description'])) . "</p>
                <p><a href='https://votredomaine.com/tresorerie.php'>Voir dans l'application</a></p>";
    
    foreach ($admins as $admin) {
        envoyerEmail($admin['email'], "Rappel : " . $e['libelle'], $message);
    }
    
    // Optionnel : marquer comme notifié (sans marquer effectué pour ne pas renvoyer)
    // On peut ajouter un champ date_dernier_rappel.
}