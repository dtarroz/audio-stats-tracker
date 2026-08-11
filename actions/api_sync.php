<?php
/**
 * API pour synchroniser les données depuis l'extérieur
 * Reçoit le contenu HTML via POST et extrait les données
 */

// Capturer toutes les erreurs PHP
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Handler d'erreur personnalisé
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Désactiver le buffering
if (ob_get_level()) {
    ob_end_clean();
}

try {
    // Augmenter les limites PHP
    set_time_limit(60); // 60 secondes max
    ini_set('memory_limit', '256M'); // 256 Mo de mémoire
    
    // Charger la configuration et les classes
    require_once dirname(__DIR__) . '/config/config.php';
    require_once APP_ROOT . '/classes/Logger.php';
    require_once APP_ROOT . '/classes/Database.php';
    require_once APP_ROOT . '/classes/AudioExtractor.php';
    require_once APP_ROOT . '/classes/TrackRepository.php';
    require_once APP_ROOT . '/classes/Track.php';
    
    // Vérifier que la méthode est POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée. Utilisez POST.');
    }
    
    // Récupérer le contenu HTML
    $htmlContent = isset($_POST['html']) ? $_POST['html'] : '';
    
    if (empty($htmlContent)) {
        // Essayer de lire depuis le corps de la requête (raw POST data)
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (isset($data['html'])) {
            $htmlContent = $data['html'];
        }
    }
    
    if (empty($htmlContent)) {
        throw new Exception('Aucun contenu HTML fourni. Envoyez le HTML dans le paramètre "html".');
    }
    
    Logger::info("Synchronisation API démarrée - Taille HTML: " . strlen($htmlContent) . " octets");
    
    // Initialiser les services
    $db = new Database(DATABASE_PATH);
    Logger::info("Database initialisée");
    
    $audioExtractor = new AudioExtractor();
    Logger::info("AudioExtractor initialisé");
    
    $trackRepository = new TrackRepository($db);
    Logger::info("TrackRepository initialisé");
    
    // Extraire les données
    Logger::info("Début de l'extraction...");
    
    try {
        $extractedData = $audioExtractor->extractFromHtml($htmlContent);
    } catch (Exception $e) {
        Logger::error("Erreur pendant l'extraction: " . $e->getMessage());
        throw new Exception("Erreur d'extraction: " . $e->getMessage());
    }
    
    Logger::info("Extraction terminée - Pistes trouvées: " . (is_array($extractedData) ? count($extractedData) : 0));
    
    if (empty($extractedData)) {
        throw new Exception('Aucune donnée extraite du HTML fourni. Vérifiez que le HTML contient des éléments data-test-audio-id.');
    }
    
    Logger::info("Données extraites: " . count($extractedData) . " pistes trouvées");
    
    // Traiter chaque piste
    $tracksAdded = 0;
    $tracksUpdated = 0;
    $errors = array();
    
    foreach ($extractedData as $trackData) {
        try {
            // Vérifier si la piste existe déjà
            $existingTrack = $trackRepository->findByAudioId($trackData['audio_id']);
            
            if ($existingTrack) {
                // Mettre à jour la piste existante
                $trackRepository->update($existingTrack->id, $trackData);
                
                // Ajouter l'historique
                $trackRepository->addHistory($existingTrack->id, $trackData['listen_count']);
                
                $tracksUpdated++;
                Logger::info("Piste mise à jour: {$trackData['title']} (ID: {$trackData['audio_id']})");
            } else {
                // Créer une nouvelle piste
                $newTrackId = $trackRepository->create($trackData);
                
                // Ajouter l'historique initial
                $trackRepository->addHistory($newTrackId, $trackData['listen_count']);
                
                $tracksAdded++;
                Logger::info("Nouvelle piste créée: {$trackData['title']} (ID: {$trackData['audio_id']})");
            }
            
        } catch (Exception $e) {
            $errorMsg = "Erreur pour la piste {$trackData['title']}: " . $e->getMessage();
            Logger::error($errorMsg);
            $errors[] = $errorMsg;
        }
    }
    
    // Préparer le message de réponse
    $message = "Synchronisation terminée avec succès.";
    if ($tracksAdded > 0) {
        $message .= " {$tracksAdded} piste(s) ajoutée(s).";
    }
    if ($tracksUpdated > 0) {
        $message .= " {$tracksUpdated} piste(s) mise(s) à jour.";
    }
    
    Logger::info($message);
    
    // Retourner la réponse
    $response = array(
        'success' => true,
        'message' => $message,
        'stats' => array(
            'tracks_found' => count($extractedData),
            'tracks_added' => $tracksAdded,
            'tracks_updated' => $tracksUpdated,
            'errors' => count($errors)
        ),
        'errors' => $errors
    );
    
    echo json_encode($response);
    
    // Forcer l'envoi de la réponse
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }
    
    exit(0);
    
} catch (Exception $e) {
    $errorDetails = array(
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    );
    
    Logger::error("Erreur lors de la synchronisation API: " . json_encode($errorDetails));
    
    http_response_code(500);
    
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'details' => array(
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        )
    ));
    
    // Forcer l'envoi de la réponse
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }
    
    exit(1);
}
