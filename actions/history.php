<?php
/**
 * Action pour récupérer l'historique d'une musique
 * Utilisé pour les graphiques
 */

header('Content-Type: application/json');

try {
    // Charger la configuration et les classes
    require_once dirname(__DIR__) . '/config/config.php';
    require_once APP_ROOT . '/classes/Logger.php';
    require_once APP_ROOT . '/classes/Database.php';
    require_once APP_ROOT . '/classes/TrackRepository.php';
    require_once APP_ROOT . '/classes/Track.php';
    
    // Initialiser les services
    $db = new Database(DATABASE_PATH);
    $trackRepository = new TrackRepository($db);
    
    // Récupérer les paramètres
    $trackId = isset($_GET['track_id']) ? (int)$_GET['track_id'] : 0;
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 0;
    
    if ($trackId <= 0) {
        echo json_encode(array(
            'success' => false,
            'error' => 'ID de musique invalide'
        ));
        exit;
    }
    
    // Récupérer l'historique
    $history = $trackRepository->getHistory($trackId, $days);
    
    echo json_encode(array(
        'success' => true,
        'history' => $history
    ));
    
} catch (Exception $e) {
    Logger::error("Erreur lors de la récupération de l'historique: " . $e->getMessage());
    
    echo json_encode(array(
        'success' => false,
        'error' => 'Une erreur est survenue lors de la récupération de l\'historique'
    ));
}
