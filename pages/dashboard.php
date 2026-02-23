<?php
require_once __DIR__ . '/../config/db.php';
requireLogin();
// DEBUG INFO
$debug_role = strtolower($_SESSION['user']['role'] ?? 'n/a');
$debug_name = strtolower($_SESSION['user']['username'] ?? 'n/a');

$db = getDB();

// Stats
$totalParfums = $db->query("SELECT COUNT(*) FROM parfums")->fetchColumn();
$totalStock   = $db->query("SELECT SUM(stock) FROM parfums")->fetchColumn();
$valeurStock  = $db->query("SELECT SUM(prix * stock) FROM parfums")->fetchColumn();
$prixMoyen    = $db->query("SELECT AVG(prix) FROM parfums")->fetchColumn();
$prixMax      = $db->query("SELECT MAX(prix) FROM parfums")->fetchColumn();
$totalUsers   = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Cart stats from session
$cartItems = $_SESSION['cart'] ?? [];
$cartCount = array_sum($cartItems);
$cartValue = 0;
if (!empty($cartItems)) {
    $ids = implode(',', array_map('intval', array_keys($cartItems)));
    foreach ($db->query("SELECT id, prix FROM parfums WHERE id IN ($ids)")->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $cartValue += $p['prix'] * ($cartItems[$p['id']] ?? 0);
    }
}

