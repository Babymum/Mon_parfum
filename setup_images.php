<?php
/**
 * Script temporaire : télécharge les images des parfums en local.
 * Accéder une fois via : http://localhost/Mini_projet/setup_images.php
 * Supprimer le fichier après utilisation.
 */

$images = [
    'chanel_n5.jpg'       => 'https://images.unsplash.com/photo-1541643600914-78b084683702?w=400&q=80',
    'sauvage.jpg'         => 'https://images.unsplash.com/photo-1590736969955-71cc94901144?w=400&q=80',
    'black_opium.jpg'     => 'https://images.unsplash.com/photo-1587017539504-67cfbddac569?w=400&q=80',
    'acqua_di_gio.jpg'    => 'https://images.unsplash.com/photo-1607201577745-6bdf5e7bda22?w=400&q=80',
    'la_vie_est_belle.jpg'=> 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?w=400&q=80',
    'bleu_de_chanel.jpg'  => 'https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=400&q=80',
    'good_girl.jpg'       => 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=400&q=80',
    'oud_wood.jpg'        => 'https://images.unsplash.com/photo-1518998053901-5348d3961a04?w=400&q=80',
];

$dir = __DIR__ . '/assets/images/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

echo "<h2>Téléchargement des images</h2><ul>";
foreach ($images as $filename => $url) {
    $dest = $dir . $filename;
    if (file_exists($dest)) {
        echo "<li>✅ <b>$filename</b> — déjà présent</li>";
        continue;
    }
    $ctx = stream_context_create(['http' => [
        'timeout' => 10,
        'header'  => "User-Agent: Mozilla/5.0\r\n"
    ]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data !== false) {
        file_put_contents($dest, $data);
        echo "<li>✅ <b>$filename</b> — téléchargé (" . strlen($data) . " bytes)</li>";
    } else {
        echo "<li>❌ <b>$filename</b> — ÉCHEC (URL: $url)</li>";
    }
}
echo "</ul>";

// Maintenant mettre à jour la BDD
require_once __DIR__ . '/config/db.php';
$pdo = getDB();

$updates = [
    'Chanel N°5'       => 'assets/images/chanel_n5.jpg',
    'Sauvage'          => 'assets/images/sauvage.jpg',
    'Black Opium'      => 'assets/images/black_opium.jpg',
    'Acqua di Gio'     => 'assets/images/acqua_di_gio.jpg',
    'La Vie est Belle' => 'assets/images/la_vie_est_belle.jpg',
    'Bleu de Chanel'   => 'assets/images/bleu_de_chanel.jpg',
    'Good Girl'        => 'assets/images/good_girl.jpg',
    'Oud Wood'         => 'assets/images/oud_wood.jpg',
];

$stmt = $pdo->prepare("UPDATE parfums SET image = ? WHERE nom = ?");
echo "<h2>Mise à jour BDD</h2><ul>";
foreach ($updates as $nom => $path) {
    $stmt->execute([$path, $nom]);
    echo "<li>✅ <b>$nom</b> → $path</li>";
}
echo "</ul><p><b>✅ Tout est prêt ! Supprimez ce fichier maintenant.</b></p>";
