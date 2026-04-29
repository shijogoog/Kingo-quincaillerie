<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);

// CORRECTION : génération du token CSRF si absent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = mysqli_prepare($conn, "SELECT * FROM articles WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$article = mysqli_fetch_assoc($result);
if (!$article) {
    header('Location: articles.php?error=notfound');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, nom FROM fournisseurs ORDER BY nom");
mysqli_stmt_execute($stmt);
$result      = mysqli_stmt_get_result($stmt);
$fournisseurs = mysqli_fetch_all($result, MYSQLI_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CORRECTION : vérification CSRF manquante dans l'original
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erreur CSRF');
    }

    $nom          = trim($_POST['nom'] ?? '');
    $categorie    = trim($_POST['categorie'] ?? '');
    $fournisseur_id = !empty($_POST['fournisseur_id']) ? (int)$_POST['fournisseur_id'] : null;
    $prix_achat   = !empty($_POST['prix_achat']) ? (float)str_replace(',', '.', $_POST['prix_achat']) : null;
    $prix_vente   = !empty($_POST['prix_vente']) ? (float)str_replace(',', '.', $_POST['prix_vente']) : null;
    $quantite     = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 0;
    $stock_minimum = isset($_POST['stock_minimum']) ? (int)$_POST['stock_minimum'] : 5;
    $description  = trim($_POST['description'] ?? '');

    if (empty($nom)) {
        $error = "Le nom est obligatoire.";
    } elseif ($prix_vente === null || $prix_vente <= 0) {
        $error = "Le prix de vente doit être un nombre positif.";
    } elseif ($quantite < 0) {
        $error = "La quantité ne peut pas être négative.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE articles SET nom=?, categorie=?, fournisseur_id=?, prix_achat=?, prix_vente=?, quantite=?, stock_minimum=?, description=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssiddiisi", $nom, $categorie, $fournisseur_id, $prix_achat, $prix_vente, $quantite, $stock_minimum, $description, $id);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: articles.php?success=updated');
            exit;
        } else {
            $error = "Erreur lors de la modification : " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un article - <?= APP_NAME ?></title>
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
            <h1 class="h3 mb-4">Modifier l'article #<?= $id ?></h1>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <!-- CORRECTION : ajout du token CSRF dans le formulaire -->
                    <form method="POST" class="row g-4">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nom *</label>
                                <input type="text" name="nom" class="form-control"
                                       value="<?= htmlspecialchars($_POST['nom'] ?? $article['nom']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Catégorie</label>
                                <input type="text" name="categorie" class="form-control"
                                       value="<?= htmlspecialchars($_POST['categorie'] ?? $article['categorie']) ?>"
                                       list="categoriesList">
                                <datalist id="categoriesList">
                                    <?php
                                    $stmt = mysqli_prepare($conn, "SELECT DISTINCT categorie FROM articles WHERE categorie IS NOT NULL ORDER BY categorie");
                                    mysqli_stmt_execute($stmt);
                                    $res = mysqli_stmt_get_result($stmt);
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        echo '<option value="' . htmlspecialchars($row['categorie']) . '">';
                                    }
                                    ?>
                                </datalist>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fournisseur</label>
                                <select name="fournisseur_id" class="form-select">
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($fournisseurs as $f): ?>
                                        <option value="<?= $f['id'] ?>"
                                            <?= (($_POST['fournisseur_id'] ?? $article['fournisseur_id']) == $f['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($f['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Prix d'achat (FCFA)</label>
                                <input type="number" step="1" min="0" name="prix_achat" class="form-control"
                                       value="<?= htmlspecialchars($_POST['prix_achat'] ?? $article['prix_achat']) ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Prix de vente * (FCFA)</label>
                                <input type="number" step="1" min="1" name="prix_vente" class="form-control"
                                       value="<?= htmlspecialchars($_POST['prix_vente'] ?? $article['prix_vente']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Quantité *</label>
                                <input type="number" name="quantite" class="form-control"
                                       value="<?= htmlspecialchars($_POST['quantite'] ?? $article['quantite']) ?>" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Stock minimum *</label>
                                <input type="number" name="stock_minimum" class="form-control"
                                       value="<?= htmlspecialchars($_POST['stock_minimum'] ?? $article['stock_minimum']) ?>" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($_POST['description'] ?? $article['description']) ?></textarea>
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                            <a href="articles.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>
