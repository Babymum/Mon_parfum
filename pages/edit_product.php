<?php
require_once __DIR__ . '/../config/db.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ' . BASE_URL . 'pages/catalog.php');
    exit;
}

$db = getDB();
$success = $error = '';

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: ' . BASE_URL . 'pages/dashboard.php');
    exit;
}

// Fetch existing product
$stmt = $db->prepare("SELECT * FROM parfums WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: ' . BASE_URL . 'pages/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $marque = trim($_POST['marque'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = (float)($_POST['prix'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    
    // Handle Image Upload
    $image_url = $product['image']; // Keep old image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $filename = uniqid('perfume_') . '.' . $file_ext;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image_url = 'assets/uploads/' . $filename;
            } else {
                $error = "Erreur lors de l'enregistrement de l'image.";
            }
        } else {
            $error = "Format d'image non valide (JPG, PNG, WEBP acceptés).";
        }
    }

    if (empty($error)) {
        if ($nom && $prix > 0) {
            try {
                $stmt = $db->prepare("UPDATE parfums SET nom = ?, marque = ?, description = ?, prix = ?, stock = ?, image = ? WHERE id = ?");
                $stmt->execute([$nom, $marque, $description, $prix, $stock, $image_url, $product_id]);
                $success = "Le parfum a été modifié avec succès !";
                // Refresh product data
                $product['nom'] = $nom;
                $product['marque'] = $marque;
                $product['description'] = $description;
                $product['prix'] = $prix;
                $product['stock'] = $stock;
                $product['image'] = $image_url;
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        } else {
            $error = "Veuillez remplir tous les champs obligatoires (Nom et Prix).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Produit – La Maison des Parfums</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<!-- NAVBAR (Reuse from catalog or include) -->
<nav class="navbar">
    <a class="navbar-brand" href="catalog.php">
        <span>🌺</span> La Maison des Parfums
    </a>
    <ul class="navbar-nav">
        <li><a href="catalog.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Catalogue
        </a></li>
        <li><a href="dashboard.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a></li>
        <li><a href="add_product.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Ajouter
        </a></li>
    </ul>
    <div class="navbar-user">
        <span class="user-badge">
            <?= htmlspecialchars($_SESSION['user']['username']) ?>
            <?php if (strtolower($_SESSION['user']['username']) !== strtolower($_SESSION['user']['role'] ?? '')): ?>
                (<?= $_SESSION['user']['role'] ?? 'SANS RÔLE' ?>)
            <?php endif; ?>
        </span>
        <a href="../auth/logout.php" class="btn-logout">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="admin-container">
        <div class="admin-header">
            <h1>Modifier <em><?= htmlspecialchars($product['nom']) ?></em></h1>
            <p class="text-muted">Mettez à jour les informations de ce parfum d'exception</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="nom">Nom du Parfum *</label>
                    <input type="text" id="nom" name="nom" class="form-control" required value="<?= htmlspecialchars($product['nom']) ?>">
                </div>
                <div class="form-group">
                    <label for="marque">Marque</label>
                    <input type="text" id="marque" name="marque" class="form-control" value="<?= htmlspecialchars($product['marque']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="prix">Prix (€) *</label>
                    <input type="number" step="0.01" id="prix" name="prix" class="form-control" required value="<?= $product['prix'] ?>">
                </div>
                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" class="form-control" value="<?= $product['stock'] ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Image du Produit</label>
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <img src="../<?= htmlspecialchars($product['image']) ?>" alt="" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;"
                         onerror="this.src='../assets/img.php?s=<?= urlencode($product['nom']) ?>&n=<?= urlencode($product['nom']) ?>'">
                    <span class="text-muted">Image actuelle</span>
                </div>
                <div class="file-input-wrapper">
                    <span class="file-preview-icon">📸</span>
                    <p id="file-name">Cliquez ou glissez une nouvelle image pour remplacer l'actuelle</p>
                    <small class="text-muted">JPG, PNG ou WEBP (Max 2MB)</small>
                    <input type="file" name="image" accept="image/*" onchange="document.getElementById('file-name').innerText = this.files[0].name">
                </div>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn-submit">Enregistrer les Modifications</button>
                <a href="dashboard.php" class="btn-submit" style="background: var(--text-muted); text-align: center; text-decoration: none;">Annuler</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
