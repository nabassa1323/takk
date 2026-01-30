<?php
// Initialisation de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration de la base de données
require_once __DIR__ . '/database.php';

// Fonction de connexion à la base de données
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
            // Forcer l'encodage UTF-8 de manière explicite
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("SET CHARACTER SET utf8mb4");
            $pdo->exec("SET character_set_connection=utf8mb4");
            $pdo->exec("SET character_set_results=utf8mb4");
            $pdo->exec("SET character_set_client=utf8mb4");
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Fonction de conversion XOF vers EUR
function xofToEur($montantXof) {
    return round($montantXof / TAUX_XOF_EUR, 2);
}

// Fonction de conversion EUR vers XOF
function eurToXof($montantEur) {
    return round($montantEur * TAUX_XOF_EUR, 0);
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_nom']);
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

// Fonction pour obtenir l'utilisateur actuel
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDB();
    // FETCH_ASSOC fonctionne maintenant
    $stmt = $pdo->prepare("SELECT id, nom, email, mot_de_passe FROM utilisateurs WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
