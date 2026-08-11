<?php
/**
 * Audio Stats Tracker
 * Point d'entrée principal de l'application
 * 
 * Application de suivi des statistiques Audio.com
 * Compatible PHP 5.4+, sans framework, sans dépendances externes
 */

// Charger la configuration
require_once __DIR__ . '/config/config.php';

// Charger toutes les classes nécessaires
require_once APP_ROOT . '/classes/Logger.php';
require_once APP_ROOT . '/classes/Database.php';
require_once APP_ROOT . '/classes/Migration.php';
require_once APP_ROOT . '/classes/Track.php';
require_once APP_ROOT . '/classes/TrackRepository.php';
require_once APP_ROOT . '/classes/Statistics.php';
require_once APP_ROOT . '/classes/SettingsRepository.php';
require_once APP_ROOT . '/classes/AudioExtractor.php';

// Gestion des erreurs personnalisée
function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $message = "Erreur [{$errno}]: {$errstr} dans {$errfile} ligne {$errline}";
    Logger::error($message);
    
    if (DISPLAY_ERRORS) {
        echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border-radius:5px;'>";
        echo "<strong>Erreur:</strong> " . htmlspecialchars($errstr);
        echo "</div>";
    }
    
    return true;
}

set_error_handler('handleError');

// Gestion des exceptions non capturées
function handleException($exception) {
    $message = "Exception non capturée: " . $exception->getMessage();
    Logger::error($message);
    
    if (DISPLAY_ERRORS) {
        echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border-radius:5px;'>";
        echo "<strong>Exception:</strong> " . htmlspecialchars($exception->getMessage());
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border-radius:5px;'>";
        echo "<strong>Une erreur est survenue.</strong> Veuillez consulter les logs.";
        echo "</div>";
    }
}

set_exception_handler('handleException');

try {
    // Initialiser la base de données
    $db = new Database(DATABASE_PATH);
    Logger::info("Application démarrée");
    
    // Exécuter les migrations
    $migration = new Migration($db);
    $migrationsApplied = $migration->migrate();
    
    if ($migrationsApplied > 0) {
        Logger::info("{$migrationsApplied} migration(s) appliquée(s)");
    }
    
    // Initialiser les repositories et services
    $trackRepository = new TrackRepository($db);
    $settingsRepository = new SettingsRepository($db);
    $statistics = new Statistics($db, $trackRepository);
    
    // Récupérer les statistiques globales
    $stats = $statistics->getGlobalStats();
    
} catch (Exception $e) {
    Logger::error("Erreur critique lors de l'initialisation: " . $e->getMessage());
    die("Erreur lors de l'initialisation de l'application. Veuillez consulter les logs.");
}

// Inclure le header
include APP_ROOT . '/views/header.php';

// Inclure le dashboard
include APP_ROOT . '/views/dashboard.php';

// Inclure la liste des musiques
include APP_ROOT . '/views/track_list.php';

// Inclure le footer
include APP_ROOT . '/views/footer.php';
