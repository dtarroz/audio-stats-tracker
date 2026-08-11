<?php
/**
 * Script de test pour AudioExtractor avec le vrai HTML d'audio.com
 */

// Inclure les classes nécessaires
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/AudioExtractor.php';

echo "=== Test AudioExtractor avec example.txt ===\n\n";

// Lire le fichier example.txt
$htmlFile = __DIR__ . '/example.txt';

if (!file_exists($htmlFile)) {
    die("❌ Fichier example.txt non trouvé\n");
}

echo "📁 Lecture de example.txt...\n";
$html = file_get_contents($htmlFile);
echo "✓ Fichier chargé (" . number_format(strlen($html)) . " caractères)\n\n";

// Créer l'extracteur (on passe null pour éviter les requêtes HTTP)
$extractor = new AudioExtractor();

// Utiliser la réflexion pour appeler la méthode privée parseTracksFromHtml
$reflection = new ReflectionClass($extractor);
$method = $reflection->getMethod('parseTracksFromHtml');
$method->setAccessible(true);

echo "🔍 Extraction des musiques...\n\n";
$tracks = $method->invoke($extractor, $html);

// Afficher les résultats
echo "=== RÉSULTATS ===\n\n";
echo "Nombre de musiques extraites : " . count($tracks) . "\n\n";

foreach ($tracks as $i => $track) {
    echo "--- Musique " . ($i + 1) . " ---\n";
    echo "ID        : " . $track['audio_id'] . "\n";
    echo "Titre     : " . $track['title'] . "\n";
    echo "URL       : " . $track['track_url'] . "\n";
    echo "Image     : " . substr($track['image_url'], 0, 60) . "...\n";
    echo "Écoutes   : " . $track['listen_count'] . "\n";
    echo "\n";
}

echo "✓ Test terminé\n";
