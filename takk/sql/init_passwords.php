<?php
/**
 * Script pour initialiser les mots de passe des utilisateurs
 * À exécuter une seule fois après la création de la base de données
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Mot de passe par défaut : "matt123"
    $password = password_hash('matt123', PASSWORD_DEFAULT);
    
    // Mettre à jour les mots de passe
    $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id IN (1, 2)");
    $stmt->execute([$password]);
    
    echo "✅ Mots de passe initialisés avec succès !\n";
    echo "Mot de passe pour les deux comptes : matt123\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?>
