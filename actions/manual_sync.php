<?php
/**
 * Action de synchronisation manuelle
 * Traite le HTML fourni par l'utilisateur au lieu de le récupérer via cURL
 */

// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 0); // Pas d'affichage direct, on capture
ini_set('log_errors', 1);

// Handler d'erreur personnalisé
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Désactiver le timeout pour cette opération
set_time_limit(300); // 5 minutes

// Headers pour le retour JSON
header('Content-Type: application/json');

try {
    // Charger la configuration et les classes
    require_once dirname(__DIR__) . '/config/config.php';
    require_once APP_ROOT . '/classes/Logger.php';
    require_once APP_ROOT . '/classes/Database.php';
    require_once APP_ROOT . '/classes/TrackRepository.php';
    require_once APP_ROOT . '/classes/Track.php';
    require_once APP_ROOT . '/classes/AudioExtractor.php';
    
    // Vérifier que des données HTML ont été envoyées
    if (!isset($_POST['html_content']) || empty($_POST['html_content'])) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Aucun contenu HTML fourni'
        ));
        exit;
    }
    
    $htmlPages = $_POST['html_content'];
    
    // Si c'est une chaîne JSON, la décoder
    if (is_string($htmlPages)) {
        $decoded = json_decode($htmlPages, true);
        if (is_array($decoded)) {
            $htmlPages = $decoded;
        } else {
            // Si ce n'est pas du JSON, c'est une seule page HTML
            $htmlPages = array($htmlPages);
        }
    }
    
    // S'assurer que c'est un tableau
    if (!is_array($htmlPages)) {
        $htmlPages = array($htmlPages);
    }
    
    Logger::info("=== Début de la synchronisation manuelle ===");
    Logger::info("Nombre de pages fournies: " . count($htmlPages));
    
    // Initialiser les services
    $db = new Database(DATABASE_PATH);
    $trackRepository = new TrackRepository($db);
    $extractor = new AudioExtractor();
    
    Logger::info("Services initialisés, marquage des musiques comme non disponibles...");
    
    // Marquer toutes les musiques comme non disponibles
    $trackRepository->markAllAsUnavailable();
    
    Logger::info("Marquage terminé, début du traitement des pages HTML...");
    
    // Traiter toutes les pages HTML fournies
    $allTracks = array();
    
    foreach ($htmlPages as $index => $html) {
        $pageNum = $index + 1;
        Logger::info("Traitement de la page $pageNum...");
        
        // Utiliser la réflexion pour appeler parseTracksFromHtml
        $reflection = new ReflectionClass($extractor);
        $method = $reflection->getMethod('parseTracksFromHtml');
        $method->setAccessible(true);
        
        $tracks = $method->invoke($extractor, $html);
        
        if (!empty($tracks)) {
            $allTracks = array_merge($allTracks, $tracks);
            Logger::info("Page $pageNum: " . count($tracks) . " musique(s) trouvée(s)");
        } else {
            Logger::warning("Page $pageNum: aucune musique trouvée");
        }
    }
    
    $totalTracks = count($allTracks);
    Logger::info("Total: {$totalTracks} musique(s) trouvée(s)");
    
    if ($totalTracks === 0) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Aucune musique trouvée dans le HTML fourni. Vérifiez que vous avez bien copié le code source de la page.'
        ));
        exit;
    }
    
    Logger::info("Début du traitement des musiques en base de données...");
    
    // Traiter chaque musique
    $processed = 0;
    $created = 0;
    $updated = 0;
    
    foreach ($allTracks as $trackData) {
        try {
            $processed++;
            
            Logger::info("Traitement de la musique {$processed}/{$totalTracks}: {$trackData['title']}");
            
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
                
                Logger::info("Musique mise à jour: {$trackData['title']} (ID: {$trackData['audio_id']})");
            } else {
                // Création - passer directement les données
                $trackId = $trackRepository->create($trackData);
                $created++;
                
                Logger::info("Nouvelle musique: {$trackData['title']} (ID: {$trackData['audio_id']})");
            }
            
            // Ajouter l'historique du nombre d'écoutes (même si 0)
            $listenCount = isset($trackData['listen_count']) ? $trackData['listen_count'] : 0;
            $trackRepository->addHistory($trackId, $listenCount);
        } catch (Exception $e) {
            Logger::error("Erreur lors du traitement de la musique {$trackData['title']}: " . $e->getMessage());
            Logger::error("Stack trace: " . $e->getTraceAsString());
            // Continuer avec la suivante
        }
    }
    
    Logger::info("=== Synchronisation terminée ===");
    Logger::info("Créées: $created, Mises à jour: $updated");
    
    echo json_encode(array(
        'success' => true,
        'message' => "Synchronisation réussie !",
        'stats' => array(
            'total' => $totalTracks,
            'created' => $created,
            'updated' => $updated,
            'pages' => count($htmlPages)
        )
    ));
    
} catch (Exception $e) {
    Logger::error("Erreur lors de la synchronisation manuelle: " . $e->getMessage());
    Logger::error("Fichier: " . $e->getFile() . " ligne " . $e->getLine());
    Logger::error("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode(array(
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage() . ' (voir logs pour détails)'
    ));
}
