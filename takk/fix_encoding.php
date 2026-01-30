<?php
/**
 * Script pour corriger l'encodage UTF-8 dans la base de données
 * À exécuter une seule fois pour corriger les données mal encodées
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=UTF-8');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // Forcer UTF-8
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Correction encodage</title></head><body>";
    echo "<h1>Correction de l'encodage UTF-8</h1>";
    echo "<pre>";
    
    // Vérifier et corriger les tables avec des champs texte
    $tables = [
        'evenements' => ['nom', 'pays'],
        'prestataires' => ['nom', 'type', 'pays', 'contact'],
        'depenses' => ['nom'],
        'taches' => ['description', 'responsable'],
        'documents' => ['nom', 'type'],
        'utilisateurs' => ['nom', 'email']
    ];
    
    foreach ($tables as $table => $columns) {
        echo "\n=== Table: $table ===\n";
        
        // Vérifier l'encodage actuel de la table
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        $create = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Encodage actuel: " . (preg_match("/CHARSET=([^\s]+)/", $create['Create Table'], $matches) ? $matches[1] : 'non trouvé') . "\n";
        
        // Lire toutes les données
        $stmt = $pdo->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $id = $row['id'] ?? null;
            if (!$id) continue;
            
            $updates = [];
            foreach ($columns as $col) {
                if (!isset($row[$col])) continue;
                
                $value = $row[$col];
                if ($value === null || $value === '') continue;
                
                // Vérifier si la valeur contient des caractères mal encodés
                // Si c'est déjà en UTF-8 valide, on ne touche pas
                if (mb_check_encoding($value, 'UTF-8')) {
                    // Vérifier s'il y a des problèmes d'encodage visibles
                    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
                        echo "  ID $id, colonne $col: contient des caractères de contrôle\n";
                        // Essayer de réencoder
                        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                        $updates[$col] = $value;
                    }
                } else {
                    echo "  ID $id, colonne $col: encodage invalide, correction...\n";
                    // Essayer de convertir depuis ISO-8859-1 (Latin-1) vers UTF-8
                    $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                    $updates[$col] = $value;
                }
            }
            
            // Mettre à jour si nécessaire
            if (!empty($updates)) {
                $setParts = [];
                $params = [];
                foreach ($updates as $col => $val) {
                    $setParts[] = "$col = ?";
                    $params[] = $val;
                }
                $params[] = $id;
                
                $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                echo "  ✓ ID $id corrigé\n";
            }
        }
    }
    
    // Vérifier que toutes les tables sont en utf8mb4
    echo "\n=== Vérification finale ===\n";
    $stmt = $pdo->query("SELECT TABLE_NAME, TABLE_COLLATION 
                         FROM information_schema.TABLES 
                         WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                         AND TABLE_TYPE = 'BASE TABLE'");
    $tables_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tables_info as $info) {
        $table = $info['TABLE_NAME'];
        $collation = $info['TABLE_COLLATION'];
        echo "Table $table: $collation\n";
        
        if (strpos($collation, 'utf8mb4') === false) {
            echo "  ⚠️ Conversion nécessaire vers utf8mb4_unicode_ci\n";
            try {
                $pdo->exec("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "  ✓ Converti\n";
            } catch (PDOException $e) {
                echo "  ✗ Erreur: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Correction terminée!\n";
    echo "</pre>";
    echo "<p><a href='dashboard.php'>Retour au dashboard</a></p>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
