<?php
// Forcer l'encodage UTF-8
header('Content-Type: text/html; charset=UTF-8');

require_once 'config/init.php';
require_once 'includes/functions.php';
requireLogin();

$pdo = getDB();
$user = getCurrentUser();
$message = '';

// Fonction pour créer une dépense depuis un prestataire
function creerDepenseDepuisPrestataire($pdo, $nom_prestataire, $montant, $devise, $evenement_id, $type_paiement = 'acompte') {
    $nom_depense = "$nom_prestataire - $type_paiement";
    $date = date('Y-m-d');
    $statut = 'payé';
    
    // Calcul du montant en EUR
    $montant_euro = $devise === 'XOF' ? xofToEur($montant) : $montant;
    
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
        $stmt->execute([$nom_depense . ' (Solange)', $montant_solange, $devise, $montant_euro_solange, $evenement_id, $statut, $date]);
        mettreAJourBudget(1, $montant_euro_solange);
        
        // Dépense Mathieu
        $montant_mathieu = $devise === 'XOF' ? eurToXof($repartition['mathieu']) : $repartition['mathieu'];
        $montant_euro_mathieu = $repartition['mathieu'];
        
        $stmt = $pdo->prepare("
            INSERT INTO depenses (nom, montant_local, devise, montant_euro, evenement_id, paye_par, statut, date)
            VALUES (?, ?, ?, ?, ?, 2, ?, ?)
        ");
        $stmt->execute([$nom_depense . ' (Mathieu)', $montant_mathieu, $devise, $montant_euro_mathieu, $evenement_id, $statut, $date]);
        mettreAJourBudget(2, $montant_euro_mathieu);
    } else {
        // Dépense unique
        $stmt = $pdo->prepare("
            INSERT INTO depenses (nom, montant_local, devise, montant_euro, evenement_id, paye_par, statut, date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nom_depense, $montant, $devise, $montant_euro, $evenement_id, $paye_par, $statut, $date]);
        mettreAJourBudget($paye_par, $montant_euro);
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'ajouter') {
            $nom = $_POST['nom'] ?? '';
            $type = $_POST['type'] ?? '';
            $pays = $_POST['pays'] ?? '';
            $devise = $_POST['devise'] ?? 'EUR';
            $montant_total = floatval($_POST['montant_total'] ?? 0);
            $acompte = floatval($_POST['acompte'] ?? 0);
            $date_limite = $_POST['date_limite'] ?? null;
            $evenement_id = intval($_POST['evenement_id'] ?? 0);
            $contact = $_POST['contact'] ?? '';
            
            // Calculer le solde dans la devise d'origine
            $solde = $montant_total - $acompte;
            
            // Vérifier si la colonne devise existe, sinon on l'ajoute
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO prestataires (nom, type, pays, montant_total, acompte, solde, date_limite, evenement_id, contact, devise)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nom, $type, $pays, $montant_total, $acompte, $solde, $date_limite ?: null, $evenement_id, $contact, $devise]);
                $prestataire_id = $pdo->lastInsertId();
            } catch (PDOException $e) {
                // Si la colonne devise n'existe pas, on l'ajoute
                if (strpos($e->getMessage(), 'devise') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                    try {
                        $pdo->exec("ALTER TABLE prestataires ADD COLUMN devise VARCHAR(10) DEFAULT 'EUR'");
                    } catch (PDOException $e2) {
                        // Colonne peut-être déjà ajoutée
                    }
                    // Réessayer l'insertion
                    $stmt = $pdo->prepare("
                        INSERT INTO prestataires (nom, type, pays, montant_total, acompte, solde, date_limite, evenement_id, contact, devise)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$nom, $type, $pays, $montant_total, $acompte, $solde, $date_limite ?: null, $evenement_id, $contact, $devise]);
                    $prestataire_id = $pdo->lastInsertId();
                } else {
                    throw $e;
                }
            }
            
            // Créer une dépense si acompte > 0
            if ($acompte > 0) {
                creerDepenseDepuisPrestataire($pdo, $nom, $acompte, $devise, $evenement_id, 'acompte');
                $message = "Prestataire ajouté et acompte enregistré comme dépense";
            } else {
                $message = "Prestataire ajouté";
            }
            
        } elseif ($_POST['action'] === 'modifier') {
            $id = intval($_POST['id'] ?? 0);
            $nom = $_POST['nom'] ?? '';
            $type = $_POST['type'] ?? '';
            $pays = $_POST['pays'] ?? '';
            $devise = $_POST['devise'] ?? 'EUR';
            $montant_total = floatval($_POST['montant_total'] ?? 0);
            $acompte = floatval($_POST['acompte'] ?? 0);
            $date_limite = $_POST['date_limite'] ?? null;
            $evenement_id = intval($_POST['evenement_id'] ?? 0);
            $contact = $_POST['contact'] ?? '';
            
            // Récupérer l'ancien acompte
            $stmt = $pdo->prepare("SELECT acompte FROM prestataires WHERE id = ?");
            $stmt->execute([$id]);
            $ancien_prestataire = $stmt->fetch(PDO::FETCH_NUM);
            $ancien_acompte = $ancien_prestataire ? floatval($ancien_prestataire[0]) : 0;
            
            // Calculer le solde
            $solde = $montant_total - $acompte;
            
            // Mettre à jour le prestataire
            $stmt = $pdo->prepare("
                UPDATE prestataires 
                SET nom = ?, type = ?, pays = ?, montant_total = ?, acompte = ?, solde = ?, date_limite = ?, evenement_id = ?, contact = ?, devise = ?
                WHERE id = ?
            ");
            $stmt->execute([$nom, $type, $pays, $montant_total, $acompte, $solde, $date_limite ?: null, $evenement_id, $contact, $devise, $id]);
            
            // Si le nouvel acompte est supérieur à l'ancien, créer une dépense pour la différence
            if ($acompte > $ancien_acompte) {
                $difference = $acompte - $ancien_acompte;
                creerDepenseDepuisPrestataire($pdo, $nom, $difference, $devise, $evenement_id, 'acompte');
                $message = "Prestataire modifié et paiement supplémentaire enregistré comme dépense";
            } else {
                $message = "Prestataire modifié";
            }
            
        } elseif ($_POST['action'] === 'paiement') {
            $id = intval($_POST['id'] ?? 0);
            $type_paiement = $_POST['type_paiement'] ?? 'acompte'; // 'acompte' ou 'solde'
            $montant = floatval($_POST['montant'] ?? 0);
            
            // Récupérer le prestataire
            $stmt = $pdo->prepare("SELECT id, nom, devise, evenement_id, acompte, solde FROM prestataires WHERE id = ?");
            $stmt->execute([$id]);
            $prest = $stmt->fetch(PDO::FETCH_NUM);
            
            if ($prest && $montant > 0) {
                $nom_prest = $prest[1];
                $devise = $prest[2] ?? 'EUR';
                $evenement_id = $prest[3];
                $ancien_acompte = floatval($prest[4]);
                $ancien_solde = floatval($prest[5]);
                
                // Créer la dépense
                creerDepenseDepuisPrestataire($pdo, $nom_prest, $montant, $devise, $evenement_id, $type_paiement);
                
                // Mettre à jour le prestataire
                if ($type_paiement === 'acompte') {
                    $nouvel_acompte = $ancien_acompte + $montant;
                    $nouveau_solde = $ancien_solde - $montant;
                } else { // solde
                    $nouvel_acompte = $ancien_acompte + $montant;
                    $nouveau_solde = $ancien_solde - $montant;
                }
                
                $stmt = $pdo->prepare("UPDATE prestataires SET acompte = ?, solde = ? WHERE id = ?");
                $stmt->execute([$nouvel_acompte, $nouveau_solde, $id]);
                
                $message = "Paiement enregistré et dépense créée";
            }
            
        } elseif ($_POST['action'] === 'supprimer') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM prestataires WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Prestataire supprimé";
        }
    }
}

