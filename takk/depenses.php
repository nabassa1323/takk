<?php
// Forcer l'encodage UTF-8
header('Content-Type: text/html; charset=UTF-8');

require_once 'config/init.php';
require_once 'includes/functions.php';
requireLogin();

$pdo = getDB();
$user = getCurrentUser();
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'ajouter') {
            $nom = $_POST['nom'] ?? '';
            $montant_local = floatval($_POST['montant_local'] ?? 0);
            $devise = $_POST['devise'] ?? 'EUR';
            $evenement_id = intval($_POST['evenement_id'] ?? 0);
            $date = $_POST['date'] ?? date('Y-m-d');
            $statut = $_POST['statut'] ?? 'prévu';
            
            // Calcul du montant en EUR
            $montant_euro = $devise === 'XOF' ? xofToEur($montant_local) : $montant_local;
            
            // Calcul de la répartition automatique
            $repartition = calculerRepartitionDepense($montant_euro);
            
            // Déterminer qui paie
            $paye_par = $repartition['solange'] > 0 ? 1 : 2;
            
            // Si répartition mixte, créer deux dépenses
            if ($repartition['solange'] > 0 && $repartition['mathieu'] > 0) {
                // Dépense Solange
                $montant_solange = $devise === 'XOF' ? eurToXof($repartition['solange']) : $repartition['solange'];
                $montant_euro_solange = $repartition['solange'];
                
                $stmt = $pdo->prepare("
                    INSERT INTO depenses (nom, montant_local, devise, montant_euro, evenement_id, paye_par, statut, date)
                    VALUES (?, ?, ?, ?, ?, 1, ?, ?)
                ");
                $stmt->execute([$nom . ' (Solange)', $montant_solange, $devise, $montant_euro_solange, $evenement_id, $statut, $date]);
                mettreAJourBudget(1, $montant_euro_solange);
                
                // Dépense Mathieu
                $montant_mathieu = $devise === 'XOF' ? eurToXof($repartition['mathieu']) : $repartition['mathieu'];
                $montant_euro_mathieu = $repartition['mathieu'];
                
                $stmt = $pdo->prepare("
                    INSERT INTO depenses (nom, montant_local, devise, montant_euro, evenement_id, paye_par, statut, date)
                    VALUES (?, ?, ?, ?, ?, 2, ?, ?)
                ");
                $stmt->execute([$nom . ' (Mathieu)', $montant_mathieu, $devise, $montant_euro_mathieu, $evenement_id, $statut, $date]);
                mettreAJourBudget(2, $montant_euro_mathieu);
                
                $message = "Dépense ajoutée avec répartition automatique : Solange " . formaterMontant($repartition['solange'], 'EUR') . " + Mathieu " . formaterMontant($repartition['mathieu'], 'EUR');
            } else {
                // Dépense unique
                $stmt = $pdo->prepare("
                    INSERT INTO depenses (nom, montant_local, devise, montant_euro, evenement_id, paye_par, statut, date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nom, $montant_local, $devise, $montant_euro, $evenement_id, $paye_par, $statut, $date]);
                mettreAJourBudget($paye_par, $montant_euro);
                
                $payeur = $paye_par === 1 ? 'Solange' : 'Mathieu';
                $message = "Dépense ajoutée et imputée à $payeur";
            }
        } elseif ($_POST['action'] === 'supprimer') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM depenses WHERE id = ?");
            $stmt->execute([$id]);
            $depense = $stmt->fetch();
            
            if ($depense) {
                // Rembourser le budget
                $montant_rembourse = -$depense['montant_euro'];
                mettreAJourBudget($depense['paye_par'], $montant_rembourse);
                
                // Supprimer la dépense
                $stmt = $pdo->prepare("DELETE FROM depenses WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Dépense supprimée";
            }
        }
    }
}

// Récupérer toutes les dépenses
$stmt = $pdo->query("
    SELECT d.*, e.nom as evenement, u.nom as payeur
    FROM depenses d
    JOIN evenements e ON d.evenement_id = e.id
    JOIN utilisateurs u ON d.paye_par = u.id
    ORDER BY d.date DESC, d.id DESC
");
$depenses = $stmt->fetchAll();

// Récupérer les événements avec colonnes explicites
$stmt = $pdo->query("SELECT id, nom, pays, date, devise FROM evenements ORDER BY date");
$rows = $stmt->fetchAll(PDO::FETCH_NUM);
$evenements = [];
foreach ($rows as $row) {
    $evenements[] = [
        'id' => $row[0],
        'nom' => $row[1],
        'pays' => $row[2],
        'date' => $row[3],
        'devise' => $row[4]
    ];
}

$currentPage = 'depenses';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dépenses - Solange & Mathieu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="header-title">Dépenses</h1>
        </header>
        
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <h2 class="card-title">Ajouter une dépense</h2>
                </div>
                <form method="POST" class="grid grid-2">
                    <input type="hidden" name="action" value="ajouter">
                    
                    <div class="form-group">
                        <label class="form-label">Nom de la dépense</label>
                        <input type="text" name="nom" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Événement</label>
                        <select name="evenement_id" required class="form-input">
                            <?php foreach ($evenements as $evt): ?>
                                <option value="<?= $evt['id'] ?>"><?= htmlspecialchars($evt['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Montant</label>
                        <input type="number" name="montant_local" step="0.01" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Devise</label>
                        <select name="devise" required class="form-input">
                            <option value="EUR">EUR (€)</option>
                            <option value="XOF">XOF (CFA)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" value="<?= date('Y-m-d') ?>" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-input">
                            <option value="prévu">Prévu</option>
                            <option value="payé">Payé</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-primary">Ajouter la dépense</button>
                        <p style="font-size: 13px; color: var(--gray-500); margin-top: 12px;">
                            La répartition entre Solange et Mathieu se fait automatiquement selon les plafonds
                        </p>
                    </div>
                </form>
            </div>

            <!-- Liste des dépenses -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Liste des dépenses</h2>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Événement</th>
                                <th>Montant local</th>
                                <th>Montant EUR</th>
                                <th>Payé par</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($depenses)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--gray-400); padding: 40px;">
                                        Aucune dépense enregistrée
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($depenses as $dep): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?= htmlspecialchars($dep['nom']) ?></td>
                                        <td><?= htmlspecialchars($dep['evenement']) ?></td>
                                        <td><?= formaterMontant($dep['montant_local'], $dep['devise']) ?></td>
                                        <td><strong><?= formaterMontant($dep['montant_euro'], 'EUR') ?></strong></td>
                                        <td>
                                            <span class="badge <?= $dep['payeur'] === 'Solange' ? 'badge-primary' : 'badge-success' ?>">
                                                <?= htmlspecialchars($dep['payeur']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $dep['statut'] === 'payé' ? 'badge-success' : 'badge-warning' ?>">
                                                <?= htmlspecialchars($dep['statut']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($dep['date'])) ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Supprimer cette dépense ?')" style="display: inline;">
                                                <input type="hidden" name="action" value="supprimer">
                                                <input type="hidden" name="id" value="<?= $dep['id'] ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
