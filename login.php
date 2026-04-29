<?php
session_start();
require_once 'config.php';
require_once 'database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = '';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erreur de validation CSRF');
    }

    if ($_SESSION['login_attempts'] >= 5) {
        $error = "Trop de tentatives échouées. Veuillez réessayer plus tard.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
        } else {
            // CORRECTION : comparaison mot de passe en texte clair (sans hachage)
            $stmt = mysqli_prepare($conn, "SELECT id, nom, email, password, role FROM users WHERE email = ? AND actif = 1");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user && $password === $user['password']) {
                $_SESSION['login_attempts'] = 0;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom']     = $user['nom'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];
                header('Location: dashboard.php');
                exit;
            } else {
                $_SESSION['login_attempts']++;
                $error = "Email ou mot de passe incorrect.";
            }
        }
    }
}

if ($msg === 'logout') {
    $success_msg = "Déconnexion réussie.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .login-page { display: flex; min-height: 100vh; }
        .login-left {
            flex: 4;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-align: center;
        }
        .login-right {
            flex: 6;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background: white;
        }
        @media (max-width: 768px) {
            .login-left { display: none; }
            .login-right { flex: 1; }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-left">
            <i class="bi bi-tools" style="font-size:3rem;"></i>
            <h1><?= APP_NAME ?></h1>
            <p>Gérez votre quincaillerie simplement</p>
            <ul class="list-unstyled mt-3">
                <li><i class="bi bi-shield-check"></i> Sécurisé</li>
                <li><i class="bi bi-lightning-charge"></i> Rapide</li>
                <li><i class="bi bi-check-circle"></i> Fiable</li>
            </ul>
        </div>

        <div class="login-right">
            <div class="login-form" style="width:100%;max-width:420px;">
                <h2 class="mb-4">Connexion à votre espace</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="form-group mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword()">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>

                <div class="mt-3 text-center">
                    <small class="text-muted">&copy; <?= date('Y') ?> <?= APP_NAME ?> - Tous droits réservés</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('togglePasswordIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
