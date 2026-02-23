<?php
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db = getDB();

// === CART ACTIONS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['parfum_id'] ?? 0);

    if ($action === 'add' && $id > 0) {
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = 0;
        }
        $_SESSION['cart'][$id]++;
    } elseif ($action === 'remove' && $id > 0) {
        unset($_SESSION['cart'][$id]);
    } elseif ($action === 'dec' && $id > 0) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]--;
            if ($_SESSION['cart'][$id] <= 0) unset($_SESSION['cart'][$id]);
        }
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }
    header('Location: ' . BASE_URL . 'pages/catalog.php');
    exit;
}

// === FETCH PERFUMES ===
$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $db->prepare("SELECT * FROM parfums WHERE nom LIKE ? OR marque LIKE ? ORDER BY nom");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $db->query("SELECT * FROM parfums ORDER BY nom");
}
$parfums = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === CART DATA ===
$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$cartTotal = 0;

if (!empty($cart)) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $cartStmt = $db->query("SELECT * FROM parfums WHERE id IN ($ids)");
    foreach ($cartStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $qty = $cart[$item['id']];
        $item['qty'] = $qty;
        $item['subtotal'] = $qty * $item['prix'];
        $cartTotal += $item['subtotal'];
        $cartItems[] = $item;
    }
}
$cartCount = array_sum($cart);
?>
<!DOCTYPE html>
<!-- DEBUG: FILE=<?= __FILE__ ?> URI=<?= $_SERVER['REQUEST_URI'] ?> ROLE=<?= $_SESSION['user']['role'] ?? 'N/A' ?> -->
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue – La Maison des Parfums</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/catalog.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a class="navbar-brand" href="<?= BASE_URL ?>pages/catalog.php">
        <span>🌺</span> La Maison des Parfums
    </a>
    <ul class="navbar-nav">
        <li><a href="<?= BASE_URL ?>pages/catalog.php" class="active">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Catalogue
        </a></li>
        <li><a href="<?= BASE_URL ?>pages/dashboard.php">
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

<div class="catalog-layout">

    <!-- MAIN CONTENT -->
    <main class="catalog-main">
        <div class="catalog-header">
            <div>
                <h1 class="page-title">Notre <em>Collection</em></h1>
                <p class="page-subtitle"><?= count($parfums) ?> fragrances d'exception</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
            <?php 
            $user_role = strtolower($_SESSION['user']['role'] ?? '');
            $user_name = strtolower($_SESSION['user']['username'] ?? '');
            if ($user_role === 'admin' || $user_name === 'admin'): 
            ?>
            <a href="<?= BASE_URL ?>pages/add_product.php" class="btn-goto-catalog" style="background: linear-gradient(135deg, var(--gold), #a07840) !important; color: var(--dark) !important; border: none !important; opacity: 1 !important; visibility: visible !important; display: flex !important;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter un produit
            </a>
            <?php endif; ?>
                <form method="GET" class="search-form">
                <div class="search-wrapper">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher un parfum…">
                    <?php if($search): ?>
                    <a href="<?= BASE_URL ?>pages/catalog.php" class="search-clear">✕</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (empty($parfums)): ?>
        <div class="empty-state">
            <span>🔍</span>
            <p>Aucun parfum trouvé pour « <?= htmlspecialchars($search) ?> ».</p>
            <a href="<?= BASE_URL ?>pages/catalog.php">Voir tous les parfums</a>
        </div>
        <?php else: ?>
        <div class="perfume-grid">
            <?php foreach ($parfums as $p): ?>
            <?php $inCart = $cart[$p['id']] ?? 0; ?>
            <article class="perfume-card <?= $inCart ? 'in-cart' : '' ?>">
                <div class="card-img-wrap">
                    <img src="<?= htmlspecialchars($p['image']) ?>"
                         alt="<?= htmlspecialchars($p['nom']) ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1541643600914-78b084683702?w=400&q=80'">
                    <?php if ($inCart): ?>
                    <span class="cart-badge"><?= $inCart ?></span>
                    <?php endif; ?>
                    <div class="card-overlay">
                        <p><?= htmlspecialchars($p['description']) ?></p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-brand"><?= htmlspecialchars($p['marque']) ?></div>
                    <h3 class="card-name"><?= htmlspecialchars($p['nom']) ?></h3>
                    <div class="card-footer">
                        <span class="card-price"><?= number_format($p['prix'], 2, ',', ' ') ?> €</span>
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="parfum_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn-add <?= $inCart ? 'added' : '' ?>">
                                <?php if ($inCart): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                Ajouté (<?= $inCart ?>)
                                <?php else: ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                </svg>
                                Ajouter
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <!-- CART SIDEBAR -->
    <aside class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                Panier
            </h2>
            <?php if ($cartCount > 0): ?>
            <span class="cart-count-badge"><?= $cartCount ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($cartItems)): ?>
        <div class="cart-empty">
            <span>🛒</span>
            <p>Votre panier est vide.</p>
            <small>Ajoutez des parfums depuis le catalogue</small>
        </div>
        <?php else: ?>
        <div class="cart-items">
            <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
                <img src="<?= htmlspecialchars($item['image']) ?>"
                     alt="<?= htmlspecialchars($item['nom']) ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1541643600914-78b084683702?w=200&q=60'">
                <div class="cart-item-info">
                    <div class="cart-item-name"><?= htmlspecialchars($item['nom']) ?></div>
                    <div class="cart-item-brand"><?= htmlspecialchars($item['marque']) ?></div>
                    <div class="cart-item-price"><?= number_format($item['subtotal'], 2, ',', ' ') ?> €</div>
                </div>
                <div class="cart-item-qty">
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="dec">
                        <input type="hidden" name="parfum_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="qty-btn">−</button>
                    </form>
                    <span class="qty-val"><?= $item['qty'] ?></span>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="parfum_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="qty-btn">+</button>
                    </form>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="parfum_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="qty-btn remove-btn">✕</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <div class="cart-total-row">
                <span>Total</span>
                <strong><?= number_format($cartTotal, 2, ',', ' ') ?> €</strong>
            </div>
            <button class="btn-checkout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                Commander
            </button>
            <form method="POST">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn-clear-cart">Vider le panier</button>
            </form>
        </div>
        <?php endif; ?>
    </aside>
</div>

</body>
</html>
