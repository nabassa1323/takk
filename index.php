<?php
// Démarrer la session AVANT tout autre code
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure seulement database.php, pas init.php qui pourrait interférer
require_once __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        try {
            // Connexion exactement comme dans test_simple_login.php qui fonctionne
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Recherche avec FETCH_NUM comme dans test_simple_login.php
            $stmt = $pdo->prepare("SELECT id, nom, email, mot_de_passe FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_NUM);
            
            if ($row && isset($row[3]) && strlen($row[3]) > 50) {
                $user_id = $row[0];
                $user_nom = $row[1];
                $user_email = $row[2];
                $user_hash = $row[3];
                
                // Vérification du mot de passe
                $isValid = password_verify($password, $user_hash);
                
                if ($isValid) {
                    // Créer la session exactement comme dans test_simple_login.php
                    $_SESSION['user_id'] = (int)$user_id;
                    $_SESSION['user_nom'] = $user_nom;
                    
                    // Redirection
                    if (!headers_sent()) {
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><script>window.location.href = "dashboard.php";</script></head><body>Redirection...</body></html>';
                        exit;
                    }
                } else {
                    $error = 'Mot de passe incorrect';
                }
            } else {
                $error = 'Email incorrect ou utilisateur non trouvé';
            }
        } catch (Exception $e) {
            $error = 'Erreur de connexion : ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Vérifier si déjà connecté
if (isset($_SESSION['user_id']) && isset($_SESSION['user_nom'])) {
    if (!headers_sent()) {
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Mariage Solange & Mathieu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fafafa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 48px 32px;
            text-align: center;
            color: white;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 300;
        }
        
        .login-form {
            padding: 40px 32px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.2s;
            background: #fafafa;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .accounts-info {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #f3f4f6;
        }
        
        .accounts-info p {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 12px;
            text-align: center;
        }
        
        .account-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #4b5563;
        }
        
        .account-item strong {
            color: #111827;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Solange & Mathieu</h1>
            <p>Organisation du mariage</p>
        </div>
        
        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="form-input"
                        placeholder="votre@email.com"
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Mot de passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="form-input"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn-primary">
                    Se connecter
                </button>
            </form>
            
            <div class="accounts-info">
                <p>Comptes disponibles</p>
                <div class="account-item">
                    <span>👰</span>
                    <strong>Solange:</strong>
                    <span>bebeso@mariage.com</span>
                </div>
                <div class="account-item">
                    <span>🤵</span>
                    <strong>Mathieu:</strong>
                    <span>matt@mariage.com</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
