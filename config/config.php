<?php
/**
 * Fichier de configuration principal de l'application
 * Toutes les constantes et paramètres doivent être définis ici
 */

// Informations Audio.com
define('AUDIO_PROFILE', 'dtarroz');
define('AUDIO_BASE_URL', 'https://audio.com/');
define('AUDIO_PROFILE_URL', AUDIO_BASE_URL . AUDIO_PROFILE);

// Configuration cURL
define('CURL_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
define('CURL_TIMEOUT', 30);
define('CURL_CONNECT_TIMEOUT', 10);

// Chemins de l'application
define('APP_ROOT', dirname(__DIR__));
define('DATABASE_PATH', APP_ROOT . '/database/database.sqlite');
define('MIGRATIONS_PATH', APP_ROOT . '/migrations');
define('LOGS_PATH', APP_ROOT . '/logs');
define('LOG_FILE', LOGS_PATH . '/application.log');

// Format de date
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('DATE_FORMAT_DISPLAY', 'd/m/Y à H:i');
define('DATE_FORMAT_DAY', 'Y-m-d');

// Configuration de l'application
define('APP_NAME', 'Audio Stats Tracker');
define('APP_VERSION', '1.3.0');

// Pagination
define('TRACKS_PER_PAGE', 50);

// Gestion des erreurs
define('DISPLAY_ERRORS', false);
define('LOG_ERRORS', true);
define('ENABLE_APPLICATION_LOGS', false);

// Configuration PHP
if (DISPLAY_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Création des dossiers nécessaires s'ils n'existent pas
$directories = array(
    dirname(DATABASE_PATH),
    LOGS_PATH
);

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
