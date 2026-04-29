<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth(['administrateur']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error   = '';
$success = '';

// ===== ACTIONS POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erreur CSRF');
    }
    $action = $_POST['action'] ?? '';

    // --- AJOUTER ---
    if ($action === 'ajouter') {
        $nom      = trim($_POST['nom'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'vendeur';
        $actif    = isset($_POST['actif']) ? 1 : 0;

        $roles_valides = ['administrateur', 'gestionnaire', 'vendeur'];
        if (empty($nom) || empty($email) || empty($password)) {
            $error = "Tous les champs obligatoires doivent être remplis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Adresse email invalide.";
        } elseif (!in_array($role, $roles_valides)) {
            $error = "Rôle invalide.";
        } elseif (strlen($password) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            // Vérifier email unique
            $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($chk, "s", $email);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            if (mysqli_stmt_num_rows($chk) > 0) {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "INSERT INTO users (nom, email, password, role, actif) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssi", $nom, $email, $hash, $role, $actif);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['toast'] = ['type' => 'success', 'msg' => "Utilisateur \"$nom\" créé avec succès."];
                    header('Location: users.php');
                    exit;
                } else {
                    $error = "Erreur lors de la création : " . mysqli_error($conn);
                }
            }
        }
    }

    // --- MODIFIER ---
    elseif ($action === 'modifier') {
        $id    = (int)$_POST['id'];
        $nom   = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? 'vendeur';
        $actif = isset($_POST['actif']) ? 1 : 0;

        // Empêcher de désactiver son propre compte
        if ($id === (int)$_SESSION['user_id'] && !$actif) {
            $error = "Vous ne pouvez pas désactiver votre propre compte.";
        } elseif (empty($nom) || empty($email)) {
            $error = "Nom et email sont obligatoires.";
        } else {
            // Vérifier email unique (sauf soi-même)
            $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
            mysqli_stmt_bind_param($chk, "si", $email, $id);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            if (mysqli_stmt_num_rows($chk) > 0) {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                // Modifier avec ou sans mot de passe
                if (!empty($_POST['password'])) {
                    if (strlen($_POST['password']) < 6) {
                        $error = "Le mot de passe doit contenir au moins 6 caractères.";
                    } else {
                        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = mysqli_prepare($conn, "UPDATE users SET nom=?, email=?, password=?, role=?, actif=? WHERE id=?");
                        mysqli_stmt_bind_param($stmt, "ssssii", $nom, $email, $hash, $role, $actif, $id);
                    }
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE users SET nom=?, email=?, role=?, actif=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "sssii", $nom, $email, $role, $actif, $id);
                }
                if (empty($error)) {
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['toast'] = ['type' => 'success', 'msg' => "Utilisateur modifié avec succès."];
                        header('Location: users.php');
                        exit;
                    } else {
                        $error = "Erreur : " . mysqli_error($conn);
                    }
                }
            }
        }
    }

    // --- SUPPRIMER ---
    elseif ($action === 'supprimer') {
        $id = (int)$_POST['id'];
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['toast'] = ['type' => 'danger', 'msg' => "Vous ne pouvez pas supprimer votre propre compte."];
        } else {
            // Vérifier si l'utilisateur a des ventes
            $chk = mysqli_prepare($conn, "SELECT COUNT(*) as nb FROM ventes WHERE utilisateur_id = ?");
            mysqli_stmt_bind_param($chk, "i", $id);
            mysqli_stmt_execute($chk);
            $nb = mysqli_fetch_assoc(mysqli_stmt_get_result($chk))['nb'];
            if ($nb > 0) {
                $_SESSION['toast'] = ['type' => 'danger', 'msg' => "Impossible de supprimer : cet utilisateur a $nb vente(s) enregistrée(s). Désactivez-le plutôt."];
            } else {
                $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $_SESSION['toast'] = ['type' => 'success', 'msg' => "Utilisateur supprimé."];
            }
        }
        header('Location: users.php');
        exit;
    }

    // --- TOGGLE ACTIF ---
    elseif ($action === 'toggle_actif') {
        $id = (int)$_POST['id'];
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['toast'] = ['type' => 'danger', 'msg' => "Vous ne pouvez pas désactiver votre propre compte."];
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET actif = NOT actif WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $_SESSION['toast'] = ['type' => 'success', 'msg' => "Statut mis à jour."];
        }
        header('Location: users.php');
        exit;
    }
}

