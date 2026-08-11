<?php
/**
 * Fichier de diagnostic pour identifier les erreurs
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnostic</title></head><body>";
echo "<h1>🔍 Diagnostic Audio Stats</h1>";

// 1. Version PHP
echo "<h2>1. Version PHP</h2>";
echo "Version actuelle : <strong>" . phpversion() . "</strong><br>";
if (version_compare(phpversion(), '5.4.0', '>=')) {
    echo "✅ PHP 5.4+ OK<br>";
    if (version_compare(phpversion(), '5.6.0', '<')) {
        echo "⚠️ PHP 5.4-5.5 détecté. Fonctionnel mais PHP 7.4+ recommandé pour la sécurité.<br>";
    }
} else {
    echo "❌ PHP version trop ancienne (minimum 5.4 requis)<br>";
}

// 2. Extensions PHP
echo "<h2>2. Extensions PHP</h2>";
$extensions = array('pdo', 'pdo_sqlite', 'curl', 'dom', 'libxml');
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext : installé<br>";
    } else {
        echo "❌ $ext : <strong>MANQUANT</strong><br>";
    }
}

// 3. Permissions fichiers
echo "<h2>3. Permissions & Chemins</h2>";
echo "Répertoire actuel : " . getcwd() . "<br>";
echo "Script actuel : " . __FILE__ . "<br>";

$paths_to_check = array(
    '.' => 'Dossier racine',
    './config' => 'Dossier config',
    './classes' => 'Dossier classes',
    './database' => 'Dossier database',
    './config/config.php' => 'Fichier config.php',
    './index.php' => 'Fichier index.php'
);

foreach ($paths_to_check as $path => $label) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "✅ $label : existe (permissions: $perms)<br>";
    } else {
        echo "❌ $label : <strong>N'EXISTE PAS</strong><br>";
    }
}

// 4. Test inclusion config
echo "<h2>4. Test inclusion config.php</h2>";
try {
    if (file_exists('./config/config.php')) {
        require_once './config/config.php';
        echo "✅ config/config.php chargé avec succès<br>";
        
        // Vérifier les constantes
        $constants = array('AUDIO_PROFILE', 'AUDIO_BASE_URL', 'DATABASE_PATH', 'LOGS_PATH');
        foreach ($constants as $const) {
            if (defined($const)) {
                echo "✅ Constante $const : définie<br>";
            } else {
                echo "❌ Constante $const : <strong>NON DÉFINIE</strong><br>";
            }
        }
    } else {
        echo "❌ Fichier config/config.php introuvable<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur lors du chargement : <strong>" . $e->getMessage() . "</strong><br>";
}

// 5. Test écriture database
echo "<h2>5. Test création database</h2>";
if (is_dir('./database')) {
    if (is_writable('./database')) {
        echo "✅ Dossier database est accessible en écriture<br>";
        
        // Tester création d'un fichier
        $test_file = './database/test_write.txt';
        if (@file_put_contents($test_file, 'test')) {
            echo "✅ Écriture de fichier test réussie<br>";
            @unlink($test_file);
        } else {
            echo "❌ <strong>IMPOSSIBLE d'écrire dans ./database/</strong><br>";
            echo "Permissions actuelles : " . substr(sprintf('%o', fileperms('./database')), -4) . "<br>";
        }
    } else {
        echo "❌ Dossier database n'est <strong>PAS accessible en écriture</strong><br>";
    }
} else {
    echo "❌ Dossier database <strong>N'EXISTE PAS</strong><br>";
}

// 6. Test classes
echo "<h2>6. Test chargement classes</h2>";
$classes_to_test = array(
    './classes/Database.php' => 'Database',
    './classes/Logger.php' => 'Logger',
    './classes/AudioExtractor.php' => 'AudioExtractor'
);

foreach ($classes_to_test as $file => $className) {
    if (file_exists($file)) {
        try {
            require_once $file;
            echo "✅ $className : chargé avec succès<br>";
        } catch (Exception $e) {
            echo "❌ $className : <strong>ERREUR</strong> - " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ $file : fichier introuvable<br>";
    }
}

// 7. Erreurs PHP récentes
echo "<h2>7. Logs d'erreur</h2>";
$error_log = ini_get('error_log');
echo "Fichier de log : " . ($error_log ? $error_log : "non configuré") . "<br>";

// 8. Variables serveur importantes
echo "<h2>8. Configuration serveur</h2>";
echo "Document Root : " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename : " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Server Software : " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

echo "<hr>";
echo "<p><strong>Diagnostic terminé.</strong> Envoyez cette page à votre développeur si vous voyez des ❌</p>";
echo "</body></html>";