// All perfumes
$parfums = $db->query("SELECT * FROM parfums ORDER BY prix DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – La Maison des Parfums</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a class="navbar-brand" href="<?= BASE_URL ?>pages/catalog.php">
        <span>🌺</span> La Maison des Parfums
    </a>
    <ul class="navbar-nav">
        <li><a href="<?= BASE_URL ?>pages/catalog.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Catalogue
        </a></li>
        <li><a href="<?= BASE_URL ?>pages/dashboard.php" class="active">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a></li>
        <?php 
        $user_role = strtolower($_SESSION['user']['role'] ?? '');
        $user_name = strtolower($_SESSION['user']['username'] ?? '');
        if ($user_role === 'admin' || $user_name === 'admin'): 
        ?>
        <li><a href="<?= BASE_URL ?>pages/add_product.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Ajouter
        </a></li>
        <?php endif; ?>
    </ul>
    <div class="navbar-user">
        <span class="user-badge"><?= htmlspecialchars($_SESSION['user']['username']) ?> (<?= $_SESSION['user']['role'] ?? 'SANS RÔLE' ?>)</span>
        <a href="<?= BASE_URL ?>auth/logout.php" class="btn-logout">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Déconnexion
        </a>
    </div>
</nav>

<div class="dashboard-wrap">

    <!-- PAGE HEADER -->
    <div class="dash-header">
        <div>
            <h1 class="page-title">Dashboard hhh<em>Admin</em></h1>
            <p class="page-subtitle">Bienvenue, <?= htmlspecialchars($_SESSION['user']['username']) ?> · <?= date('d F Y') ?></p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <?php if (strtolower($_SESSION['user']['role'] ?? '') === 'admin' || strtolower($_SESSION['user']['username'] ?? '') === 'admin'): ?>
            <a href="<?= BASE_URL ?>pages/add_product.php" class="btn-goto-catalog" style="background: linear-gradient(135deg, var(--gold), #a07840); color: var(--dark); border: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter un produit
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>pages/catalog.php" class="btn-goto-catalog">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                Voir le catalogue
            </a>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card stat-gold">
            <div class="stat-icon">🌺</div>
            <div class="stat-body">
                <div class="stat-value"><?= $totalParfums ?></div>
                <div class="stat-label">Parfums en catalogue</div>
            </div>
        </div>
        <div class="stat-card stat-purple">
            <div class="stat-icon">📦</div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format((int)$totalStock, 0, ',', ' ') ?></div>
                <div class="stat-label">Unités en stock</div>
            </div>
        </div>
        <div class="stat-card stat-emerald">
            <div class="stat-icon">💰</div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format((float)$valeurStock, 0, ',', ' ') ?> €</div>
                <div class="stat-label">Valeur du stock</div>
            </div>
        </div>
        <div class="stat-card stat-blue">
            <div class="stat-icon">🛒</div>
            <div class="stat-body">
                <div class="stat-value"><?= $cartCount ?> art.</div>
                <div class="stat-label">Panier actuel · <?= number_format($cartValue, 2, ',', ' ') ?> €</div>
            </div>
        </div>
        <div class="stat-card stat-rose">
            <div class="stat-icon">📊</div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format((float)$prixMoyen, 0, ',', ' ') ?> €</div>
                <div class="stat-label">Prix moyen</div>
            </div>
        </div>
        <div class="stat-card stat-amber">
            <div class="stat-icon">👑</div>
            <div class="stat-body">
                <div class="stat-value"><?= number_format((float)$prixMax, 0, ',', ' ') ?> €</div>
                <div class="stat-label">Parfum le plus cher</div>
            </div>
        </div>
    </div>

    <!-- CHART + TABLE ROW -->
    <div class="dash-row">

        <!-- PRICE DISTRIBUTION (visual bars) -->
        <div class="dash-card">
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                Prix par parfum
            </h2>
            <div class="bar-chart">
                <?php
                $maxPrix = max(array_column($parfums, 'prix'));
                foreach ($parfums as $p):
                    $pct = ($p['prix'] / $maxPrix) * 100;
                ?>
                <div class="bar-row">
                    <span class="bar-label" title="<?= htmlspecialchars($p['nom']) ?>">
                        <?= htmlspecialchars(mb_strimwidth($p['nom'], 0, 16, '…')) ?>
                    </span>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: <?= round($pct) ?>%"
                             data-val="<?= number_format($p['prix'], 0, ',', '') ?> €"></div>
                    </div>
                    <span class="bar-price"><?= number_format($p['prix'], 0, ',', ' ') ?> €</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TOP 5 EXPENSIVE -->
        <div class="dash-card">
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                Top 5 Parfums
            </h2>
            <div class="top-list">
                <?php foreach (array_slice($parfums, 0, 5) as $i => $p): ?>
                <div class="top-item">
                    <span class="top-rank rank-<?= $i+1 ?>"><?= $i+1 ?></span>
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt=""
                         onerror="this.src='https://images.unsplash.com/photo-1541643600914-78b084683702?w=100&q=60'">
                    <div class="top-info">
                        <div class="top-name"><?= htmlspecialchars($p['nom']) ?></div>
                        <div class="top-brand"><?= htmlspecialchars($p['marque']) ?></div>
                    </div>
                    <div class="top-price"><?= number_format($p['prix'], 0, ',', ' ') ?> €</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- FULL PERFUME TABLE -->
    <div class="dash-card">
        <div class="table-header-row">
            <h2 class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
                Inventaire complet
            </h2>
            <span class="table-count"><?= count($parfums) ?> produits</span>
        </div>
        <div class="table-wrap">
            <table class="parfums-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Parfum</th>
                        <th>Marque</th>
                        <th>Prix unitaire</th>
                        <th>Stock</th>
                        <th>Valeur stock</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parfums as $i => $p):
                        $valeur = $p['prix'] * $p['stock'];
                        $inCart = $_SESSION['cart'][$p['id']] ?? 0;
                    ?>
                    <tr class="<?= $inCart ? 'row-incart' : '' ?>">
                        <td class="td-num"><?= $i + 1 ?></td>
                        <td class="td-parfum">
                            <img src="<?= htmlspecialchars($p['image']) ?>" alt=""
                                 onerror="this.src='https://images.unsplash.com/photo-1541643600914-78b084683702?w=80&q=50'">
                            <span><?= htmlspecialchars($p['nom']) ?></span>
                        </td>
                        <td class="td-brand"><span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span></td>
                        <td class="td-price"><?= number_format($p['prix'], 2, ',', ' ') ?> €</td>
                        <td class="td-stock"><?= $p['stock'] ?></td>
                        <td class="td-valeur"><?= number_format($valeur, 0, ',', ' ') ?> €</td>
                        <td>
                            <?php if ($inCart): ?>
                            <span class="status-badge status-cart">🛒 Panier (<?= $inCart ?>)</span>
                            <?php elseif ($p['stock'] > 20): ?>
                            <span class="status-badge status-ok">✓ Disponible</span>
                            <?php elseif ($p['stock'] > 0): ?>
                            <span class="status-badge status-low">! Stock faible</span>
                            <?php else: ?>
                            <span class="status-badge status-out">✗ Épuisé</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- end .dashboard-wrap -->

<script>
// Animate bars on load
document.querySelectorAll('.bar-fill').forEach(bar => {
    const w = bar.style.width;
    bar.style.width = '0';
    setTimeout(() => { bar.style.width = w; }, 200);
});
</script>

</body>
</html>
