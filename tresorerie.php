<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur', 'gestionnaire']);
// Vérifier si un solde initial a déjà été défini
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as nb FROM transactions WHERE categorie = 'solde_initial'");
mysqli_stmt_execute($stmt);
$solde_initial_existe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['nb'] > 0;

$mois = isset($_GET['mois']) ? (int) $_GET['mois'] : date('m');
$annee = isset($_GET['annee']) ? (int) $_GET['annee'] : date('Y');
$date_debut = "$annee-$mois-01";
$date_fin = date('Y-m-t', strtotime($date_debut));

// Solde espèces (depots - retraits)
$stmt = mysqli_prepare($conn, "
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'depot' THEN montant ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN type = 'retrait' THEN montant ELSE 0 END), 0) as solde
    FROM caisse 
    WHERE mode = 'especes' AND date_operation <= ?
");
mysqli_stmt_bind_param($stmt, "s", $date_fin);
mysqli_stmt_execute($stmt);
$soldeEspeces = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['solde'] ?? 0;

// Solde banque
$stmt = mysqli_prepare($conn, "
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'depot' THEN montant ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN type = 'retrait' THEN montant ELSE 0 END), 0) as solde
    FROM caisse 
    WHERE mode = 'banque' AND date_operation <= ?
");
mysqli_stmt_bind_param($stmt, "s", $date_fin);
mysqli_stmt_execute($stmt);
$soldeBanque = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['solde'] ?? 0;
// Recettes du mois
$stmt = mysqli_prepare($conn, "SELECT SUM(montant) as total FROM transactions WHERE type='recette' AND DATE(date_transaction) BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$recettes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0;

// Dépenses du mois
$stmt = mysqli_prepare($conn, "SELECT SUM(montant) as total FROM transactions WHERE type='depense' AND DATE(date_transaction) BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$depenses = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0;

// Dépenses périodiques à appliquer ce mois (si non encore enregistrées)
// On vérifie les dépenses périodiques actives et on propose de les ajouter
$stmt = mysqli_prepare($conn, "SELECT * FROM depenses_periodiques WHERE actif=1");
mysqli_stmt_execute($stmt);
$periodiques = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Transactions détaillées pour affichage
$stmt = mysqli_prepare($conn, "
    SELECT t.*, u.nom as created_by_name
    FROM transactions t
    LEFT JOIN users u ON u.id = t.created_by
    WHERE DATE(t.date_transaction) BETWEEN ? AND ?
    ORDER BY t.date_transaction DESC, t.id DESC
");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$transactions = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Opérations de caisse du mois
$stmt = mysqli_prepare($conn, "SELECT * FROM caisse WHERE DATE(date_operation) BETWEEN ? AND ? ORDER BY date_operation DESC");
mysqli_stmt_bind_param($stmt, "ss", $date_debut, $date_fin);
mysqli_stmt_execute($stmt);
$caisses = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Trésorerie - <?= APP_NAME ?></title>
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
                <h1 class="h3">Trésorerie et gestion financière</h1>
                <div>
                    <a href="ajouter_transaction.php" class="btn btn-primary me-2"><i class="bi bi-plus"></i> Ajouter
                        transaction</a>
                    <a href="depenses.php" class="btn btn-outline"><i class="bi bi-receipt"></i> Dépenses
                        périodiques</a>
                </div>
            </div>
            <div class="btn-group mb-3">
                <button class="btn btn-outline dropdown-toggle" data-bs-toggle="dropdown">Exporter Excel</button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item"
                            href="exporter_finances.php?type=transactions&mois=<?= $mois ?>&annee=<?= $annee ?>">Transactions
                            du mois</a></li>
                    <li><a class="dropdown-item"
                            href="exporter_finances.php?type=ventes&mois=<?= $mois ?>&annee=<?= $annee ?>">Ventes du
                            mois</a></li>
                    <li><a class="dropdown-item"
                            href="exporter_finances.php?type=depenses&mois=<?= $mois ?>&annee=<?= $annee ?>">Dépenses du
                            mois</a></li>
                </ul>
            </div>
            <!-- Filtre mois -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Mois</label>
                            <select name="mois" class="form-select">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $mois == $m ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Année</label>
                            <select name="annee" class="form-select">
                                <?php $currentYear = date('Y');
                                for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++): ?>
                                    <option value="<?= $y ?>" <?= $annee == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cartes résumé -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="kpi-card blue">
                        <div class="kpi-header">
                            <div class="kpi-label">Espèces en caisse</div><i class="bi bi-cash-stack fs-4"></i>
                        </div>
                        <div class="kpi-value"><?= fcfa($soldeEspeces) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card green">
                        <div class="kpi-header">
                            <div class="kpi-label">Banque</div><i class="bi bi-bank fs-4"></i>
                        </div>
                        <div class="kpi-value"><?= fcfa($soldeBanque) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card orange">
                        <div class="kpi-header">
                            <div class="kpi-label">Recettes du mois</div><i class="bi bi-arrow-up-circle fs-4"></i>
                        </div>
                        <div class="kpi-value"><?= fcfa($recettes) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card red">
                        <div class="kpi-header">
                            <div class="kpi-label">Dépenses du mois</div><i class="bi bi-arrow-down-circle fs-4"></i>
                        </div>
                        <div class="kpi-value"><?= fcfa($depenses) ?></div>
                    </div>
                </div>
            </div>
            <?php if (!$solde_initial_existe && ($_SESSION['role'] === 'administrateur' || $_SESSION['role'] === 'gestionnaire')): ?>
                <div class="alert alert-warning mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Solde initial non défini</strong> – Vos soldes actuels (espèces/banque) partent de zéro.
                            Pour corriger, indiquez l'argent que vous aviez avant d'utiliser ce module.
                        </div>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#soldeInitialModal">
                            <i class="bi bi-pencil-square"></i> Définir le solde initial
                        </button>
                    </div>
                </div>

                <!-- Modal pour définir solde initial -->
                <div class="modal fade" id="soldeInitialModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="definir_solde_initial.php" class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Définir le solde initial</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted">Indiquez l'argent que vous aviez en caisse et à la banque
                                    <strong>avant la première utilisation</strong> de ce module.</p>
                                <div class="mb-3">
                                    <label class="form-label">Espèces (FCFA)</label>
                                    <input type="number" name="especes" class="form-control" step="1" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Banque (FCFA)</label>
                                    <input type="number" name="banque" class="form-control" step="1" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date d'effet</label>
                                    <input type="date" name="date_initial" class="form-control" value="<?= date('Y-m-d') ?>"
                                        required>
                                </div>
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Enregistrer le solde initial</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dépenses périodiques à appliquer -->
            <?php if (count($periodiques) > 0): ?>
                <div class="card mb-4">
                    <div class="card-header">Dépenses périodiques à enregistrer pour ce mois</div>
                    <div class="card-body">
                        <form method="POST" action="ajouter_transaction.php" class="row g-3">
                            <input type="hidden" name="type" value="depense">
                            <input type="hidden" name="reference_type" value="depense_periodique">
                            <input type="hidden" name="date_transaction" value="<?= date('Y-m-d') ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="col-md-8">
                                <select name="categorie" class="form-select" required>
                                    <option value="">-- Choisir une dépense périodique --</option>
                                    <?php foreach ($periodiques as $p): ?>
                                        <option value="<?= htmlspecialchars($p['categorie']) ?>"
                                            data-montant="<?= $p['montant'] ?>"
                                            data-libelle="<?= htmlspecialchars($p['libelle']) ?>">
                                            <?= htmlspecialchars($p['libelle']) ?> - <?= fcfa($p['montant']) ?>
                                            (<?= $p['frequence'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="montant" class="form-control" placeholder="Montant" required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($solde_initial_existe && $_SESSION['role'] === 'administrateur'): ?>
                <div class="alert alert-info mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-info-circle me-2"></i> Un solde initial a été défini. Pour le modifier,
                            supprimez d'abord les transactions "solde_initial".</div>
                        <a href="supprimer_solde_initial.php" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Supprimer toutes les transactions de solde initial ? Cela affectera vos soldes.')">Supprimer
                            le solde initial</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tableau des transactions -->
            <div class="card">
                <div class="card-header">Transactions du mois</div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Catégorie</th>
                                <th>Description</th>
                                <th class="text-end">Montant</th>
                                <th>Créé par</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr class="<?= $t['type'] == 'recette' ? 'table-success' : 'table-warning' ?>">
                                    <td><?= date('d/m/Y', strtotime($t['date_transaction'])) ?></td>
                                    <td><?= ucfirst($t['type']) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $t['categorie'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($t['description'] ?? '')) ?></td>
                                    <td class="text-end fw-bold"><?= fcfa($t['montant']) ?></td>
                                    <td><?= htmlspecialchars($t['created_by_name'] ?? '-') ?></td>
                                    <td>
                                        <a href="supprimer_transaction.php?id=<?= $t['id'] ?>"
                                            class="btn btn-sm btn-outline text-danger delete-confirm"><i
                                                class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Aucune transaction pour cette période.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Opérations de caisse -->
            <div class="card mt-4">
                <div class="card-header">Mouvements de caisse (détail)</div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Libellé</th>
                                <th>Type</th>
                                <th>Mode</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($caisses as $c): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($c['date_operation'])) ?></td>
                                    <td><?= htmlspecialchars($c['libelle']) ?></td>
                                    <td><?= ucfirst($c['type']) ?></td>
                                    <td><?= $c['mode'] == 'especes' ? 'Espèces' : 'Banque' ?></td>
                                    <td class="text-end"><?= fcfa($c['montant']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
    <script>
        // Pré-remplir le montant quand on sélectionne une dépense périodique
        document.querySelector('select[name="categorie"]')?.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const montant = selected.dataset.montant;
            if (montant) document.querySelector('input[name="montant"]').value = montant;
        });
    </script>
</body>

</html>