// Récupérer le prestataire à modifier si demandé
$prestataire_a_modifier = null;
if (isset($_GET['modifier'])) {
    $id_modifier = intval($_GET['modifier']);
    $stmt = $pdo->prepare("
        SELECT p.id, p.nom, p.type, p.pays, p.montant_total, p.acompte, p.solde, p.date_limite, p.contact, p.devise, p.evenement_id
        FROM prestataires p
        WHERE p.id = ?
    ");
    $stmt->execute([$id_modifier]);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row) {
        $prestataire_a_modifier = [
            'id' => $row[0],
            'nom' => $row[1],
            'type' => $row[2],
            'pays' => $row[3],
            'montant_total' => $row[4],
            'acompte' => $row[5],
            'solde' => $row[6],
            'date_limite' => $row[7],
            'contact' => $row[8],
            'devise' => $row[9] ?? ($row[3] === 'Sénégal' ? 'XOF' : 'EUR'),
            'evenement_id' => $row[10]
        ];
    }
}

// Récupérer tous les prestataires avec colonnes explicites
$stmt = $pdo->query("
    SELECT p.id, p.nom, p.type, p.pays, p.montant_total, p.acompte, p.solde, p.date_limite, p.contact, p.devise, e.nom as evenement
    FROM prestataires p
    JOIN evenements e ON p.evenement_id = e.id
    ORDER BY p.date_limite ASC, p.nom
");
$rows = $stmt->fetchAll(PDO::FETCH_NUM);
$prestataires = [];
foreach ($rows as $row) {
    $prestataires[] = [
        'id' => $row[0],
        'nom' => $row[1],
        'type' => $row[2],
        'pays' => $row[3],
        'montant_total' => $row[4],
        'acompte' => $row[5],
        'solde' => $row[6],
        'date_limite' => $row[7],
        'contact' => $row[8],
        'devise' => $row[9] ?? ($row[3] === 'Sénégal' ? 'XOF' : 'EUR'),
        'evenement' => $row[10]
    ];
}

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

$currentPage = 'prestataires';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestataires - Solange & Mathieu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    <script>
        function updateDevise() {
            const pays = document.getElementById('pays').value;
            const deviseLabel = document.getElementById('devise-label');
            const deviseLabelAcompte = document.getElementById('devise-label-acompte');
            const deviseInput = document.getElementById('devise');
            const montantInput = document.getElementById('montant_total');
            const acompteInput = document.getElementById('acompte');
            
            if (pays === 'Sénégal') {
                deviseLabel.textContent = 'XOF';
                deviseLabelAcompte.textContent = 'XOF';
                deviseInput.value = 'XOF';
                montantInput.step = '1';
                acompteInput.step = '1';
            } else if (pays === 'France') {
                deviseLabel.textContent = '€';
                deviseLabelAcompte.textContent = '€';
                deviseInput.value = 'EUR';
                montantInput.step = '0.01';
                acompteInput.step = '0.01';
            } else {
                deviseLabel.textContent = '€';
                deviseLabelAcompte.textContent = '€';
                deviseInput.value = 'EUR';
                montantInput.step = '0.01';
                acompteInput.step = '0.01';
            }
        }
        
        function showPaiementModal(id, nom, solde, devise) {
            const montantMax = prompt(`Enregistrer un paiement pour "${nom}"\n\nSolde restant: ${solde.toLocaleString('fr-FR')} ${devise === 'XOF' ? 'XOF' : '€'}\n\nMontant à payer:`, solde);
            if (montantMax && !isNaN(montantMax) && parseFloat(montantMax) > 0) {
                const montant = parseFloat(montantMax);
                if (montant > solde) {
                    alert('Le montant ne peut pas dépasser le solde restant');
                    return;
                }
                if (confirm(`Créer une dépense de ${montant.toLocaleString('fr-FR')} ${devise === 'XOF' ? 'XOF' : '€'} pour "${nom}" ?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="paiement">
                        <input type="hidden" name="id" value="${id}">
                        <input type="hidden" name="type_paiement" value="${montant >= solde ? 'solde' : 'acompte'}">
                        <input type="hidden" name="montant" value="${montant}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="header-title">Prestataires</h1>
        </header>
        
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout/modification -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <h2 class="card-title"><?= $prestataire_a_modifier ? 'Modifier le prestataire' : 'Ajouter un prestataire' ?></h2>
                </div>
                <?php if ($prestataire_a_modifier): ?>
                    <a href="prestataires.php" style="display: inline-block; margin-bottom: 16px; color: var(--primary); text-decoration: none; font-size: 14px;">← Annuler la modification</a>
                <?php endif; ?>
                <form method="POST" class="grid grid-2">
                    <input type="hidden" name="action" value="<?= $prestataire_a_modifier ? 'modifier' : 'ajouter' ?>">
                    <?php if ($prestataire_a_modifier): ?>
                        <input type="hidden" name="id" value="<?= $prestataire_a_modifier['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nom" required class="form-input" value="<?= $prestataire_a_modifier ? htmlspecialchars($prestataire_a_modifier['nom']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="type" required class="form-input">
                            <option value="traiteur" <?= $prestataire_a_modifier && $prestataire_a_modifier['type'] === 'traiteur' ? 'selected' : '' ?>>Traiteur</option>
                            <option value="salle" <?= $prestataire_a_modifier && $prestataire_a_modifier['type'] === 'salle' ? 'selected' : '' ?>>Salle</option>
                            <option value="photographe" <?= $prestataire_a_modifier && $prestataire_a_modifier['type'] === 'photographe' ? 'selected' : '' ?>>Photographe</option>
                            <option value="décorateur" <?= $prestataire_a_modifier && $prestataire_a_modifier['type'] === 'décorateur' ? 'selected' : '' ?>>Décorateur</option>
                            <option value="musique" <?= $prestataire_a_modifier && $prestataire_a_modifier['type'] === 'musique' ? 'selected' : '' ?>>Musique / DJ</option>
                            <option value="transport" <?= $prestataire_a_modifier && $prestataire_a_modifier['type'] === 'transport' ? 'selected' : '' ?>>Transport</option>
                            <option value="autre" <?= $prestataire_a_modifier && $prestataire_a_modifier['type'] === 'autre' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Pays</label>
                        <select name="pays" id="pays" required class="form-input" onchange="updateDevise()">
                            <option value="">Sélectionner un pays</option>
                            <option value="Sénégal" <?= $prestataire_a_modifier && $prestataire_a_modifier['pays'] === 'Sénégal' ? 'selected' : '' ?>>Sénégal</option>
                            <option value="France" <?= $prestataire_a_modifier && $prestataire_a_modifier['pays'] === 'France' ? 'selected' : '' ?>>France</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Événement</label>
                        <select name="evenement_id" required class="form-input">
                            <?php foreach ($evenements as $evt): ?>
                                <option value="<?= $evt['id'] ?>" <?= $prestataire_a_modifier && $prestataire_a_modifier['evenement_id'] == $evt['id'] ? 'selected' : '' ?>><?= htmlspecialchars($evt['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Montant total (<span id="devise-label"><?= $prestataire_a_modifier && $prestataire_a_modifier['devise'] === 'XOF' ? 'XOF' : '€' ?></span>)</label>
                        <input type="number" name="montant_total" id="montant_total" step="<?= $prestataire_a_modifier && $prestataire_a_modifier['devise'] === 'XOF' ? '1' : '0.01' ?>" required class="form-input" value="<?= $prestataire_a_modifier ? $prestataire_a_modifier['montant_total'] : '' ?>">
                        <input type="hidden" name="devise" id="devise" value="<?= $prestataire_a_modifier ? $prestataire_a_modifier['devise'] : 'EUR' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Acompte (<span id="devise-label-acompte"><?= $prestataire_a_modifier && $prestataire_a_modifier['devise'] === 'XOF' ? 'XOF' : '€' ?></span>)</label>
                        <input type="number" name="acompte" id="acompte" step="<?= $prestataire_a_modifier && $prestataire_a_modifier['devise'] === 'XOF' ? '1' : '0.01' ?>" value="<?= $prestataire_a_modifier ? $prestataire_a_modifier['acompte'] : '0' ?>" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date limite de paiement</label>
                        <input type="date" name="date_limite" class="form-input" value="<?= $prestataire_a_modifier && $prestataire_a_modifier['date_limite'] ? $prestataire_a_modifier['date_limite'] : '' ?>">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Contact</label>
                        <textarea name="contact" rows="3" class="form-input"><?= $prestataire_a_modifier ? htmlspecialchars($prestataire_a_modifier['contact']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-primary"><?= $prestataire_a_modifier ? 'Modifier le prestataire' : 'Ajouter le prestataire' ?></button>
                    </div>
                </form>
            </div>

            <!-- Liste des prestataires -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Liste des prestataires</h2>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Événement</th>
                                <th>Montant total</th>
                                <th>Acompte</th>
                                <th>Solde</th>
                                <th>Date limite</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prestataires)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--gray-400); padding: 40px;">
                                        Aucun prestataire enregistré
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($prestataires as $prest): ?>
                                    <?php
                                    $isUrgent = $prest['solde'] > 0 && $prest['date_limite'] && strtotime($prest['date_limite']) <= strtotime('+7 days');
                                    ?>
                                    <tr style="<?= $isUrgent ? 'background: rgba(245, 158, 11, 0.1);' : '' ?>">
                                        <td style="font-weight: 500;"><?= htmlspecialchars($prest['nom']) ?></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?= htmlspecialchars($prest['type']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($prest['evenement']) ?></td>
                                        <td><strong><?= formaterMontant($prest['montant_total'], $prest['devise']) ?></strong></td>
                                        <td><?= formaterMontant($prest['acompte'], $prest['devise']) ?></td>
                                        <td style="color: <?= $prest['solde'] > 0 ? 'var(--danger)' : 'var(--success)' ?>; font-weight: 600;">
                                            <?= formaterMontant($prest['solde'], $prest['devise']) ?>
                                        </td>
                                        <td>
                                            <?= $prest['date_limite'] ? date('d/m/Y', strtotime($prest['date_limite'])) : '-' ?>
                                            <?php if ($isUrgent): ?>
                                                <span style="color: var(--danger); margin-left: 8px;">⚠️</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                                <a href="prestataires.php?modifier=<?= $prest['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Modifier</a>
                                                <?php if ($prest['solde'] > 0): ?>
                                                    <button onclick="showPaiementModal(<?= $prest['id'] ?>, '<?= htmlspecialchars($prest['nom']) ?>', <?= $prest['solde'] ?>, '<?= $prest['devise'] ?>')" class="btn" style="padding: 6px 12px; font-size: 12px; background: var(--success); color: white;">Payer</button>
                                                <?php endif; ?>
                                                <form method="POST" onsubmit="return confirm('Supprimer ce prestataire ?')" style="display: inline;">
                                                    <input type="hidden" name="action" value="supprimer">
                                                    <input type="hidden" name="id" value="<?= $prest['id'] ?>">
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
