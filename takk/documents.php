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

// Créer le dossier uploads s'il n'existe pas
$uploadDir = __DIR__ . '/assets/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'ajouter') {
            $nom = $_POST['nom'] ?? '';
            $type = $_POST['type'] ?? '';
            $evenement_id = intval($_POST['evenement_id'] ?? 0);
            
            if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['fichier'];
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                
                if (in_array($file['type'], $allowedTypes)) {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO documents (nom, type, fichier, evenement_id)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$nom, $type, $filename, $evenement_id]);
                        $message = "Document ajouté";
                    } else {
                        $error = "Erreur lors de l'upload";
                    }
                } else {
                    $error = "Type de fichier non autorisé (PDF, JPG, PNG uniquement)";
                }
            } else {
                $error = "Veuillez sélectionner un fichier";
            }
        } elseif ($_POST['action'] === 'supprimer') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT fichier FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();
            
            if ($doc) {
                $filepath = $uploadDir . $doc['fichier'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Document supprimé";
            }
        }
    }
}

// Récupérer tous les documents
$stmt = $pdo->query("
    SELECT d.*, e.nom as evenement
    FROM documents d
    JOIN evenements e ON d.evenement_id = e.id
    ORDER BY d.created_at DESC
");
$documents = $stmt->fetchAll();

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

$currentPage = 'documents';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - Solange & Mathieu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="header-title">Documents</h1>
        </header>
        
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <span>❌</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <h2 class="card-title">Ajouter un document</h2>
                </div>
                <form method="POST" enctype="multipart/form-data" class="grid grid-2">
                    <input type="hidden" name="action" value="ajouter">
                    
                    <div class="form-group">
                        <label class="form-label">Nom du document</label>
                        <input type="text" name="nom" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="type" required class="form-input">
                            <option value="contrat">Contrat</option>
                            <option value="devis">Devis</option>
                            <option value="facture">Facture</option>
                            <option value="justificatif">Justificatif</option>
                            <option value="autre">Autre</option>
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
                        <label class="form-label">Fichier (PDF, JPG, PNG)</label>
                        <input type="file" name="fichier" accept=".pdf,.jpg,.jpeg,.png" required class="form-input">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-primary">Ajouter le document</button>
                    </div>
                </form>
            </div>

            <!-- Liste des documents -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Liste des documents</h2>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Événement</th>
                                <th>Date d'ajout</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documents)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--gray-400); padding: 40px;">
                                        Aucun document enregistré
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?= htmlspecialchars($doc['nom']) ?></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?= htmlspecialchars($doc['type']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($doc['evenement']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></td>
                                        <td>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <a href="assets/uploads/<?= htmlspecialchars($doc['fichier']) ?>" target="_blank" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Voir</a>
                                                <form method="POST" onsubmit="return confirm('Supprimer ce document ?')" style="display: inline;">
                                                    <input type="hidden" name="action" value="supprimer">
                                                    <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Supprimer</button>
                                                </form>
                                            </div>
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
