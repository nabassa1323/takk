<?php
// Démarrer la session AVANT tout autre code
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/init.php';
require_once 'includes/functions.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    if (!headers_sent()) {
        header('Location: index.php?error=not_logged_in');
        exit;
    } else {
        echo '<script>window.location.href = "index.php?error=not_logged_in";</script>';
        exit;
    }
}

$pdo = getDB();
$user = getCurrentUser();

// Si l'utilisateur n'est pas trouvé, déconnecter et rediriger
if (!$user) {
    session_destroy();
    header('Location: index.php?error=session_expired');
    exit;
}

// Récupérer les budgets
$stmt = $pdo->query("
    SELECT u.nom, b.plafond, b.utilise, b.restant 
    FROM budget b 
    JOIN utilisateurs u ON b.utilisateur_id = u.id 
    ORDER BY u.id
");
$budgets = $stmt->fetchAll();

// Budget global
$budgetGlobal = array_sum(array_column($budgets, 'plafond'));
$utiliseGlobal = array_sum(array_column($budgets, 'utilise'));
$restantGlobal = $budgetGlobal - $utiliseGlobal;

// Dépenses par événement
$stmt = $pdo->query("
    SELECT e.nom as evenement, e.devise, 
           SUM(d.montant_local) as total_local,
           SUM(d.montant_euro) as total_euro
    FROM depenses d
    JOIN evenements e ON d.evenement_id = e.id
    GROUP BY e.id, e.nom, e.devise
");
$depensesParEvenement = $stmt->fetchAll();

// Alertes
$alertes = getAlertes();

// Jours restants
$joursRestants = joursRestants();

$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Solange & Mathieu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="header-title">Dashboard</h1>
        </header>
        
        <div class="container">
            <!-- Compte à rebours -->
            <div class="countdown-card">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px; font-weight: 500;">Jours restants</div>
                <div class="countdown-number"><?= $joursRestants ?></div>
                <div style="font-size: 16px; opacity: 0.9; margin-top: 8px;">Mariage religieux - 28 Novembre 2026</div>
            </div>

            <!-- Alertes -->
            <?php if (!empty($alertes)): ?>
                <div class="grid" style="margin-bottom: 32px;">
                    <?php foreach ($alertes as $alerte): ?>
                        <div class="alert alert-<?= $alerte['type'] ?>">
                            <span>⚠️</span>
                            <span><?= htmlspecialchars($alerte['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Budget global -->
            <div class="grid grid-3" style="margin-bottom: 32px;">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-label">Budget total</div>
                        <div class="stat-icon" style="background: rgba(99, 102, 241, 0.1);">💰</div>
                    </div>
                    <div class="stat-value"><?= number_format($budgetGlobal, 2, ',', ' ') ?> €</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-label">Dépensé</div>
                        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1);">💸</div>
                    </div>
                    <div class="stat-value negative"><?= number_format($utiliseGlobal, 2, ',', ' ') ?> €</div>
                    <div style="font-size: 13px; color: var(--gray-500); margin-top: 8px;">
                        <?= $budgetGlobal > 0 ? number_format(($utiliseGlobal / $budgetGlobal) * 100, 1) : 0 ?>% du budget
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-label">Restant</div>
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1);">✨</div>
                    </div>
                    <div class="stat-value positive"><?= number_format($restantGlobal, 2, ',', ' ') ?> €</div>
                </div>
            </div>

            <!-- Budgets individuels -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <h2 class="card-title">Budgets individuels</h2>
                </div>
                <div class="grid grid-2">
                    <?php foreach ($budgets as $budget): ?>
                        <div style="padding: 20px; background: var(--gray-50); border-radius: 12px;">
                            <div style="font-size: 18px; font-weight: 600; color: var(--gray-900); margin-bottom: 16px;">
                                <?= htmlspecialchars($budget['nom']) ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 14px; color: var(--gray-600); margin-bottom: 8px;">
                                <span>Plafond</span>
                                <strong style="color: var(--gray-900);"><?= number_format($budget['plafond'], 2, ',', ' ') ?> €</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 14px; color: var(--gray-600); margin-bottom: 8px;">
                                <span>Utilisé</span>
                                <strong style="color: var(--danger);"><?= number_format($budget['utilise'], 2, ',', ' ') ?> €</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 14px; color: var(--gray-600); margin-bottom: 12px;">
                                <span>Restant</span>
                                <strong style="color: var(--success);"><?= number_format($budget['restant'], 2, ',', ' ') ?> €</strong>
                            </div>
                            <div style="height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: linear-gradient(90deg, var(--primary), var(--secondary)); width: <?= min(100, ($budget['utilise'] / $budget['plafond']) * 100) ?>%; transition: width 0.3s;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dépenses par événement -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <h2 class="card-title">Dépenses par événement</h2>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Événement</th>
                                <th>Montant local</th>
                                <th>Montant EUR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($depensesParEvenement)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--gray-400); padding: 40px;">
                                        Aucune dépense enregistrée
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($depensesParEvenement as $dep): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?= htmlspecialchars($dep['evenement']) ?></td>
                                        <td><?= formaterMontant($dep['total_local'], $dep['devise']) ?></td>
                                        <td><strong><?= formaterMontant($dep['total_euro'], 'EUR') ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Actions rapides</h2>
                </div>
                <div class="grid grid-4">
                    <a href="depenses.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div style="text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 12px;">💸</div>
                            <div style="font-size: 16px; font-weight: 600; color: var(--gray-900); margin-bottom: 4px;">Dépenses</div>
                            <div style="font-size: 13px; color: var(--gray-500);">Gérer les dépenses</div>
                        </div>
                    </a>
                    <a href="prestataires.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div style="text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 12px;">👔</div>
                            <div style="font-size: 16px; font-weight: 600; color: var(--gray-900); margin-bottom: 4px;">Prestataires</div>
                            <div style="font-size: 13px; color: var(--gray-500);">Suivre les prestataires</div>
                        </div>
                    </a>
                    <a href="taches.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div style="text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 12px;">✅</div>
                            <div style="font-size: 16px; font-weight: 600; color: var(--gray-900); margin-bottom: 4px;">Tâches</div>
                            <div style="font-size: 13px; color: var(--gray-500);">Organiser les tâches</div>
                        </div>
                    </a>
                    <a href="documents.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div style="text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 12px;">📁</div>
                            <div style="font-size: 16px; font-weight: 600; color: var(--gray-900); margin-bottom: 4px;">Documents</div>
                            <div style="font-size: 13px; color: var(--gray-500);">Gérer les documents</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
