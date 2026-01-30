<?php
require_once __DIR__ . '/../config/init.php';

/**
 * Calcule la répartition automatique d'une dépense selon les plafonds
 * Retourne ['solange' => montant, 'mathieu' => montant]
 */
function calculerRepartitionDepense($montantEur) {
    $pdo = getDB();
    
    // Récupérer les budgets actuels
    $stmt = $pdo->prepare("
        SELECT utilisateur_id, restant 
        FROM budget 
        WHERE utilisateur_id IN (1, 2)
        ORDER BY utilisateur_id
    ");
    $stmt->execute();
    $budgets = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $budgetSolange = $budgets[1] ?? PLAFOND_SOLANGE;
    $budgetMathieu = $budgets[2] ?? PLAFOND_MATHIEU;
    
    $repartition = ['solange' => 0, 'mathieu' => 0];
    
    // Priorité : Solange d'abord
    if ($budgetSolange > 0) {
        if ($montantEur <= $budgetSolange) {
            $repartition['solange'] = $montantEur;
        } else {
            $repartition['solange'] = $budgetSolange;
            $repartition['mathieu'] = $montantEur - $budgetSolange;
        }
    } else {
        // Solange a atteint son plafond, tout sur Mathieu
        $repartition['mathieu'] = $montantEur;
    }
    
    return $repartition;
}

/**
 * Met à jour les budgets après une dépense
 */
function mettreAJourBudget($utilisateurId, $montantEur) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        UPDATE budget 
        SET utilise = utilise + ?, 
            restant = plafond - (utilise + ?)
        WHERE utilisateur_id = ?
    ");
    $stmt->execute([$montantEur, $montantEur, $utilisateurId]);
}

/**
 * Calcule le nombre de jours jusqu'au mariage
 */
function joursRestants() {
    $dateMariage = new DateTime(DATE_MARIAGE_RELIGIEUX);
    $aujourdhui = new DateTime();
    $diff = $aujourdhui->diff($dateMariage);
    
    if ($dateMariage < $aujourdhui) {
        return 0;
    }
    
    return $diff->days;
}

/**
 * Récupère toutes les alertes pour le dashboard
 */
function getAlertes() {
    $pdo = getDB();
    $alertes = [];
    
    // Tâches en retard
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM taches 
        WHERE date_limite < CURDATE() AND statut != 'terminé'
    ");
    $count = $stmt->fetch()['count'];
    if ($count > 0) {
        $alertes[] = [
            'type' => 'danger',
            'message' => "$count tâche(s) en retard"
        ];
    }
    
    // Prestataires à payer (solde > 0 et date limite proche ou passée)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM prestataires 
        WHERE solde > 0 AND date_limite <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $count = $stmt->fetch()['count'];
    if ($count > 0) {
        $alertes[] = [
            'type' => 'info',
            'message' => "$count prestataire(s) à payer bientôt"
        ];
    }
    
    return $alertes;
}

/**
 * Formate un montant selon la devise
 */
function formaterMontant($montant, $devise) {
    if ($devise === 'XOF') {
        return number_format($montant, 0, ',', ' ') . ' XOF';
    } else {
        return number_format($montant, 2, ',', ' ') . ' €';
    }
}
?>
