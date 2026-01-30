# 🚀 Guide d'installation rapide

## Étapes d'installation

### 1. Base de données

Exécutez le script SQL pour créer la base de données :

```bash
mysql -u root -p < sql/schema.sql
```

Ou via phpMyAdmin :
- Importez le fichier `sql/schema.sql`

### 2. Configuration

Éditez `config/database.php` avec vos paramètres :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mariage_matt_bebeso');
define('DB_USER', 'root');
define('DB_PASS', 'votre_mot_de_passe');
```

### 3. Initialisation des mots de passe

Exécutez le script PHP pour initialiser les mots de passe :

```bash
php sql/init_passwords.php
```

**OU** manuellement via MySQL :

```sql
UPDATE utilisateurs 
SET mot_de_passe = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy' 
WHERE id IN (1, 2);
```

(Mot de passe : `matt123`)

### 4. Dossier d'uploads

Créez le dossier et donnez-lui les permissions d'écriture :

```bash
mkdir -p assets/uploads
chmod 755 assets/uploads
```

### 5. Serveur web

**Option A : Serveur PHP intégré**
```bash
php -S localhost:8000
```

**Option B : Apache/Nginx**
Configurez votre serveur web pour pointer vers le répertoire du projet.

### 6. Accès à l'application

Ouvrez votre navigateur :
- URL : `http://localhost:8000` (ou votre URL configurée)

### 7. Connexion

Utilisez les identifiants par défaut :
- **Bébé So** : `bebeso@mariage.com` / `matt123`
- **Matt** : `matt@mariage.com` / `matt123`

⚠️ **Changez ces mots de passe après la première connexion !**

## Vérification

Après l'installation, vérifiez que :
- ✅ La base de données est créée avec toutes les tables
- ✅ Les 2 utilisateurs existent
- ✅ Les 2 budgets sont initialisés (Bébé So : 10 000 €, Matt : 15 000 €)
- ✅ Les 2 événements sont créés
- ✅ Le dossier `assets/uploads/` existe et est accessible en écriture
- ✅ Vous pouvez vous connecter avec les identifiants par défaut

## Problèmes courants

### Erreur de connexion à la base de données
- Vérifiez les paramètres dans `config/database.php`
- Vérifiez que MySQL est démarré
- Vérifiez que l'utilisateur MySQL a les droits nécessaires

### Erreur 500
- Vérifiez les logs PHP
- Vérifiez les permissions des fichiers
- Vérifiez que toutes les extensions PHP nécessaires sont activées (PDO, MySQL)

### Impossible d'uploader des documents
- Vérifiez que le dossier `assets/uploads/` existe
- Vérifiez les permissions (755 ou 777 selon votre configuration)
- Vérifiez la configuration `upload_max_filesize` dans php.ini

## Support

En cas de problème, vérifiez :
1. Les logs d'erreur PHP
2. Les logs MySQL
3. La configuration de votre serveur web
