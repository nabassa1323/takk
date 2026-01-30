
USE c1498480c_mariage;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des événements
CREATE TABLE IF NOT EXISTS evenements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    pays VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    devise VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des dépenses
CREATE TABLE IF NOT EXISTS depenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    montant_local DECIMAL(12, 2) NOT NULL,
    devise VARCHAR(10) NOT NULL,
    montant_euro DECIMAL(12, 2) NOT NULL,
    evenement_id INT NOT NULL,
    paye_par INT NOT NULL,
    statut ENUM('prévu', 'payé') DEFAULT 'prévu',
    date DATE NOT NULL,
    justificatif VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    FOREIGN KEY (paye_par) REFERENCES utilisateurs(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table du budget
CREATE TABLE IF NOT EXISTS budget (
    utilisateur_id INT PRIMARY KEY,
    plafond DECIMAL(12, 2) NOT NULL,
    utilise DECIMAL(12, 2) DEFAULT 0,
    restant DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des prestataires
CREATE TABLE IF NOT EXISTS prestataires (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    pays VARCHAR(100) NOT NULL,
    montant_total DECIMAL(12, 2) NOT NULL,
    acompte DECIMAL(12, 2) DEFAULT 0,
    solde DECIMAL(12, 2) NOT NULL,
    date_limite DATE NULL,
    evenement_id INT NOT NULL,
    contact TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des tâches
CREATE TABLE IF NOT EXISTS taches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    description TEXT NOT NULL,
    responsable VARCHAR(50) NOT NULL,
    date_limite DATE NULL,
    statut ENUM('à faire', 'en cours', 'terminé') DEFAULT 'à faire',
    evenement_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des documents
CREATE TABLE IF NOT EXISTS documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    fichier VARCHAR(500) NOT NULL,
    evenement_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des utilisateurs par défaut
-- IMPORTANT : Exécutez sql/init_passwords.php après la création de la base pour initialiser les mots de passe
-- Mot de passe par défaut : "matt123"
INSERT INTO utilisateurs (id, nom, email, mot_de_passe) VALUES
(1, 'Bébé So', 'bebeso@mariage.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy'),
(2, 'Matt', 'matt@mariage.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Insertion des budgets
INSERT INTO budget (utilisateur_id, plafond, utilise, restant) VALUES
(1, 10000.00, 0, 10000.00),
(2, 15000.00, 0, 15000.00)
ON DUPLICATE KEY UPDATE plafond=VALUES(plafond);

-- Insertion des événements
INSERT INTO evenements (id, nom, pays, date, devise) VALUES
(1, 'Mariage religieux (église)', 'Sénégal', '2026-11-28', 'XOF'),
(2, 'Mariage civil (mairie)', 'France', '2026-12-31', 'EUR')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);
