# 💍 Application Web - Mariage Matt & Bébé So

Application web privée pour gérer tous les aspects financiers et organisationnels du mariage.

## 🚀 Installation

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx) ou PHP built-in server

### Étapes d'installation

1. **Cloner ou télécharger le projet**

2. **Créer la base de données**
   ```bash
   mysql -u root -p < sql/schema.sql
   ```

3. **Configurer la base de données**
   Éditez le fichier `config/database.php` avec vos paramètres de connexion :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'mariage_matt_bebeso');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

4. **Initialiser les mots de passe** (optionnel)
   ```bash
   php sql/init_passwords.php
   ```

5. **Créer le dossier d'upload**
   ```bash
   mkdir -p assets/uploads
   chmod 755 assets/uploads
   ```

6. **Démarrer le serveur** (si vous utilisez le serveur PHP intégré)
   ```bash
   php -S localhost:8000
   ```

7. **Accéder à l'application**
   Ouvrez votre navigateur à l'adresse : `http://localhost:8000`

## 🔐 Comptes par défaut

- **Bébé So** : `bebeso@mariage.com` / `matt123`
- **Matt** : `matt@mariage.com` / `matt123`

⚠️ **Important** : Changez ces mots de passe après la première connexion !

## 📋 Fonctionnalités

### Dashboard
- Compte à rebours jusqu'au mariage religieux (28/11/2026)
- Vue d'ensemble des budgets (global et individuel)
- Dépenses par événement et devise
- Alertes (dépenses sans justificatif, tâches en retard, prestataires à payer)

### Gestion des dépenses
- Ajout de dépenses avec répartition automatique selon les plafonds
- Support multi-devises (EUR et XOF)
- Conversion automatique XOF → EUR (taux fixe : 1 € = 655 XOF)
- Logique de priorité : Bébé So d'abord, puis Matt

### Gestion des prestataires
- Suivi des prestataires par événement
- Gestion des acomptes et soldes
- Alertes pour les échéances proches

### Gestion des tâches
- Création de tâches avec responsable
- Suivi du statut (à faire / en cours / terminé)
- Alertes pour les tâches en retard

### Gestion des documents
- Upload de documents (PDF, JPG, PNG)
- Organisation par événement
- Stockage sécurisé dans `assets/uploads/`

## 💰 Logique de budget

### Plafonds
- **Bébé So** : 10 000 €
- **Matt** : 15 000 €
- **Total** : 25 000 €

### Priorité de paiement
1. Toutes les dépenses sont d'abord imputées sur **Bébé So**
2. Quand Bébé So atteint son plafond (10 000 €), les nouvelles dépenses sont automatiquement sur **Matt**
3. Si une dépense dépasse le budget restant de Bébé So, elle est répartie automatiquement entre les deux

### Exemple
- Bébé So a déjà dépensé 9 900 €
- Nouvelle dépense : 500 €
- Répartition automatique :
  - Bébé So : 100 €
  - Matt : 400 €

## 🌍 Multi-devises

- **EUR** : devise principale pour les calculs de plafond
- **XOF (CFA)** : conversion automatique avec taux fixe 1 € = 655 XOF
- Toutes les dépenses sont affichées dans leur devise locale + conversion en EUR

## 📁 Structure du projet

```
.
├── config/
│   ├── database.php      # Configuration de la base de données
│   └── init.php          # Initialisation et fonctions communes
├── includes/
│   └── functions.php     # Fonctions utilitaires
├── assets/
│   └── uploads/           # Dossier pour les documents uploadés
├── sql/
│   ├── schema.sql         # Schéma de la base de données
│   └── init_passwords.php # Script d'initialisation des mots de passe
├── index.php              # Page de connexion
├── dashboard.php           # Tableau de bord principal
├── depenses.php            # Gestion des dépenses
├── prestataires.php        # Gestion des prestataires
├── taches.php              # Gestion des tâches
├── documents.php           # Gestion des documents
└── logout.php              # Déconnexion
```

## 🔒 Sécurité

- Authentification par session PHP
- Mots de passe hashés avec `password_hash()`
- Protection contre les injections SQL (requêtes préparées)
- Validation des types de fichiers uploadés
- Protection CSRF recommandée (à implémenter)

## 📝 Notes

- Le taux de conversion XOF/EUR est fixe (655) et défini dans `config/database.php`
- Les dates des événements peuvent être modifiées dans la base de données
- Les plafonds de budget sont configurables dans `config/database.php`

## 🛠️ Technologies utilisées

- **Backend** : PHP 7.4+
- **Base de données** : MySQL
- **Frontend** : HTML, CSS, JavaScript
- **Framework CSS** : Tailwind CSS (via CDN)

## 📞 Support

Pour toute question ou problème, contactez les administrateurs de l'application.

---

Fait avec ❤️ pour le mariage de Matt & Bébé So
