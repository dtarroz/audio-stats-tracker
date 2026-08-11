<?php
/**
 * Test de connexion à audio.com
 * Diagnostique les problèmes de connexion cURL
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test Connexion Audio.com</title>";
echo "<style>body{font-family:Arial;max-width:900px;margin:20px auto;padding:20px;}";
echo "pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto;}";
echo ".ok{color:#10b981;} .error{color:#ef4444;} h2{margin-top:30px;}</style></head><body>";

echo "<h1>🔍 Test de connexion à audio.com</h1>";

// Charger la config
require_once __DIR__ . '/config/config.php';

echo "<h2>1. Configuration</h2>";
echo "URL du profil : <strong>" . AUDIO_PROFILE_URL . "</strong><br>";
echo "User-Agent : " . substr(CURL_USER_AGENT, 0, 50) . "...<br>";
echo "Timeout : " . CURL_TIMEOUT . " secondes<br>";
echo "Connect Timeout : " . CURL_CONNECT_TIMEOUT . " secondes<br>";

// Test 1 : cURL disponible
echo "<h2>2. Extension cURL</h2>";
if (function_exists('curl_init')) {
    echo "<span class='ok'>✅ cURL disponible</span><br>";
    $version = curl_version();
    echo "Version cURL : " . $version['version'] . "<br>";
    echo "SSL Version : " . $version['ssl_version'] . "<br>";
} else {
    echo "<span class='error'>❌ cURL NON disponible - impossible de faire des requêtes HTTP</span><br>";
    die("</body></html>");
}

// Test 2 : Connexion simple à audio.com
echo "<h2>3. Test connexion simple à audio.com</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://audio.com/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, CURL_TIMEOUT);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_CONNECT_TIMEOUT);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, CURL_USER_AGENT);

// Headers HTTP réalistes pour éviter le blocage 403
$headers = array(
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
    'Accept-Encoding: gzip, deflate, br',
    'Cache-Control: max-age=0',
    'Upgrade-Insecure-Requests: 1',
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-User: ?1'
);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_ENCODING, '');

echo "Tentative de connexion à https://audio.com/...<br>";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$errno = curl_errno($ch);

if ($errno) {
    echo "<span class='error'>❌ Erreur cURL #$errno : $error</span><br>";
    
    // Diagnostics supplémentaires
    echo "<h3>Diagnostic de l'erreur :</h3>";
    switch ($errno) {
        case 6: // CURLE_COULDNT_RESOLVE_HOST
            echo "❌ <strong>Impossible de résoudre le nom de domaine</strong><br>";
            echo "Possible causes :<br>";
            echo "- Serveur sans accès DNS<br>";
            echo "- Firewall bloquant les requêtes DNS<br>";
            echo "- Problème réseau temporaire<br>";
            break;
        case 7: // CURLE_COULDNT_CONNECT
            echo "❌ <strong>Impossible de se connecter au serveur</strong><br>";
            echo "Possible causes :<br>";
            echo "- Firewall bloquant les connexions sortantes<br>";
            echo "- Port HTTPS (443) bloqué<br>";
            echo "- Serveur audio.com inaccessible<br>";
            break;
        case 28: // CURLE_OPERATION_TIMEDOUT
            echo "⏱️ <strong>Timeout - connexion trop lente</strong><br>";
            echo "Possible causes :<br>";
            echo "- Connexion internet lente<br>";
            echo "- Serveur audio.com surchargé<br>";
            break;
        case 35: // CURLE_SSL_CONNECT_ERROR
            echo "🔒 <strong>Erreur SSL/TLS</strong><br>";
            echo "Possible causes :<br>";
            echo "- Certificats SSL obsolètes sur le serveur<br>";
            echo "- Version OpenSSL trop ancienne<br>";
            break;
        case 60: // CURLE_SSL_CACERT
            echo "🔒 <strong>Certificat SSL non vérifié</strong><br>";
            echo "Possible causes :<br>";
            echo "- Bundle CA manquant ou obsolète<br>";
            break;
        default:
            echo "Code erreur : $errno<br>";
    }
} else {
    echo "<span class='ok'>✅ Connexion réussie !</span><br>";
    echo "Code HTTP : <strong>$httpCode</strong><br>";
    echo "Taille de la réponse : " . strlen($response) . " octets<br>";
    
    // Diagnostic spécifique HTTP 403
    if ($httpCode == 403) {
        echo "<div style='background:#fef3c7;padding:15px;border-left:5px solid #f59e0b;margin:10px 0;'>";
        echo "<strong>⚠️ HTTP 403 Forbidden détecté</strong><br>";
        echo "Le serveur refuse l'accès. Cela signifie généralement que :<br>";
        echo "• Audio.com détecte que la requête vient d'un bot/script<br>";
        echo "• Protection anti-scraping active (Cloudflare, etc.)<br>";
        echo "• Headers HTTP insuffisants pour simuler un navigateur<br>";
        echo "</div>";
    }
}

curl_close($ch);

// Test 3 : Connexion au profil spécifique
if (!$errno) {
    echo "<h2>4. Test connexion au profil dtarroz</h2>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, AUDIO_PROFILE_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, CURL_TIMEOUT);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_CONNECT_TIMEOUT);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, CURL_USER_AGENT);
    
    // Headers HTTP réalistes
    $headers = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
        'Accept-Encoding: gzip, deflate, br',
        'Cache-Control: max-age=0',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1'
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    
    echo "Tentative de connexion à " . AUDIO_PROFILE_URL . "...<br>";
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    
    if ($errno) {
        echo "<span class='error'>❌ Erreur cURL #$errno : $error</span><br>";
    } else {
        echo "<span class='ok'>✅ Connexion au profil réussie !</span><br>";
        echo "Code HTTP : <strong>$httpCode</strong><br>";
        echo "Taille de la réponse : " . strlen($response) . " octets<br>";
        
        // Vérifier si le contenu contient bien des données de profil
        if (strpos($response, 'dtarroz') !== false) {
            echo "<span class='ok'>✅ Contenu du profil détecté</span><br>";
        } else {
            echo "<span class='error'>⚠️ Le profil ne semble pas accessible</span><br>";
        }
        
        // Chercher des musiques dans la réponse
        if (strpos($response, 'data-test-audio-id') !== false) {
            preg_match_all('/data-test-audio-id="audio-card-(\d+)"/', $response, $matches);
            $count = count($matches[1]);
            echo "<span class='ok'>✅ $count musique(s) détectée(s) dans le HTML</span><br>";
        } else {
            echo "<span class='error'>⚠️ Aucune musique détectée dans le HTML</span><br>";
        }
    }
    
    curl_close($ch);
}

// Test 4 : Test avec AudioExtractor
echo "<h2>5. Test avec AudioExtractor</h2>";

try {
    require_once __DIR__ . '/classes/Logger.php';
    require_once __DIR__ . '/classes/AudioExtractor.php';
    
    echo "Création de l'extracteur...<br>";
    $extractor = new AudioExtractor();
    
    echo "Test d'extraction d'une page...<br>";
    
    // Utiliser la réflexion pour appeler la méthode privée
    $reflection = new ReflectionClass($extractor);
    $method = $reflection->getMethod('fetchPage');
    $method->setAccessible(true);
    
    $html = $method->invoke($extractor, AUDIO_PROFILE_URL);
    
    if ($html && strlen($html) > 1000) {
        echo "<span class='ok'>✅ Extraction réussie avec AudioExtractor</span><br>";
        echo "Taille HTML : " . number_format(strlen($html)) . " caractères<br>";
    } else {
        echo "<span class='error'>❌ Extraction échouée</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</span><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Recommandations
echo "<h2>6. Recommandations</h2>";

if ($errno) {
    echo "<div style='background:#fef3c7;padding:15px;border-left:5px solid #f59e0b;margin:20px 0;'>";
    echo "<h3>🔧 Solutions possibles :</h3>";
    echo "<ol>";
    echo "<li><strong>Vérifier avec votre hébergeur :</strong><br>";
    echo "Contactez le support et demandez si les connexions sortantes HTTPS sont autorisées<br>";
    echo "Demandez si le port 443 (HTTPS) est ouvert</li>";
    echo "<li><strong>Vérifier le firewall :</strong><br>";
    echo "Certains hébergements bloquent les connexions sortantes par sécurité</li>";
    echo "<li><strong>Tester depuis votre ordinateur :</strong><br>";
    echo "Si ça fonctionne en local mais pas sur l'hébergement, c'est un problème de configuration serveur</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background:#d1fae5;padding:15px;border-left:5px solid #10b981;margin:20px 0;'>";
    echo "<h3>✅ Connexion fonctionnelle !</h3>";
    echo "<p>Les tests de connexion sont réussis. L'application devrait fonctionner correctement.</p>";
    echo "<p>Si vous avez toujours une erreur, consultez les logs dans <code>/logs/application.log</code></p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Retour à l'application</a></p>";
echo "</body></html>";
