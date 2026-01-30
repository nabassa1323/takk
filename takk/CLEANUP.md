# 🧹 Nettoyage des fichiers de test

Pour des raisons de sécurité, supprimez ces fichiers de test après vérification :

## Fichiers à supprimer (sécurité)

- `test_connection.php` - Test de connexion
- `test_login_direct.php` - Test de connexion directe
- `test_real_login.php` - Test de connexion réel
- `test_simple_login.php` - Test simple (fonctionne)
- `test_db_direct.php` - Test comparaison DB
- `test_fetch_methods.php` - Test méthodes fetch
- `test_query_methods.php` - Test méthodes requête
- `test_session.php` - Test session
- `deep_debug.php` - Diagnostic approfondi
- `debug_login.php` - Debug connexion
- `check_table_structure.php` - Vérification structure table
- `fix_passwords.php` - Correction mots de passe
- `fix_data_columns.php` - Correction données
- `reset_passwords.php` - Réinitialisation mots de passe

## Fichiers à garder

- `index.php` - Page de connexion ✅
- `dashboard.php` - Tableau de bord ✅
- `depenses.php` - Gestion dépenses ✅
- `prestataires.php` - Gestion prestataires ✅
- `taches.php` - Gestion tâches ✅
- `documents.php` - Gestion documents ✅
- `logout.php` - Déconnexion ✅
- `config/` - Configuration ✅
- `includes/` - Fonctions ✅
- `sql/` - Schéma base de données ✅

## Note importante

Changez les mots de passe par défaut (`matt123`) après la première connexion pour des raisons de sécurité !
