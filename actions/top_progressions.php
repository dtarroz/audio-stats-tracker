<?php
/**
 * Action pour récupérer le top des progressions
 */

header('Content-Type: application/json');

try {
    // Charger la configuration et les classes
    require_once dirname(__DIR__) . '/config/config.php';
    require_once APP_ROOT . '/classes/Logger.php';
    require_once APP_ROOT . '/classes/Database.php';
    require_once APP_ROOT . '/classes/TrackRepository.php';
    require_once APP_ROOT . '/classes/Track.php';
    require_once APP_ROOT . '/classes/Statistics.php';
    
    // Initialiser les services
    $db = new Database(DATABASE_PATH);
    $trackRepository = new TrackRepository($db);
    $statistics = new Statistics($db, $trackRepository);
    
    // Récupérer les paramètres
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Récupérer le top des progressions
    $progressions = $statistics->getTopProgressions($days, $limit);
    
    echo json_encode(array(
        'success' => true,
        'progressions' => $progressions
    ));
    
} catch (Exception $e) {
    Logger::error("Erreur lors de la récupération du top des progressions: " . $e->getMessage());
    
    echo json_encode(array(
        'success' => false,
        'error' => 'Une erreur est survenue lors de la récupération des progressions'
    ));
}
