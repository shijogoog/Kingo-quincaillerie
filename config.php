<?php
/**
 * Fichier de configuration principal
 * Définit les constantes et fonctions d'authentification
 */

if (!defined('ACCESS_ALLOWED') && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Accès interdit');
}

// === CONSTANTES DE CONNEXION BDD ===
define('DB_HOST', 'localhost');
define('DB_NAME', 'quincaillerie');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// === CONSTANTES APPLICATION ===
define('APP_NAME', 'QuincaStore');
define('STOCK_ALERTE_SEUIL', 5);

/**
 * Formate un montant en Franc CFA
 */
function fcfa($montant) {
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

/**
 * Vérifie si l'utilisateur est authentifié et dispose du rôle requis
 */
function checkAuth($roles = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    if (!empty($roles) && !in_array($_SESSION['role'], $roles)) {
        $_SESSION['error'] = "Accès refusé : vous n'avez pas les droits nécessaires.";
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Retourne les informations de l'utilisateur connecté
 */
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) ? [
        'id'    => $_SESSION['user_id'],
        'nom'   => $_SESSION['nom'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role']
    ] : null;
}
