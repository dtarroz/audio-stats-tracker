<?php
/**
 * Classe AudioExtractor
 * Extrait les informations des musiques depuis Audio.com
 */

class AudioExtractor {
    
    /**
     * @var string URL du profil Audio.com
     */
    private $profileUrl;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->profileUrl = AUDIO_PROFILE_URL;
    }
    
    /**
     * Extrait les données depuis un HTML fourni
     * Utile pour l'API où le HTML est envoyé directement
     * @param string $html Contenu HTML de la page
     * @return array Tableau des musiques trouvées
     */
    public function extractFromHtml($html) {
        Logger::info("Extraction depuis HTML fourni - Taille: " . strlen($html) . " octets");
        
        try {
            $tracks = $this->parseTracksFromHtml($html);
            Logger::info("Extraction terminée - " . count($tracks) . " pistes trouvées");
            return $tracks;
        } catch (Exception $e) {
            Logger::error("Erreur lors de l'extraction: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Récupère le contenu HTML d'une page via cURL
     * @param string $url URL à récupérer
     * @return string|false Contenu HTML ou false en cas d'erreur
     */
    private function fetchPage($url) {
        try {
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, CURL_USER_AGENT);
            curl_setopt($ch, CURLOPT_TIMEOUT, CURL_TIMEOUT);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_CONNECT_TIMEOUT);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            // Fichier pour stocker les cookies
$cookieFile = sys_get_temp_dir() . '/audio_cookies.txt';
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            
            // Headers HTTP pour simuler un navigateur réel et éviter le blocage 403
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
            curl_setopt($ch, CURLOPT_ENCODING, ''); // Support gzip/deflate
            
            // Ajouter un referer si ce n'est pas la première page
            if (strpos($url, '?') !== false) {
                curl_setopt($ch, CURLOPT_REFERER, AUDIO_PROFILE_URL);
            }
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                Logger::error("Erreur cURL: {$error}");
                return false;
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                Logger::error("Erreur HTTP {$httpCode} pour l'URL: {$url}");
                return false;
            }
            
            return $html;
            
        } catch (Exception $e) {
            Logger::error("Erreur lors de la récupération de la page: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Parse le HTML et extrait les informations des musiques
     * Structure Audio.com :
     * - HTML visible : data-test-audio-id, titre, slug, image
     * - JSON échappé : playsCount dans une ligne contenant \"playsCount\":
     * @param string $html Contenu HTML
     * @return array Tableau des musiques trouvées
     */
    private function parseTracksFromHtml($html) {
        $tracks = array();
        
        Logger::info("parseTracksFromHtml - Début");
        
        // Étape 1 : Extraire les playsCount depuis le JSON échappé
        Logger::info("parseTracksFromHtml - Étape 1: Extraction playsCount");
        $playsCounts = $this->extractPlaysCountFromJson($html);
        Logger::info("parseTracksFromHtml - playsCount extraits: " . count($playsCounts));
        
        // Étape 2 : Parser le HTML visible pour extraire les musiques
        Logger::info("parseTracksFromHtml - Étape 2: Création DOMDocument");
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        
        Logger::info("parseTracksFromHtml - Chargement HTML dans DOM (peut être long)...");
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        Logger::info("parseTracksFromHtml - HTML chargé dans DOM");
        
        libxml_clear_errors();
        
        Logger::info("parseTracksFromHtml - Création XPath");
        $xpath = new DOMXPath($dom);
        
        // Rechercher les éléments avec data-test-audio-id
        Logger::info("parseTracksFromHtml - Recherche des éléments audio-card");
        $trackElements = $xpath->query("//div[contains(@data-test-audio-id, 'audio-card-')]");
        Logger::info("parseTracksFromHtml - Éléments trouvés: " . $trackElements->length);
        
        $processedIds = array();
        
        foreach ($trackElements as $element) {
            try {
                // Extraire l'ID depuis data-test-audio-id="audio-card-XXXXXXXXX"
                $dataTestId = $element->getAttribute('data-test-audio-id');
                if (preg_match('/audio-card-(\d+)/', $dataTestId, $idMatches)) {
                    $audioId = $idMatches[1];
                } else {
                    continue;
                }
                
                // Éviter les doublons
                if (in_array($audioId, $processedIds)) {
                    continue;
                }
                
                // Rechercher le titre dans l'attribut alt de l'image
                $imgElements = $xpath->query(".//img[@alt]", $element);
                $title = '';
                $imageUrl = '';
                
                if ($imgElements->length > 0) {
                    $img = $imgElements->item(0);
                    $title = trim($img->getAttribute('alt'));
                    
                    // Récupérer l'URL de l'image depuis src
                    $imageSrc = $img->getAttribute('src');
                    if (!empty($imageSrc)) {
                        if (strpos($imageSrc, 'http') === 0) {
                            $imageUrl = $imageSrc;
                        } else if (!empty($imageSrc)) {
                            $imageUrl = AUDIO_BASE_URL . ltrim($imageSrc, '/');
                        }
                    }
                }
                
                // Rechercher l'URL/slug de la musique dans les liens
                $linkElements = $xpath->query(".//a[contains(@href, '/dtarroz/')]", $element);
                $trackUrl = '';
                $slug = '';
                
                if ($linkElements->length > 0) {
                    $href = $linkElements->item(0)->getAttribute('href');
                    
                    // Extraire le slug depuis /dtarroz/audio/nom-de-la-musique
                    if (preg_match('/\/dtarroz\/(.+)$/', $href, $slugMatches)) {
                        $slug = $slugMatches[1];
                    }
                    
                    // Construire l'URL complète
                    if (strpos($href, 'http') === 0) {
                        $trackUrl = $href;
                    } else {
                        $trackUrl = AUDIO_BASE_URL . ltrim($href, '/');
                    }
                }
                
                // Si pas de slug trouvé, utiliser l'ID
                if (empty($slug)) {
                    $slug = 'audio/' . $audioId;
                }
                
                if (empty($trackUrl)) {
                    $trackUrl = AUDIO_BASE_URL . 'dtarroz/' . $slug;
                }
                
                // Récupérer le nombre d'écoutes depuis le tableau extrait
                $listenCount = isset($playsCounts[$audioId]) ? $playsCounts[$audioId] : 0;
                
                // Vérifier que nous avons au moins un ID et un titre, et qu'on n'a pas déjà traité cet ID
                if (!empty($audioId) && !empty($title)) {
                    // Éviter les doublons dans la même extraction
                    if (in_array($audioId, $processedIds)) {
                        Logger::warning("Musique déjà traitée, ignorée: {$title} (ID: {$audioId})");
                        continue;
                    }
                    
                    $tracks[] = array(
                        'audio_id' => $audioId,
                        'track_url' => $trackUrl,
                        'title' => $title,
                        'image_url' => $imageUrl,
                        'listen_count' => $listenCount
                    );
                    
                    $processedIds[] = $audioId;
                    
                    Logger::info("Musique extraite: {$title} (ID: {$audioId}, Plays: {$listenCount})");
                } else {
                    Logger::warning("Musique ignorée: titre ou ID manquant (ID: {$audioId}, Titre: {$title})");
                }
                
            } catch (Exception $e) {
                Logger::warning("Erreur lors du parsing d'un élément: " . $e->getMessage());
                continue;
            }
        }
        
        Logger::info("parseTracksFromHtml - Fin: " . count($tracks) . " pistes extraites");
        return $tracks;
    }
    
    /**
     * Extrait les playsCount depuis le JSON échappé dans le HTML
     * Format : \"id\":\"XXXXXXXXX\",…,\"playsCount\":YYY
     * @param string $html Contenu HTML
     * @return array Tableau associatif [audio_id => plays_count]
     */
    private function extractPlaysCountFromJson($html) {
        $playsCounts = array();
        
        // Le JSON est échappé avec des backslashes : \"id\":\"123\", \"playsCount\":456
        // Le playsCount se trouve ~3000-4000 caractères après l'ID (après le champ source)
        
        // Pattern : chercher "id":"XXXXXXXXX" suivi de "playsCount":YYY dans les 5000 premiers caractères
        // On vérifie aussi que c'est bien un objet audio de dtarroz
        if (preg_match_all('/\\\\"id\\\\":\\\\"(\d+)\\\\".{50,5000}?\\\\"authorUsername\\\\":\\\\"dtarroz\\\\".{50,5000}?\\\\"playsCount\\\\":(\d+)/', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $audioId = $match[1];
                $playsCount = intval($match[2]);
                
                // Ne garder que le premier playsCount trouvé pour chaque ID (éviter les doublons)
                if (!isset($playsCounts[$audioId])) {
                    $playsCounts[$audioId] = $playsCount;
                }
            }
        }
        
        return $playsCounts;
    }
    
    /**
     * Convertit une chaîne de caractères représentant un nombre d'écoutes en entier
     * Exemples: "120" -> 120, "1.2k" -> 1200, "15.8k" -> 15800, "2M" -> 2000000
     * @param string $text Texte contenant le nombre
     * @return int Nombre d'écoutes
     */
    private function parseListenCount($text) {
        // Nettoyer le texte
        $text = trim($text);
        $text = str_replace(',', '', $text);
        
        // Rechercher un nombre avec multiplicateur
        if (preg_match('/(\d+\.?\d*)\s*([kKmM])?/', $text, $matches)) {
            $number = floatval($matches[1]);
            $multiplier = isset($matches[2]) ? strtoupper($matches[2]) : '';
            
            switch ($multiplier) {
                case 'K':
                    $number *= 1000;
                    break;
                case 'M':
                    $number *= 1000000;
                    break;
            }
            
            return (int)$number;
        }
        
        return 0;
    }
    
    /**
     * Récupère toutes les musiques du profil en parcourant toutes les pages
     * @return array Tableau associatif avec 'success', 'tracks' et 'message'
     */
    public function extractAllTracks() {
        $allTracks = array();
        $page = 1;
        $hasMorePages = true;
        
        Logger::info("Début de l'extraction des musiques depuis " . $this->profileUrl);
        
        while ($hasMorePages) {
            // Construire l'URL de la page
            $url = $this->profileUrl . '?page=' . $page;
            
            Logger::info("Lecture de la page {$page}");
            
            // Récupérer le contenu de la page
            $html = $this->fetchPage($url);
            
            if ($html === false) {
                return array(
                    'success' => false,
                    'tracks' => array(),
                    'message' => "Erreur lors de la récupération de la page {$page}"
                );
            }
            
            // Parser les musiques
            $tracks = $this->parseTracksFromHtml($html);
            
            // Si aucune musique trouvée, arrêter
            if (empty($tracks)) {
                $hasMorePages = false;
                Logger::info("Aucune musique trouvée sur la page {$page}, arrêt du scan");
            } else {
                Logger::info(count($tracks) . " musique(s) trouvée(s) sur la page {$page}");
                $allTracks = array_merge($allTracks, $tracks);
                $page++;
            }
            
            // Petite pause pour ne pas surcharger le serveur
            usleep(500000); // 0.5 seconde
        }
        
        Logger::info("Extraction terminée: " . count($allTracks) . " musique(s) au total");
        
        return array(
            'success' => true,
            'tracks' => $allTracks,
            'message' => count($allTracks) . ' musique(s) trouvée(s)'
        );
    }
}
