<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erreur CSRF');
    }

    $type = $_POST['type'] ?? 'depense';
    $categorie = $_POST['categorie'] ?? '';
    $montant = (float)($_POST['montant'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $date_transaction = $_POST['date_transaction'] ?? date('Y-m-d');
    $reference_type = $_POST['reference_type'] ?? 'autre';
    $reference_id = !empty($_POST['reference_id']) ? (int)$_POST['reference_id'] : null;
    $mode_caisse = $_POST['mode_caisse'] ?? 'especes';

    if ($montant <= 0) {
        $error = "Le montant doit être supérieur à 0.";
    } elseif (empty($categorie)) {
        $error = "Veuillez sélectionner une catégorie.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // Insertion dans transactions
            $stmt = mysqli_prepare($conn, "INSERT INTO transactions (type, categorie, montant, description, date_transaction, reference_type, reference_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdsssii", $type, $categorie, $montant, $description, $date_transaction, $reference_type, $reference_id, $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $transaction_id = mysqli_insert_id($conn);

            // Insertion dans caisse
            $libelle = $description ?: ($type == 'recette' ? 'Recette' : 'Dépense') . " - " . $categorie;
            $type_caisse = ($type == 'recette') ? 'depot' : 'retrait';
            $stmt2 = mysqli_prepare($conn, "INSERT INTO caisse (date_operation, libelle, type, montant, mode, description, transaction_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, "sssdssii", $date_transaction, $libelle, $type_caisse, $montant, $mode_caisse, $description, $transaction_id, $_SESSION['user_id']);
            mysqli_stmt_execute($stmt2);

            mysqli_commit($conn);
            $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Transaction ajoutée avec succès.'];
            header('Location: tresorerie.php');
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Erreur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter transaction - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Ajouter une transaction</h1>
                <a href="tresorerie.php" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Retour</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Type *</label>
                                <select name="type" class="form-select" required>
                                    <option value="recette">Recette (entrée d'argent)</option>
                                    <option value="depense">Dépense (sortie d'argent)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catégorie *</label>
                                <select name="categorie" class="form-select" required>
                                    <option value="vente">Vente (déjà automatique)</option>
                                    <option value="loyer">Loyer</option>
                                    <option value="salaires">Salaires</option>
                                    <option value="services">Services (eau, électricité, internet)</option>
                                    <option value="impots_taxes">Impôts et taxes</option>
                                    <option value="fourniture">Fourniture de bureau</option>
                                    <option value="reparation">Réparation / Entretien</option>
                                    <option value="divers">Divers</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Montant (FCFA) *</label>
                                <input type="number" step="1" min="1" name="montant" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date *</label>
                                <input type="date" name="date_transaction" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Mode de paiement *</label>
                                <select name="mode_caisse" class="form-select" required>
                                    <option value="especes">Espèces</option>
                                    <option value="banque">Banque / Virement</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Motif de la transaction..."></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Enregistrer la transaction</button>
                        <a href="tresorerie.php" class="btn btn-outline">Annuler</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>