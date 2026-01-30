<?php
// Header commun pour toutes les pages
if (!isset($pageTitle)) $pageTitle = 'Mariage Matt & Bébé So';
if (!isset($pageIcon)) $pageIcon = '💍';
if (!isset($pageSubtitle)) $pageSubtitle = '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 25%, #fbcfe8 50%, #f9a8d4 100%);
            min-height: 100vh;
        }
        
        .font-elegant {
            font-family: 'Playfair Display', serif;
        }
        
        .nav-elegant {
            background: linear-gradient(135deg, #ec4899 0%, #f472b6 50%, #fb7185 100%);
            box-shadow: 0 4px 20px rgba(236, 72, 153, 0.3);
        }
        
        .card-elegant {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.2);
            transition: all 0.3s ease;
        }
        
        .card-elegant:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .btn-rose {
            background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%);
            transition: all 0.3s ease;
            color: white;
            font-weight: 600;
        }
        
        .btn-rose:hover {
            background: linear-gradient(135deg, #db2777 0%, #ec4899 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(236, 72, 153, 0.3);
        }
        
        .btn-gold {
            background: linear-gradient(135deg, #d4af37 0%, #f4e4bc 100%);
            transition: all 0.3s ease;
            color: white;
            font-weight: 600;
        }
        
        .btn-gold:hover {
            background: linear-gradient(135deg, #c9a227 0%, #e8d4a8 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.3);
        }
        
        .input-elegant {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }
        
        .input-elegant:focus {
            border-color: #ec4899;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
            outline: none;
        }
        
        .table-elegant tbody tr {
            transition: all 0.2s ease;
        }
        
        .table-elegant tbody tr:hover {
            background: #fdf2f8;
            transform: scale(1.01);
        }
    </style>
</head>
<body>
    <!-- Navigation élégante -->
    <nav class="nav-elegant text-white shadow-xl">
        <div class="container mx-auto px-6 py-5">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <span class="text-3xl"><?= $pageIcon ?></span>
                    <div>
                        <h1 class="font-elegant text-2xl font-bold"><?= htmlspecialchars($pageTitle) ?></h1>
                        <?php if ($pageSubtitle): ?>
                            <p class="text-xs text-pink-100"><?= htmlspecialchars($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="dashboard.php" class="bg-white/20 hover:bg-white/30 text-white font-semibold px-6 py-2 rounded-full transition">
                    ← Retour
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
