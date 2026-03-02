<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();

// Remove BASE_URL from image paths in DB if present
$baseUrl = BASE_URL;
$stmt = $db->query("SELECT id, image FROM parfums");
$parfums = $stmt->fetchAll();

$count = 0;
foreach ($parfums as $p) {
    $newPath = $p['image'];
    if (strpos($newPath, $baseUrl) === 0) {
        $newPath = substr($newPath, strlen($baseUrl));
        $update = $db->prepare("UPDATE parfums SET image = ? WHERE id = ?");
        $update->execute([$newPath, $p['id']]);
        $count++;
    }
}

echo "<h2>Base de données mise à jour</h2>";
echo "<p>$count images ont été corrigées pour utiliser des chemins relatifs.</p>";
echo "<p><a href='pages/catalog.php'>Retour au catalogue</a></p>";
