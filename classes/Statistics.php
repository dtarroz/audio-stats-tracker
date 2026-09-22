<?php
/**
 * Classe Statistics
 * Calcule les statistiques de l'application
 */

class Statistics {
    
    /**
     * @var Database
     */
    private $db;
    
    /**
     * @var TrackRepository
     */
    private $trackRepository;
    
    /**
     * Constructeur
     * @param Database $db
     * @param TrackRepository $trackRepository
     */
    public function __construct($db, $trackRepository) {
        $this->db = $db;
        $this->trackRepository = $trackRepository;
    }
    
    /**
     * Récupère la date de la dernière synchronisation
     * @return string|null
     */
    public function getLastSyncDate() {
        $query = "
            SELECT MAX(captured_at) as last_sync
            FROM track_history
        ";
        
        $result = $this->db->queryOne($query);
        
        if ($result && $result['last_sync']) {
            return $result['last_sync'];
        }
        
        return null;
    }
    
    /**
     * Calcule la progression d'une musique sur une période
     * @param int $trackId ID de la musique
     * @param int $days Nombre de jours (0 = tout l'historique)
     * @return array Tableau avec first_value, current_value, progression
     */
    public function calculateProgression($trackId, $days) {
        if ($days == 0) {
            // Tout l'historique : prendre la première et dernière valeur
            $queryFirst = "
                SELECT listen_count
                FROM track_history
                WHERE track_id = ?
                ORDER BY captured_at ASC
                LIMIT 1
            ";
            $firstResult = $this->db->queryOne($queryFirst, array($trackId));
            
            $queryCurrent = "
                SELECT listen_count
                FROM track_history
                WHERE track_id = ?
                ORDER BY captured_at DESC
                LIMIT 1
            ";
            $currentResult = $this->db->queryOne($queryCurrent, array($trackId));
        } else {
            // Période spécifique : utiliser la même logique que getHistory
            // Récupérer les N derniers jours de sync (valeur max par jour)
            $query = "
                SELECT 
                    MAX(listen_count) as listen_count
                FROM track_history
                WHERE track_id = ?
                GROUP BY DATE(captured_at)
                ORDER BY DATE(captured_at) DESC
                LIMIT ?
            ";
            
            $results = $this->db->query($query, array($trackId, $days));
            
            if (empty($results)) {
                return array(
                    'first_value' => 0,
                    'current_value' => 0,
                    'progression' => null
                );
            }
            
            // La première valeur est la plus récente (index 0)
            // La dernière valeur est la plus ancienne (index count-1)
            $currentResult = array('listen_count' => $results[0]['listen_count']);
            $firstResult = array('listen_count' => $results[count($results) - 1]['listen_count']);
        }
        
        if (!$firstResult || !$currentResult) {
            return array(
                'first_value' => 0,
                'current_value' => 0,
                'progression' => null
            );
        }
        
        $firstValue = (int)$firstResult['listen_count'];
        $currentValue = (int)$currentResult['listen_count'];
        
        // Calcul de la progression (nombre d'écoutes)
        $progression = $currentValue - $firstValue;
        
        return array(
            'first_value' => $firstValue,
            'current_value' => $currentValue,
            'progression' => $progression
        );
    }

    /**
     * Calcule la progression du jour par rapport à la veille
     * @param int $trackId ID de la musique
     * @return array Tableau avec first_value, current_value, progression
     */
    public function calculateDailyProgression($trackId) {
        $query = "
            SELECT 
                MAX(listen_count) as listen_count
            FROM track_history
            WHERE track_id = ?
            GROUP BY DATE(captured_at)
            ORDER BY DATE(captured_at) DESC
            LIMIT 2
        ";

        $results = $this->db->query($query, array($trackId));

        if (count($results) < 2) {
            return array(
                'first_value' => 0,
                'current_value' => 0,
                'progression' => null
            );
        }

        $currentResult = array('listen_count' => $results[0]['listen_count']);
        $firstResult = array('listen_count' => $results[1]['listen_count']);

        $firstValue = (int)$firstResult['listen_count'];
        $currentValue = (int)$currentResult['listen_count'];

        return array(
            'first_value' => $firstValue,
            'current_value' => $currentValue,
            'progression' => $currentValue - $firstValue
        );
    }
    
    /**
     * Récupère le top des progressions
     * @param int $days Nombre de jours
     * @param int $limit Nombre de résultats
     * @return array
     */
    public function getTopProgressions($days, $limit = 10) {
        // Récupérer toutes les tracks disponibles
        $tracksQuery = "SELECT id, title, image_url FROM tracks WHERE available = 1";
        $tracks = $this->db->query($tracksQuery);
        
        $progressions = array();
        
        foreach ($tracks as $track) {
            // Calculer la progression pour chaque track
            $progression = $this->calculateProgression($track['id'], $days);
            
            if ($progression['progression'] !== null && $progression['progression'] > 0) {
                $progressions[] = array(
                    'id' => $track['id'],
                    'title' => $track['title'],
                    'image_url' => $track['image_url'],
                    'first_value' => $progression['first_value'],
                    'current_value' => $progression['current_value'],
                    'progression' => $progression['progression']
                );
            }
        }
        
        // Trier par progression décroissante
        usort($progressions, function($a, $b) {
            return $b['progression'] - $a['progression'];
        });
        
        // Retourner seulement les N premiers
        return array_slice($progressions, 0, $limit);
    }
    
    /**
     * Récupère les statistiques globales
     * @return array
     */
    public function getGlobalStats() {
        return array(
            'total_tracks' => $this->trackRepository->countAll(),
            'available_tracks' => $this->trackRepository->countAvailable(),
            'today_listens' => $this->getTodayListens(),
            'total_listen_count' => $this->trackRepository->getTotalListenCount(),
            'last_sync' => $this->getLastSyncDate(),
            'most_listened' => $this->trackRepository->getMostListened(),
            'top_listened' => $this->trackRepository->getTopListened(5)
        );
    }
    
    /**
     * Calcule le nombre total d'écoutes sur la dernière journée
     * @return int
     */
    public function getTodayListens() {
        // Comparer la dernière journée de sync avec la journée précédente
        $query = "
            SELECT 
                SUM(COALESCE(today.listen_count, 0) - COALESCE(yesterday.listen_count, 0)) as today_listens
            FROM tracks t
            LEFT JOIN (
                SELECT track_id, listen_count
                FROM track_history
                WHERE DATE(captured_at) = (
                    SELECT DATE(MAX(captured_at)) FROM track_history
                )
                GROUP BY track_id
                HAVING captured_at = MAX(captured_at)
            ) today ON t.id = today.track_id
            LEFT JOIN (
                SELECT track_id, listen_count
                FROM track_history
                WHERE DATE(captured_at) = (
                    SELECT DATE(MAX(captured_at), '-1 day') FROM track_history
                )
                GROUP BY track_id
                HAVING captured_at = MAX(captured_at)
            ) yesterday ON t.id = yesterday.track_id
            WHERE today.listen_count > COALESCE(yesterday.listen_count, 0)
        ";
        
        $result = $this->db->queryOne($query);
        
        if ($result && $result['today_listens']) {
            return max(0, (int)$result['today_listens']);
        }
        
        return 0;
    }
}
