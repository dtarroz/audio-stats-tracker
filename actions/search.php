<?php
/**
 * Action de recherche et tri
 * Retourne les musiques filtrées et triées
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
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'default';
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
    
    // Récupérer les musiques
    $tracks = $trackRepository->findAll(array(
        'search' => $search,
        'orderBy' => $orderBy
    ));
    
    // Enrichir avec les progressions si nécessaire
    if ($orderBy === 'progression' || $days > 0) {
        foreach ($tracks as &$track) {
            $progression = $statistics->calculateProgression($track->id, $days);
            $track->progression = $progression['progression'];
            $track->firstValue = $progression['first_value'];
        }
        unset($track); // Important: libérer la référence pour éviter les problèmes
        
        // Trier par progression si demandé
        if ($orderBy === 'progression') {
            usort($tracks, function($a, $b) {
                if ($a->progression === null) return 1;
                if ($b->progression === null) return -1;
                return $b->progression - $a->progression;
            });
        }
    }
    
    // Convertir en tableau pour JSON
    $result = array();
    foreach ($tracks as $track) {
        $trackArray = array(
            'id' => $track->id,
            'audio_id' => $track->audioId,
            'title' => $track->title,
            'track_url' => $track->trackUrl,
            'image_url' => $track->imageUrl,
            'available' => $track->available,
            'current_listen_count' => $track->currentListenCount,
            'created_at' => $track->createdAt,
            'updated_at' => $track->updatedAt
        );
        
        if (isset($track->progression)) {
            $trackArray['progression'] = $track->progression;
        }
        
        $result[] = $trackArray;
    }
    
    echo json_encode(array(
        'success' => true,
        'tracks' => $result,
        'count' => count($result)
    ));
    
} catch (Exception $e) {
    Logger::error("Erreur lors de la recherche: " . $e->getMessage());
    
    echo json_encode(array(
        'success' => false,
        'error' => 'Une erreur est survenue lors de la recherche'
    ));
}
