<?php
/**
 * Vérification de l'installation
 * Ce script vérifie que tous les prérequis sont remplis
 */

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Vérification de l'installation - Audio Stats</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .check-item {
            background: white;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .check-ok {
            border-left: 5px solid #10b981;
        }
        .check-error {
            border-left: 5px solid #ef4444;
        }
        .check-warning {
            border-left: 5px solid #f59e0b;
        }
        .status {
            font-weight: bold;
            padding: 5px 15px;
            border-radius: 20px;
        }
        .status-ok {
            background: #d1fae5;
            color: #065f46;
        }
        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .summary {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🔍 Vérification de l'installation</h1>
        <p>Audio Stats Tracker</p>
    </div>
";

$checks = array();
$errors = 0;
$warnings = 0;

// Vérification de la version PHP
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '5.4.0', '>=');
$checks[] = array(
    'name' => 'Version PHP',
    'detail' => "Version détectée : {$phpVersion}",
    'status' => $phpOk ? 'ok' : 'error'
);
if (!$phpOk) $errors++;

// Avertissement si PHP < 7.0
if ($phpOk && version_compare($phpVersion, '7.0.0', '<')) {
    $checks[] = array(
        'name' => 'Version PHP (Avertissement)',
        'detail' => "PHP {$phpVersion} fonctionne mais PHP 7.4+ fortement recommandé pour la sécurité et les performances",
        'status' => 'warning'
    );
    $warnings++;
}

// Vérification de PDO
$pdoOk = extension_loaded('pdo');
$checks[] = array(
    'name' => 'Extension PDO',
    'detail' => $pdoOk ? 'Extension chargée' : 'Extension non trouvée',
    'status' => $pdoOk ? 'ok' : 'error'
);
if (!$pdoOk) $errors++;

// Vérification de PDO SQLite
$pdoSqliteOk = extension_loaded('pdo_sqlite');
$checks[] = array(
    'name' => 'Extension PDO SQLite',
    'detail' => $pdoSqliteOk ? 'Extension chargée' : 'Extension non trouvée',
    'status' => $pdoSqliteOk ? 'ok' : 'error'
);
if (!$pdoSqliteOk) $errors++;

// Vérification de cURL
$curlOk = extension_loaded('curl');
$checks[] = array(
    'name' => 'Extension cURL',
    'detail' => $curlOk ? 'Extension chargée' : 'Extension non trouvée',
    'status' => $curlOk ? 'ok' : 'error'
);
if (!$curlOk) $errors++;

// Vérification de DOM
$domOk = extension_loaded('dom');
$checks[] = array(
    'name' => 'Extension DOM',
    'detail' => $domOk ? 'Extension chargée' : 'Extension non trouvée',
    'status' => $domOk ? 'ok' : 'error'
);
if (!$domOk) $errors++;

// Vérification de libxml
$libxmlOk = extension_loaded('libxml');
$checks[] = array(
    'name' => 'Extension libxml',
    'detail' => $libxmlOk ? 'Extension chargée' : 'Extension non trouvée',
    'status' => $libxmlOk ? 'ok' : 'error'
);
if (!$libxmlOk) $errors++;

// Vérification du dossier database
$databaseDir = __DIR__ . '/database';
$databaseWritable = is_dir($databaseDir) && is_writable($databaseDir);
$checks[] = array(
    'name' => 'Dossier database/',
    'detail' => $databaseWritable ? 'Accessible en écriture' : 'Non accessible en écriture',
    'status' => $databaseWritable ? 'ok' : 'warning'
);
if (!$databaseWritable) $warnings++;

// Vérification du dossier logs
$logsDir = __DIR__ . '/logs';
$logsWritable = is_dir($logsDir) && is_writable($logsDir);
$checks[] = array(
    'name' => 'Dossier logs/',
    'detail' => $logsWritable ? 'Accessible en écriture' : 'Non accessible en écriture',
    'status' => $logsWritable ? 'ok' : 'warning'
);
if (!$logsWritable) $warnings++;

// Vérification des fichiers requis
$requiredFiles = array(
    'config/config.php',
    'classes/Database.php',
    'classes/Migration.php',
    'classes/AudioExtractor.php',
    'classes/TrackRepository.php',
    'migrations/001_initial.sql',
    'index.php'
);

foreach ($requiredFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $checks[] = array(
        'name' => "Fichier {$file}",
        'detail' => $exists ? 'Présent' : 'Manquant',
        'status' => $exists ? 'ok' : 'error'
    );
    if (!$exists) $errors++;
}

// Affichage des résultats
foreach ($checks as $check) {
    $statusClass = $check['status'] === 'ok' ? 'status-ok' : ($check['status'] === 'error' ? 'status-error' : 'status-warning');
    $checkClass = $check['status'] === 'ok' ? 'check-ok' : ($check['status'] === 'error' ? 'check-error' : 'check-warning');
    $statusText = $check['status'] === 'ok' ? '✓ OK' : ($check['status'] === 'error' ? '✗ ERREUR' : '⚠ ATTENTION');
    
    echo "<div class='check-item {$checkClass}'>";
    echo "<div>";
    echo "<strong>{$check['name']}</strong><br>";
    echo "<small>{$check['detail']}</small>";
    echo "</div>";
    echo "<span class='status {$statusClass}'>{$statusText}</span>";
    echo "</div>";
}

// Résumé
echo "<div class='summary'>";
echo "<h2>Résumé</h2>";

if ($errors > 0) {
    echo "<p style='color: #ef4444; font-weight: bold;'>❌ {$errors} erreur(s) critique(s) détectée(s)</p>";
    echo "<p>L'application ne pourra pas fonctionner correctement. Veuillez corriger les erreurs ci-dessus.</p>";
} elseif ($warnings > 0) {
    echo "<p style='color: #f59e0b; font-weight: bold;'>⚠️ {$warnings} avertissement(s)</p>";
    echo "<p>L'application devrait fonctionner mais certaines fonctionnalités peuvent être limitées.</p>";
} else {
    echo "<p style='color: #10b981; font-weight: bold;'>✅ Tous les tests sont passés avec succès !</p>";
    echo "<p>Votre environnement est correctement configuré.</p>";
    echo "<a href='index.php' class='btn'>Accéder à l'application →</a>";
}

echo "</div>";

echo "</body></html>";