// ===== RÉCUPÉRER TOUS LES UTILISATEURS =====
$stmt = mysqli_prepare($conn, "
    SELECT u.*, 
           COUNT(v.id) as nb_ventes,
           COALESCE(SUM(v.montant_total), 0) as ca_total
    FROM users u
    LEFT JOIN ventes v ON v.utilisateur_id = u.id AND v.statut = 'validée'
    GROUP BY u.id
    ORDER BY u.role, u.nom
");
mysqli_stmt_execute($stmt);
$users = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Stats
$totalUsers    = count($users);
$totalActifs   = count(array_filter($users, fn($u) => $u['actif']));
$totalAdmins   = count(array_filter($users, fn($u) => $u['role'] === 'administrateur'));
$totalVendeurs = count(array_filter($users, fn($u) => $u['role'] === 'vendeur'));

// Permissions par rôle
$permissions = [
    'administrateur' => [
        'dashboard' => true, 'articles' => true, 'ventes' => true,
        'stock' => true, 'rapports' => true, 'fournisseurs' => true,
        'users' => true, 'alertes' => true, 'supprimer' => true, 'annuler_vente' => true
    ],
    'gestionnaire' => [
        'dashboard' => true, 'articles' => true, 'ventes' => true,
        'stock' => true, 'rapports' => true, 'fournisseurs' => false,
        'users' => false, 'alertes' => true, 'supprimer' => false, 'annuler_vente' => false
    ],
    'vendeur' => [
        'dashboard' => true, 'articles' => false, 'ventes' => true,
        'stock' => false, 'rapports' => false, 'fournisseurs' => false,
        'users' => false, 'alertes' => true, 'supprimer' => false, 'annuler_vente' => false
    ],
];
$permLabels = [
    'dashboard' => 'Tableau de bord', 'articles' => 'Articles', 'ventes' => 'Ventes',
    'stock' => 'Stock', 'rapports' => 'Rapports', 'fournisseurs' => 'Fournisseurs',
    'users' => 'Utilisateurs', 'alertes' => 'Alertes', 'supprimer' => 'Supprimer articles', 'annuler_vente' => 'Annuler ventes'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - <?= APP_NAME ?></title>
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

            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Gestion des utilisateurs</h1>
                    <p class="text-muted small mb-0">Gérez les accès et permissions de votre équipe</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajoutModal">
                    <i class="bi bi-person-plus me-1"></i> Nouvel utilisateur
                </button>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- KPI -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card blue">
                        <div class="kpi-header">
                            <div class="kpi-label">Total</div>
                            <div class="kpi-icon" style="background:#eff6ff;color:#3b82f6"><i class="bi bi-people fs-5"></i></div>
                        </div>
                        <div class="kpi-value"><?= $totalUsers ?></div>
                        <div class="kpi-sub">utilisateurs</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card green">
                        <div class="kpi-header">
                            <div class="kpi-label">Actifs</div>
                            <div class="kpi-icon" style="background:#f0fdf4;color:#16a34a"><i class="bi bi-person-check fs-5"></i></div>
                        </div>
                        <div class="kpi-value"><?= $totalActifs ?></div>
                        <div class="kpi-sub">comptes actifs</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card orange">
                        <div class="kpi-header">
                            <div class="kpi-label">Admins</div>
                            <div class="kpi-icon" style="background:#fff7ed;color:#f97316"><i class="bi bi-shield-check fs-5"></i></div>
                        </div>
                        <div class="kpi-value"><?= $totalAdmins ?></div>
                        <div class="kpi-sub">administrateurs</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card red">
                        <div class="kpi-header">
                            <div class="kpi-label">Vendeurs</div>
                            <div class="kpi-icon" style="background:#fef2f2;color:#ef4444"><i class="bi bi-person-badge fs-5"></i></div>
                        </div>
                        <div class="kpi-value"><?= $totalVendeurs ?></div>
                        <div class="kpi-sub">vendeurs/caissiers</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Tableau utilisateurs -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <span>Liste des utilisateurs</span>
                            <input type="text" class="form-control form-control-sm" id="userSearch"
                                   placeholder="Rechercher..." style="width:200px">
                        </div>
                        <div class="card-body p-0">
                            <table class="table mb-0" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>Utilisateur</th>
                                        <th>Rôle</th>
                                        <th>Ventes</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="user-avatar-sm" style="background:<?= $u['role'] === 'administrateur' ? '#f97316' : ($u['role'] === 'gestionnaire' ? '#3b82f6' : '#16a34a') ?>">
                                                    <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-600 small"><?= htmlspecialchars($u['nom']) ?></div>
                                                    <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($u['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="role-badge role-<?= $u['role'] ?>">
                                                <?php
                                                $roleIcons = ['administrateur' => 'bi-shield-fill', 'gestionnaire' => 'bi-tools', 'vendeur' => 'bi-person-badge'];
                                                ?>
                                                <i class="bi <?= $roleIcons[$u['role']] ?>"></i>
                                                <?= ucfirst($u['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small fw-600"><?= $u['nb_ventes'] ?> ventes</div>
                                            <div class="text-muted" style="font-size:11px"><?= fcfa($u['ca_total']) ?></div>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_actif">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <button type="submit" class="badge border-0 <?= $u['actif'] ? 'bg-success' : 'bg-secondary' ?>"
                                                    style="cursor:pointer;font-size:11px;padding:4px 8px;"
                                                    <?= $u['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                                    <?= $u['actif'] ? 'Actif' : 'Inactif' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editModal<?= $u['id'] ?>"
                                                title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" class="d-inline delete-form">
                                                <input type="hidden" name="action" value="supprimer">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline text-danger" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Modal Modifier -->
                                    <div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST" class="modal-content">
                                                <input type="hidden" name="action" value="modifier">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Modifier <?= htmlspecialchars($u['nom']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Nom complet *</label>
                                                        <input type="text" name="nom" class="form-control"
                                                               value="<?= htmlspecialchars($u['nom']) ?>" required>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Email *</label>
                                                        <input type="email" name="email" class="form-control"
                                                               value="<?= htmlspecialchars($u['email']) ?>" required>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Nouveau mot de passe <small class="text-muted">(laisser vide pour ne pas changer)</small></label>
                                                        <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password">
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Rôle *</label>
                                                        <select name="role" class="form-select" <?= $u['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                                            <option value="administrateur" <?= $u['role'] === 'administrateur' ? 'selected' : '' ?>>Administrateur</option>
                                                            <option value="gestionnaire"   <?= $u['role'] === 'gestionnaire'   ? 'selected' : '' ?>>Gestionnaire</option>
                                                            <option value="vendeur"        <?= $u['role'] === 'vendeur'        ? 'selected' : '' ?>>Vendeur / Caissier</option>
                                                        </select>
                                                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                        <input type="hidden" name="role" value="<?= $u['role'] ?>">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="actif" id="actif<?= $u['id'] ?>"
                                                               <?= $u['actif'] ? 'checked' : '' ?>
                                                               <?= $u['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                                        <label class="form-check-label" for="actif<?= $u['id'] ?>">Compte actif</label>
                                                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                        <input type="hidden" name="actif" value="1">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tableau des permissions -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-shield-lock me-2"></i>Permissions par rôle
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th style="font-size:11px">Fonctionnalité</th>
                                            <th class="text-center" style="font-size:10px">Admin</th>
                                            <th class="text-center" style="font-size:10px">Gest.</th>
                                            <th class="text-center" style="font-size:10px">Vend.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($permLabels as $key => $label): ?>
                                        <tr>
                                            <td style="font-size:11px"><?= $label ?></td>
                                            <?php foreach (['administrateur','gestionnaire','vendeur'] as $r): ?>
                                            <td class="text-center">
                                                <?php if ($permissions[$r][$key]): ?>
                                                    <i class="bi bi-check-circle-fill text-success" style="font-size:12px"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-x-circle-fill text-danger" style="font-size:12px;opacity:.4"></i>
                                                <?php endif; ?>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Légende rôles -->
                    <div class="card mt-3">
                        <div class="card-header">Description des rôles</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="role-badge role-administrateur"><i class="bi bi-shield-fill"></i> Administrateur</span>
                                </div>
                                <p class="text-muted small mb-0">Accès complet : gestion des utilisateurs, suppression, annulation de ventes, rapports complets.</p>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="role-badge role-gestionnaire"><i class="bi bi-tools"></i> Gestionnaire</span>
                                </div>
                                <p class="text-muted small mb-0">Gestion du stock et des articles, consultation des rapports, mais pas d'accès aux utilisateurs.</p>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="role-badge role-vendeur"><i class="bi bi-person-badge"></i> Vendeur / Caissier</span>
                                </div>
                                <p class="text-muted small mb-0">Uniquement les ventes et alertes. Ne peut voir que ses propres ventes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout -->
    <div class="modal fade" id="ajoutModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="ajouter">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Nouvel utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Nom complet *</label>
                        <input type="text" name="nom" class="form-control" placeholder="Ex: Koffi Mensah" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Adresse email *</label>
                        <input type="email" name="email" class="form-control" placeholder="exemple@domaine.com" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Mot de passe * <small class="text-muted">(min. 6 caractères)</small></label>
                        <div class="input-group">
                            <input type="password" name="password" id="newPassword" class="form-control" minlength="6" required autocomplete="new-password">
                            <span class="input-group-text" style="cursor:pointer"
                                  onclick="togglePwd('newPassword','eyeNew')">
                                <i class="bi bi-eye" id="eyeNew"></i>
                            </span>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Rôle *</label>
                        <select name="role" class="form-select">
                            <option value="vendeur">Vendeur / Caissier</option>
                            <option value="gestionnaire">Gestionnaire de stock</option>
                            <option value="administrateur">Administrateur</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="actif" id="newActif" checked>
                        <label class="form-check-label" for="newActif">Compte actif immédiatement</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Créer</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/script.js"></script>
    <script>
        // Recherche live
        document.getElementById('userSearch').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#usersTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        // Toggle password visibility
        function togglePwd(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
    <?php if (isset($_SESSION['toast'])): ?>
    <script>showToast('<?= addslashes($_SESSION['toast']['msg']) ?>', '<?= $_SESSION['toast']['type'] ?>');</script>
    <?php unset($_SESSION['toast']); endif; ?>
</body>
</html>