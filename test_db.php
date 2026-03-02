<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();
$stmt = $db->query("SELECT id, nom, image FROM parfums LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
