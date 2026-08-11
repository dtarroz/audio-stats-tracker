<?php
/**
 * Action de synchronisation
 * Récupère les musiques depuis Audio.com et met à jour la base de données
 */

// Désactiver le timeout pour cette opération longue
set_time_limit(300); // 5 minutes

// Headers pour le streaming de la progression
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Fonction pour envoyer un message au client
function sendMessage($message) {
    echo "data: " . json_encode(array('message' => $message)) . "\n\n";
    flush();
    ob_flush();
}

try {
    // Charger la configuration et les classes
    require_once dirname(__DIR__) . '/config/config.php';
    require_once APP_ROOT . '/classes/Logger.php';
    require_once APP_ROOT . '/classes/Database.php';
    require_once APP_ROOT . '/classes/TrackRepository.php';
    require_once APP_ROOT . '/classes/Track.php';
    require_once APP_ROOT . '/classes/AudioExtractor.php';
    
    // Initialiser les services
    $db = new Database(DATABASE_PATH);
    $trackRepository = new TrackRepository($db);
    $extractor = new AudioExtractor();
    
    Logger::info("=== Début de la synchronisation ===");
    sendMessage("Début de la synchronisation...");
    
    // Marquer toutes les musiques comme non disponibles
    sendMessage("Marquage des musiques existantes...");
    $trackRepository->markAllAsUnavailable();
    
    // Extraire toutes les musiques
    sendMessage("Scan du profil Audio.com en cours...");
    $result = $extractor->extractAllTracks();
    
    if (!$result['success']) {
        sendMessage("Erreur: " . $result['message']);
        Logger::error("Erreur lors de l'extraction: " . $result['message']);
        exit;
    }
    
    $tracks = $result['tracks'];
    $totalTracks = count($tracks);
    
    sendMessage("{$totalTracks} musique(s) trouvée(s)");
    Logger::info("{$totalTracks} musique(s) trouvée(s)");
    
    // Traiter chaque musique
    $processed = 0;
    $created = 0;
    $updated = 0;
    
    foreach ($tracks as $trackData) {
        $processed++;
        
        // Chercher si la musique existe déjà
        $existingTrack = $trackRepository->findByAudioId($trackData['audio_id']);
        
        if ($existingTrack) {
            // Mise à jour
            $trackRepository->update($existingTrack->id, array(
                'title' => $trackData['title'],
                'track_url' => $trackData['track_url'],
                'image_url' => $trackData['image_url'],
                'available' => 1
            ));
            
            $trackId = $existingTrack->id;
            $updated++;
        } else {
            // Création
            $trackId = $trackRepository->create($trackData);
            $created++;
        }
        
        // Ajouter dans l'historique
        $trackRepository->addHistory($trackId, $trackData['listen_count']);
        
        // Envoyer la progression tous les 10 enregistrements
        if ($processed % 10 == 0 || $processed == $totalTracks) {
            sendMessage("Progression: {$processed}/{$totalTracks} musiques traitées");
        }
    }
    
    // Message final
    $message = "Synchronisation terminée: {$created} créée(s), {$updated} mise(s) à jour";
    sendMessage($message);
    Logger::info($message);
    Logger::info("=== Fin de la synchronisation ===");
    
    // Envoyer un message de fin
    echo "data: " . json_encode(array('done' => true, 'created' => $created, 'updated' => $updated)) . "\n\n";
    flush();
    
} catch (Exception $e) {
    $errorMessage = "Erreur lors de la synchronisation: " . $e->getMessage();
    sendMessage($errorMessage);
    Logger::error($errorMessage);
    
    echo "data: " . json_encode(array('error' => true, 'message' => $errorMessage)) . "\n\n";
    flush();
}
