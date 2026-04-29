<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = mysqli_prepare($conn, "SELECT id, nom, prix_vente, quantite, categorie FROM articles WHERE quantite > 0 ORDER BY nom");
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$articles = mysqli_fetch_all($result, MYSQLI_ASSOC);

$categories = array_unique(array_column($articles, 'categorie'));
sort($categories);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erreur CSRF');
    }

    $client_nom       = trim($_POST['client_nom'] ?? '');
    $client_telephone = trim($_POST['client_telephone'] ?? '');
    $paiement         = $_POST['paiement'] ?? 'especes';
    $articles_data    = $_POST['articles'] ?? [];
    $remise           = max(0, min(100, (int)($_POST['remise'] ?? 0)));

    if (empty($articles_data)) {
        $error = "Veuillez ajouter au moins un article.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, "INSERT INTO ventes (utilisateur_id, client_nom, client_telephone, montant_total, statut, paiement, remise) VALUES (?, ?, ?, 0, 'validée', ?, ?)");
            if (!$stmt) {
                // Fallback sans nouvelles colonnes
                $stmt = mysqli_prepare($conn, "INSERT INTO ventes (utilisateur_id, client_nom, montant_total, statut) VALUES (?, ?, 0, 'validée')");
                mysqli_stmt_bind_param($stmt, "is", $_SESSION['user_id'], $client_nom);
            } else {
                mysqli_stmt_bind_param($stmt, "isssi", $_SESSION['user_id'], $client_nom, $client_telephone, $paiement, $remise);
            }
            mysqli_stmt_execute($stmt);
            $vente_id = mysqli_insert_id($conn);

            $total = 0;
            foreach ($articles_data as $art) {
                $article_id = (int)$art['id'];
                $qte        = (int)$art['qte'];
                if ($qte <= 0) continue;

                $stmt2 = mysqli_prepare($conn, "SELECT prix_vente, quantite FROM articles WHERE id = ?");
                mysqli_stmt_bind_param($stmt2, "i", $article_id);
                mysqli_stmt_execute($stmt2);
                $article = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

                if (!$article || $article['quantite'] < $qte) {
                    throw new Exception("Stock insuffisant pour l'article ID $article_id");
                }

                $prix       = $article['prix_vente'];
                $sous_total = $qte * $prix;
                $total     += $sous_total;

                $stmt3 = mysqli_prepare($conn, "INSERT INTO details_vente (vente_id, article_id, quantite, prix_unitaire, sous_total) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt3, "iiidd", $vente_id, $article_id, $qte, $prix, $sous_total);
                mysqli_stmt_execute($stmt3);

                $stmt4 = mysqli_prepare($conn, "UPDATE articles SET quantite = quantite - ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt4, "ii", $qte, $article_id);
                mysqli_stmt_execute($stmt4);
            }

            $totalFinal = $total * (1 - $remise / 100);

            $stmt5 = mysqli_prepare($conn, "UPDATE ventes SET montant_total = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt5, "di", $totalFinal, $vente_id);
            mysqli_stmt_execute($stmt5);

            // ===== NOUVEAU : Enregistrement de la recette dans la table transactions =====
            $stmtRec = mysqli_prepare($conn, "INSERT INTO transactions (type, categorie, montant, description, date_transaction, reference_type, reference_id, created_by) VALUES ('recette', 'vente', ?, ?, CURDATE(), 'vente', ?, ?)");
            $description = "Vente n°$vente_id - Client: " . ($client_nom ?: 'Anonyme');
            mysqli_stmt_bind_param($stmtRec, "dsii", $totalFinal, $description, $vente_id, $_SESSION['user_id']);
            mysqli_stmt_execute($stmtRec);
            $transaction_id = mysqli_insert_id($conn);

            // Enregistrement dans la caisse (si paiement espèces ou mobile money -> espèces, sinon banque)
            $mode_caisse = ($paiement == 'carte' || $paiement == 'virement') ? 'banque' : 'especes';
            $stmtCaisse = mysqli_prepare($conn, "INSERT INTO caisse (date_operation, libelle, type, montant, mode, description, transaction_id, created_by) VALUES (CURDATE(), ?, 'depot', ?, ?, ?, ?, ?)");
            $libelle = "Vente n°$vente_id";
            mysqli_stmt_bind_param($stmtCaisse, "sissii", $libelle, $totalFinal, $mode_caisse, $description, $transaction_id, $_SESSION['user_id']);
            mysqli_stmt_execute($stmtCaisse);

            mysqli_commit($conn);
            header("Location: facture.php?id=$vente_id");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle vente - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* === VENTE LAYOUT === */
        .vente-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .vente-layout { grid-template-columns: 1fr; }
        }

        /* === CATALOGUE ARTICLES === */
        .catalogue-header {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .search-article-wrap {
            flex: 1;
            position: relative;
            min-width: 200px;
        }
        .search-article-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }
        .search-article-input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            font-size: 0.875rem;
            outline: none;
            transition: border-color .15s;
            background: white;
        }
        .search-article-input:focus { border-color: var(--primary); }

        .barcode-btn {
            padding: 8px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            background: white;
            cursor: pointer;
            color: #64748b;
            font-size: 1rem;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 500;
        }
        .barcode-btn:hover { border-color: var(--primary); color: var(--primary); }

        .cat-filters {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .cat-btn {
            padding: 4px 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            background: white;
            font-size: 0.78rem;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
        }
        .cat-btn:hover, .cat-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* Grille de cartes articles */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
            padding: 2px;
        }
        .article-card {
            border: 1.5px solid #e8edf3;
            border-radius: 10px;
            padding: 12px 10px;
            background: white;
            cursor: pointer;
            transition: all .15s ease;
            position: relative;
            overflow: hidden;
        }
        .article-card:hover {
            border-color: var(--primary);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30,74,111,0.1);
        }
        .article-card.in-cart {
            border-color: var(--accent);
            background: #fff9f5;
        }
        .article-card.stock-zero {
            opacity: .5;
            cursor: not-allowed;
            background: #f8f9fa;
        }
        .article-card-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .article-card-prix {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .article-card-stock {
            font-size: 0.7rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .article-card-stock.low { color: #f59e0b; }
        .article-card-stock.out { color: #ef4444; }
        .article-card-cat {
            font-size: 0.65rem;
            background: #f1f5f9;
            color: #64748b;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 6px;
        }
        .in-cart-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* === PANIER === */
        .panier-card {
            position: sticky;
            top: 80px;
            background: white;
            border-radius: 14px;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .panier-header {
            background: var(--primary);
            color: white;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .panier-header-title {
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panier-count {
            background: rgba(255,255,255,0.25);
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .panier-items {
            max-height: 260px;
            overflow-y: auto;
            padding: 10px;
        }
        .panier-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
            margin-bottom: 6px;
            background: #fafbfc;
        }
        .panier-item-name {
            flex: 1;
            font-size: 0.8rem;
            font-weight: 500;
            color: #1e293b;
            line-height: 1.3;
        }
        .panier-item-prix {
            font-size: 0.75rem;
            color: #64748b;
        }
        .panier-item-subtotal {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--primary);
            white-space: nowrap;
        }
        .qty-control {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .qty-btn {
            width: 22px;
            height: 22px;
            border: 1.5px solid #e2e8f0;
            border-radius: 5px;
            background: white;
            font-size: 0.9rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #475569;
            transition: all .1s;
            padding: 0;
        }
        .qty-btn:hover { border-color: var(--primary); color: var(--primary); }
        .qty-input {
            width: 38px;
            text-align: center;
            border: 1.5px solid #e2e8f0;
            border-radius: 5px;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 2px 4px;
            outline: none;
        }
        .qty-input:focus { border-color: var(--primary); }
        .del-btn {
            background: none;
            border: none;
            color: #cbd5e1;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 2px;
            line-height: 1;
            transition: color .1s;
        }
        .del-btn:hover { color: #ef4444; }

        .panier-empty {
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
        }
        .panier-empty i { font-size: 2.5rem; display: block; margin-bottom: 8px; }

        /* Client & paiement */
        .panier-form-section {
            padding: 12px 14px;
            border-top: 1px solid #f1f5f9;
        }
        .panier-form-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #94a3b8;
            margin-bottom: 6px;
            display: block;
        }
        .paiement-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 10px;
        }
        .paiement-option {
            border: 2px solid #e8edf3;
            border-radius: 8px;
            padding: 8px 8px;
            text-align: center;
            cursor: pointer;
            transition: all .15s;
            font-size: 0.75rem;
            font-weight: 500;
            color: #64748b;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .paiement-option i { font-size: 1.1rem; }
        .paiement-option:hover { border-color: var(--primary); color: var(--primary); }
        .paiement-option.selected {
            border-color: var(--primary);
            background: var(--primary-light);
            color: var(--primary);
        }
        .paiement-option.mobile-money { }
        .paiement-option.mobile-money.selected {
            border-color: #f59e0b;
            background: #fffbeb;
            color: #d97706;
        }

        /* Récap total */
        .panier-total-section {
            padding: 12px 14px;
            border-top: 2px solid #f1f5f9;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.82rem;
            margin-bottom: 4px;
        }
        .total-line.main {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            margin-top: 8px;
        }
        .total-line .label { color: #64748b; }
        .total-line .value { font-weight: 600; color: #1e293b; }
        .total-line.main .value { color: var(--primary); font-size: 1.1rem; }

        /* Bouton valider */
        .btn-valider {
            width: 100%;
            padding: 13px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }
        .btn-valider:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-valider:disabled { opacity: .5; cursor: not-allowed; transform: none; }

        /* Barcode modal */
        .barcode-modal-content {
            text-align: center;
            padding: 20px;
        }
        #barcodeInput {
            font-size: 1.2rem;
            letter-spacing: 4px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Nouvelle vente</h1>
                    <p class="text-muted small mb-0">Sélectionnez les articles puis validez la vente</p>
                </div>
                <a href="ventes.php" class="btn btn-outline"><i class="bi bi-arrow-left me-1"></i>Retour</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="venteForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="paiement" id="paiementInput" value="especes">
                <input type="hidden" name="remise" id="remiseInput" value="0">
                <div id="articlesHidden"></div>

                <div class="vente-layout">
                    <!-- GAUCHE : Catalogue -->
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <span><i class="bi bi-grid me-2"></i>Catalogue articles</span>
                                <span class="badge-period"><?= count($articles) ?> articles disponibles</span>
                            </div>
                            <div class="card-body">
                                <!-- Recherche + barcode -->
                                <div class="catalogue-header">
                                    <div class="search-article-wrap">
                                        <i class="bi bi-search"></i>
                                        <input type="text" class="search-article-input" id="searchArticle"
                                               placeholder="Rechercher un article...">
                                    </div>
                                    <button type="button" class="barcode-btn" data-bs-toggle="modal" data-bs-target="#barcodeModal">
                                        <i class="bi bi-upc-scan"></i> Code-barres
                                    </button>
                                </div>

                                <!-- Filtres catégories -->
                                <div class="cat-filters">
                                    <button type="button" class="cat-btn active" data-cat="">Tout</button>
                                    <?php foreach ($categories as $cat): if (!$cat) continue; ?>
                                    <button type="button" class="cat-btn" data-cat="<?= htmlspecialchars($cat) ?>">
                                        <?= htmlspecialchars($cat) ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Grille articles -->
                                <div class="articles-grid" id="articlesGrid">
                                    <?php foreach ($articles as $a): ?>
                                    <div class="article-card <?= $a['quantite'] == 0 ? 'stock-zero' : '' ?>"
                                         data-id="<?= $a['id'] ?>"
                                         data-nom="<?= htmlspecialchars($a['nom']) ?>"
                                         data-prix="<?= $a['prix_vente'] ?>"
                                         data-stock="<?= $a['quantite'] ?>"
                                         data-cat="<?= htmlspecialchars($a['categorie'] ?? '') ?>"
                                         onclick="ajouterAuPanier(this)">
                                        <div class="in-cart-badge" id="badge_<?= $a['id'] ?>" style="display:none"></div>
                                        <?php if ($a['categorie']): ?>
                                        <div class="article-card-cat"><?= htmlspecialchars($a['categorie']) ?></div>
                                        <?php endif; ?>
                                        <div class="article-card-name"><?= htmlspecialchars($a['nom']) ?></div>
                                        <div class="article-card-prix"><?= fcfa($a['prix_vente']) ?></div>
                                        <div class="article-card-stock <?= $a['quantite'] == 0 ? 'out' : ($a['quantite'] <= 5 ? 'low' : '') ?>">
                                            <i class="bi bi-box"></i>
                                            <?= $a['quantite'] == 0 ? 'Rupture' : $a['quantite'] . ' en stock' ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DROITE : Panier -->
                    <div>
                        <div class="panier-card">
                            <div class="panier-header">
                                <div class="panier-header-title">
                                    <i class="bi bi-cart3"></i> Panier
                                    <span class="panier-count" id="panierCount">0</span>
                                </div>
                                <button type="button" class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:white;border:none;border-radius:6px;font-size:0.75rem" onclick="viderPanier()">
                                    <i class="bi bi-trash"></i> Vider
                                </button>
                            </div>

                            <!-- Items panier -->
                            <div class="panier-items" id="panierItems">
                                <div class="panier-empty" id="panierEmpty">
                                    <i class="bi bi-cart-x"></i>
                                    Aucun article sélectionné
                                    <div style="font-size:0.75rem;margin-top:4px">Cliquez sur les articles du catalogue</div>
                                </div>
                            </div>

                            <!-- Client -->
                            <div class="panier-form-section">
                                <label class="panier-form-label">Client</label>
                                <input type="text" name="client_nom" class="form-control form-control-sm mb-2"
                                       placeholder="Nom du client (optionnel)"
                                       value="<?= htmlspecialchars($_POST['client_nom'] ?? '') ?>">
                                <input type="tel" name="client_telephone" class="form-control form-control-sm"
                                       placeholder="Téléphone (optionnel)"
                                       value="<?= htmlspecialchars($_POST['client_telephone'] ?? '') ?>">
                            </div>

                            <!-- Mode de paiement -->
                            <div class="panier-form-section">
                                <label class="panier-form-label">Mode de paiement</label>
                                <div class="paiement-grid">
                                    <div class="paiement-option selected" data-paiement="especes">
                                        <i class="bi bi-cash-stack"></i> Espèces
                                    </div>
                                    <div class="paiement-option mobile-money" data-paiement="mobile_money">
                                        <i class="bi bi-phone"></i> Mobile Money
                                    </div>
                                    <div class="paiement-option" data-paiement="carte">
                                        <i class="bi bi-credit-card"></i> Carte
                                    </div>
                                    <div class="paiement-option" data-paiement="virement">
                                        <i class="bi bi-bank"></i> Virement
                                    </div>
                                </div>

                                <!-- Détails Mobile Money -->
                                <div id="mobilemoneyDetails" style="display:none">
                                    <div class="mb-2">
                                        <label class="panier-form-label">Opérateur</label>
                                        <select name="mm_operateur" class="form-select form-select-sm">
                                            <option value="tmoney">T-Money (Togocel)</option>
                                            <option value="flooz">Flooz (Moov)</option>
                                            <option value="orange_money">Orange Money</option>
                                            <option value="wave">Wave</option>
                                        </select>
                                    </div>
                                    <input type="text" name="mm_numero" class="form-control form-control-sm"
                                           placeholder="Numéro de transaction (optionnel)">
                                </div>
                            </div>

                            <!-- Remise -->
                            <div class="panier-form-section">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="panier-form-label mb-0">Remise</label>
                                    <span id="remiseLabel" style="font-size:0.8rem;font-weight:700;color:var(--accent)">0%</span>
                                </div>
                                <input type="range" class="form-range" min="0" max="50" step="5"
                                       value="0" id="remiseRange"
                                       oninput="updateRemise(this.value)">
                                <div class="d-flex justify-content-between" style="font-size:0.65rem;color:#94a3b8;margin-top:2px">
                                    <span>0%</span><span>10%</span><span>20%</span><span>30%</span><span>40%</span><span>50%</span>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="panier-total-section">
                                <div class="total-line">
                                    <span class="label">Sous-total</span>
                                    <span class="value" id="sousTotal">0 FCFA</span>
                                </div>
                                <div class="total-line" id="remiseLine" style="display:none;color:#f59e0b">
                                    <span>Remise (<span id="remisePct">0</span>%)</span>
                                    <span id="remiseMontant">-0 FCFA</span>
                                </div>
                                <div class="total-line main">
                                    <span class="label">Total TTC</span>
                                    <span class="value" id="totalDisplay">0 FCFA</span>
                                </div>
                                <button type="submit" class="btn-valider" id="btnValider" disabled>
                                    <i class="bi bi-check-circle"></i> Valider la vente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Code-barres -->
    <div class="modal fade" id="barcodeModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upc-scan me-2"></i>Scanner un code-barres</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body barcode-modal-content">
                    <p class="text-muted small mb-3">Scannez ou entrez manuellement le code-barres de l'article.</p>
                    <input type="text" id="barcodeInput" class="form-control mb-3"
                           placeholder="Code-barres..." autofocus autocomplete="off">
                    <button type="button" class="btn btn-primary w-100" onclick="rechercherParCode()">
                        <i class="bi bi-search me-1"></i> Rechercher
                    </button>
                    <div id="barcodeResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
    <script>
        // Données articles depuis PHP
        const ARTICLES = <?= json_encode($articles) ?>;
        let panier = []; // [{id, nom, prix, stock, qte}]

        // ===== CATALOGUE =====
        function ajouterAuPanier(card) {
            if (card.classList.contains('stock-zero')) return;
            const id    = parseInt(card.dataset.id);
            const nom   = card.dataset.nom;
            const prix  = parseFloat(card.dataset.prix);
            const stock = parseInt(card.dataset.stock);

            const existing = panier.find(p => p.id === id);
            if (existing) {
                if (existing.qte < stock) existing.qte++;
            } else {
                panier.push({ id, nom, prix, stock, qte: 1 });
            }
            renderPanier();
            renderCatalogue();
        }

        function renderCatalogue() {
            panier.forEach(item => {
                const badge = document.getElementById('badge_' + item.id);
                const card  = document.querySelector(`[data-id="${item.id}"]`);
                if (badge) { badge.style.display = 'flex'; badge.textContent = item.qte; }
                if (card)  { card.classList.add('in-cart'); }
            });
            // Retirer les badges des articles supprimés
            ARTICLES.forEach(a => {
                if (!panier.find(p => p.id === a.id)) {
                    const badge = document.getElementById('badge_' + a.id);
                    const card  = document.querySelector(`[data-id="${a.id}"]`);
                    if (badge) badge.style.display = 'none';
                    if (card)  card.classList.remove('in-cart');
                }
            });
        }

        // ===== PANIER =====
        function renderPanier() {
            const container = document.getElementById('panierItems');
            const empty     = document.getElementById('panierEmpty');
            const countEl   = document.getElementById('panierCount');
            const btnValider= document.getElementById('btnValider');
            const hidden    = document.getElementById('articlesHidden');

            // Vider
            container.querySelectorAll('.panier-item').forEach(el => el.remove());
            hidden.innerHTML = '';

            if (panier.length === 0) {
                empty.style.display = 'block';
                countEl.textContent = '0';
                btnValider.disabled = true;
                updateTotal();
                return;
            }
            empty.style.display = 'none';
            countEl.textContent = panier.reduce((s, p) => s + p.qte, 0);
            btnValider.disabled = false;

            panier.forEach((item, i) => {
                const el = document.createElement('div');
                el.className = 'panier-item';
                el.innerHTML = `
                    <div style="flex:1;min-width:0">
                        <div class="panier-item-name">${item.nom}</div>
                        <div class="panier-item-prix">${formatFCFA(item.prix)} / u</div>
                    </div>
                    <div class="qty-control">
                        <button type="button" class="qty-btn" onclick="changeQty(${i},-1)">−</button>
                        <input type="number" class="qty-input" value="${item.qte}" min="1" max="${item.stock}"
                               onchange="setQty(${i},this.value)">
                        <button type="button" class="qty-btn" onclick="changeQty(${i},1)">+</button>
                    </div>
                    <div class="panier-item-subtotal ms-2">${formatFCFA(item.prix * item.qte)}</div>
                    <button type="button" class="del-btn ms-1" onclick="supprimerItem(${i})"><i class="bi bi-x"></i></button>
                `;
                container.appendChild(el);

                // Hidden inputs
                ['id','qte'].forEach(f => {
                    const inp = document.createElement('input');
                    inp.type  = 'hidden';
                    inp.name  = `articles[${i}][${f}]`;
                    inp.value = f === 'id' ? item.id : item.qte;
                    hidden.appendChild(inp);
                });
            });

            updateTotal();
        }

        function changeQty(index, delta) {
            panier[index].qte = Math.max(1, Math.min(panier[index].stock, panier[index].qte + delta));
            renderPanier();
            renderCatalogue();
        }

        function setQty(index, val) {
            panier[index].qte = Math.max(1, Math.min(panier[index].stock, parseInt(val) || 1));
            renderPanier();
            renderCatalogue();
        }

        function supprimerItem(index) {
            panier.splice(index, 1);
            renderPanier();
            renderCatalogue();
        }

        function viderPanier() {
            if (panier.length === 0) return;
            if (!confirm('Vider le panier ?')) return;
            panier = [];
            renderPanier();
            renderCatalogue();
        }

        // ===== TOTAL =====
        let remisePct = 0;

        function updateTotal() {
            const sousTotal = panier.reduce((s, p) => s + p.prix * p.qte, 0);
            const remise    = sousTotal * remisePct / 100;
            const total     = sousTotal - remise;

            document.getElementById('sousTotal').textContent    = formatFCFA(sousTotal);
            document.getElementById('totalDisplay').textContent = formatFCFA(total);

            if (remisePct > 0) {
                document.getElementById('remiseLine').style.display    = 'flex';
                document.getElementById('remiseMontant').textContent   = '-' + formatFCFA(remise);
                document.getElementById('remisePct').textContent       = remisePct;
            } else {
                document.getElementById('remiseLine').style.display = 'none';
            }
        }

        function updateRemise(val) {
            remisePct = parseInt(val);
            document.getElementById('remiseLabel').textContent = val + '%';
            document.getElementById('remiseInput').value = val;
            updateTotal();
        }

        function formatFCFA(n) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FCFA';
        }

        // ===== PAIEMENT =====
        document.querySelectorAll('.paiement-option').forEach(opt => {
            opt.addEventListener('click', () => {
                document.querySelectorAll('.paiement-option').forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                const p = opt.dataset.paiement;
                document.getElementById('paiementInput').value = p;
                document.getElementById('mobilemoneyDetails').style.display =
                    p === 'mobile_money' ? 'block' : 'none';
            });
        });

        // ===== RECHERCHE CATALOGUE =====
        document.getElementById('searchArticle').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.article-card').forEach(card => {
                const match = card.dataset.nom.toLowerCase().includes(q) ||
                              (card.dataset.cat || '').toLowerCase().includes(q);
                card.style.display = match ? '' : 'none';
            });
        });

        // ===== FILTRE CATÉGORIES =====
        document.querySelectorAll('.cat-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const cat = this.dataset.cat;
                document.querySelectorAll('.article-card').forEach(card => {
                    card.style.display = (!cat || card.dataset.cat === cat) ? '' : 'none';
                });
            });
        });

        // ===== CODE-BARRES =====
        function rechercherParCode() {
            const code = document.getElementById('barcodeInput').value.trim();
            const result = document.getElementById('barcodeResult');
            if (!code) return;

            // Cherche dans le nom (simulation — en prod, chercher par champ code_barre en BDD)
            const found = ARTICLES.find(a => a.nom.toLowerCase().includes(code.toLowerCase()) || String(a.id) === code);
            if (found) {
                result.innerHTML = `<div class="alert alert-success py-2 small">
                    <strong>${found.nom}</strong> — ${formatFCFA(found.prix_vente)}<br>
                    <small>Stock: ${found.quantite}</small>
                </div>`;
                // Fermer modal et ajouter
                bootstrap.Modal.getInstance(document.getElementById('barcodeModal')).hide();
                const card = document.querySelector(`[data-id="${found.id}"]`);
                if (card) ajouterAuPanier(card);
            } else {
                result.innerHTML = `<div class="alert alert-warning py-2 small">Aucun article trouvé pour "<strong>${code}</strong>"</div>`;
            }
        }

        // Scanner auto (dès que le modal est ouvert, focus sur l'input)
        document.getElementById('barcodeModal').addEventListener('shown.bs.modal', () => {
            document.getElementById('barcodeInput').focus();
            document.getElementById('barcodeInput').value = '';
            document.getElementById('barcodeResult').innerHTML = '';
        });
        document.getElementById('barcodeInput').addEventListener('keydown', e => {
            if (e.key === 'Enter') rechercherParCode();
        });
    </script>
</body>
</html>