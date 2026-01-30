<?php
if (!isset($currentPage)) $currentPage = '';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="logo-icon">💍</div>
            <div class="logo-text">
                <div class="logo-title">Solange & Mathieu</div>
                <div class="logo-subtitle">Mariage</div>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="depenses.php" class="nav-item <?= $currentPage === 'depenses' ? 'active' : '' ?>">
            <span class="nav-icon">💸</span>
            <span class="nav-label">Dépenses</span>
        </a>
        <a href="prestataires.php" class="nav-item <?= $currentPage === 'prestataires' ? 'active' : '' ?>">
            <span class="nav-icon">👔</span>
            <span class="nav-label">Prestataires</span>
        </a>
        <a href="taches.php" class="nav-item <?= $currentPage === 'taches' ? 'active' : '' ?>">
            <span class="nav-icon">✅</span>
            <span class="nav-label">Tâches</span>
        </a>
        <a href="documents.php" class="nav-item <?= $currentPage === 'documents' ? 'active' : '' ?>">
            <span class="nav-icon">📁</span>
            <span class="nav-label">Documents</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($user['nom'], 0, 1)) ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($user['nom']) ?></div>
                <div class="user-role">Organisateur</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <span>🚪</span>
            <span>Déconnexion</span>
        </a>
    </div>
</aside>
