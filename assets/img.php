<?php
/**
 * Générateur d'images dynamique pour les parfums.
 * Usage: assets/img.php?s=chanel5&n=Chanel+N5
 */

$seed  = preg_replace('/[^a-z0-9]/i', '', $_GET['s'] ?? 'default');
$label = substr(strip_tags($_GET['n'] ?? $seed), 0, 24);
$w = 400; $h = 400;

// Palette de dégradés indexée par seed
$palettes = [
    'chanel5'     => [[20,10,40],    [120,60,160]],
    'sauvage'     => [[5,20,60],     [30,120,200]],
    'blackopium'  => [[20,5,10],     [100,20,60]],
    'acqua'       => [[5,40,60],     [20,160,200]],
    'lavie'       => [[50,10,40],    [200,80,160]],
    'bleuchanel'  => [[5,10,50],     [20,60,180]],
    'goodgirl'    => [[40,5,20],     [180,20,100]],
    'oudwood'     => [[30,15,5],     [130,70,20]],
];

// Fallback : couleur dérivée du seed
$default = [abs(crc32($seed)) % 80 + 10, abs(crc32($seed.'g')) % 80 + 10, abs(crc32($seed.'b')) % 80 + 10];
$palette = $palettes[$seed] ?? [$default, [min(255,$default[0]+100), min(255,$default[1]+100), min(255,$default[2]+100)]];

[$r1,$g1,$b1] = $palette[0];
[$r2,$g2,$b2] = $palette[1];

// Headers cache
header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=86400');

// Création de l'image
$img = imagecreatetruecolor($w, $h);

// Dégradé vertical
for ($y = 0; $y < $h; $y++) {
    $t   = $y / $h;
    $r   = (int)($r1 + ($r2 - $r1) * $t);
    $g   = (int)($g1 + ($g2 - $g1) * $t);
    $b   = (int)($b1 + ($b2 - $b1) * $t);
    $col = imagecolorallocate($img, $r, $g, $b);
    imagefilledrectangle($img, 0, $y, $w, $y, $col);
}

// Cercle décoratif central (flacon abstrait)
$cx = $w / 2; $cy = $h / 2;
$glow = imagecolorallocatealpha($img, 255, 255, 255, 90);
imagefilledellipse($img, $cx, $cy - 20, 180, 220, imagecolorallocatealpha($img, 255,255,255, 110));

// Reflets
for ($i = 3; $i >= 0; $i--) {
    $alpha = 120 - $i * 25;
    $col   = imagecolorallocatealpha($img, 255, 255, 255, $alpha);
    imagefilledellipse($img, $cx - 30, $cy - 60, 60 - $i*8, 80 - $i*10, $col);
}

// Texte du nom
$white = imagecolorallocate($img, 255, 255, 255);
$shadow= imagecolorallocatealpha($img, 0, 0, 0, 60);

// Ombre du texte
imagestring($img, 5, $cx - strlen($label)*4 + 1, $cy + 90 + 1, $label, $shadow);
// Texte principal
imagestring($img, 5, $cx - strlen($label)*4, $cy + 90, $label, $white);

// Petite ligne décorative sous le texte
imagesetthickness($img, 2);
imageline($img, $cx - 40, $cy + 108, $cx + 40, $cy + 108, imagecolorallocatealpha($img, 255,255,255,80));

imagejpeg($img, null, 90);
imagedestroy($img);
