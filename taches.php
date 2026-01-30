<?php
require_once 'config/init.php';
require_once 'includes/functions.php';
requireLogin();

$pdo = getDB();
$user = getCurrentUser();
$message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'ajouter') {
            $description = $_POST['description'] ?? '';
            $responsable = $_POST['responsable'] ?? '';
            $date_limite = $_POST['date_limite'] ?? null;
            $evenement_id = intval($_POST['evenement_id'] ?? 0);
            $statut = $_POST['statut'] ?? 'à faire';
            
            $stmt = $pdo->prepare("
                INSERT INTO taches (description, responsable, date_limite, evenement_id, statut)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$description, $responsable, $date_limite ?: null, $evenement_id, $statut]);
            $message = "Tâche ajoutée";
        } elseif ($_POST['action'] === 'modifier') {
            $id = intval($_POST['id'] ?? 0);
            $statut = $_POST['statut'] ?? 'à faire';
            
            $stmt = $pdo->prepare("UPDATE taches SET statut = ? WHERE id = ?");
            $stmt->execute([$statut, $id]);
            $message = "Statut de la tâche mis à jour";
        } elseif ($_POST['action'] === 'supprimer') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM taches WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Tâche supprimée";
        }
    }
}

// Récupérer toutes les tâches
$stmt = $pdo->query("
    SELECT t.*, e.nom as evenement
    FROM taches t
    JOIN evenements e ON t.evenement_id = e.id
    ORDER BY t.date_limite ASC, t.statut, t.id
");
$taches = $stmt->fetchAll();

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

$currentPage = 'taches';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tâches - Solange & Mathieu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="header-title">Tâches</h1>
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
                    <h2 class="card-title">Ajouter une tâche</h2>
                </div>
                <form method="POST" class="grid grid-2">
                    <input type="hidden" name="action" value="ajouter">
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Description</label>
                        <textarea name="description" required class="form-input" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Responsable</label>
                        <select name="responsable" required class="form-input">
                            <option value="Mathieu">Mathieu</option>
                            <option value="Solange">Solange</option>
                            <option value="ensemble">Ensemble</option>
                        </select>
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
                        <label class="form-label">Date limite</label>
                        <input type="date" name="date_limite" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-input">
                            <option value="à faire">À faire</option>
                            <option value="en cours">En cours</option>
                            <option value="terminé">Terminé</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-primary">Ajouter la tâche</button>
                    </div>
                </form>
            </div>

            <!-- Liste des tâches -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Liste des tâches</h2>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Responsable</th>
                                <th>Événement</th>
                                <th>Date limite</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($taches)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--gray-400); padding: 40px;">
                                        Aucune tâche enregistrée
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($taches as $tache): ?>
                                    <?php
                                    $enRetard = $tache['date_limite'] && strtotime($tache['date_limite']) < time() && $tache['statut'] !== 'terminé';
                                    ?>
                                    <tr style="<?= $enRetard ? 'background: rgba(239, 68, 68, 0.1);' : '' ?>">
                                        <td style="font-weight: 500;"><?= nl2br(htmlspecialchars($tache['description'])) ?></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?= htmlspecialchars($tache['responsable']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($tache['evenement']) ?></td>
                                        <td>
                                            <?= $tache['date_limite'] ? date('d/m/Y', strtotime($tache['date_limite'])) : '-' ?>
                                            <?php if ($enRetard): ?>
                                                <span style="color: var(--danger); margin-left: 8px;">⚠️</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="modifier">
                                                <input type="hidden" name="id" value="<?= $tache['id'] ?>">
                                                <select name="statut" onchange="this.form.submit()" class="form-input" style="width: auto; padding: 6px 12px; font-size: 13px;">
                                                    <option value="à faire" <?= $tache['statut'] === 'à faire' ? 'selected' : '' ?>>À faire</option>
                                                    <option value="en cours" <?= $tache['statut'] === 'en cours' ? 'selected' : '' ?>>En cours</option>
                                                    <option value="terminé" <?= $tache['statut'] === 'terminé' ? 'selected' : '' ?>>Terminé</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Supprimer cette tâche ?')" style="display: inline;">
                                                <input type="hidden" name="action" value="supprimer">
                                                <input type="hidden" name="id" value="<?= $tache['id'] ?>">
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
