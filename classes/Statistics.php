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
     * @param int $days Nombre de jours
     * @return array Tableau avec first_value, current_value, progression
     */
    public function calculateProgression($trackId, $days) {
        // Récupérer la première valeur chronologique sur la période
        $dateLimit = date('Y-m-d', strtotime("-{$days} days"));
        
        $queryFirst = "
            SELECT listen_count
            FROM track_history
            WHERE track_id = ?
            AND DATE(captured_at) >= ?
            ORDER BY captured_at ASC
            LIMIT 1
        ";
        
        $firstResult = $this->db->queryOne($queryFirst, array($trackId, $dateLimit));
        
        // Récupérer la dernière valeur chronologique
        $queryCurrent = "
            SELECT listen_count
            FROM track_history
            WHERE track_id = ?
            ORDER BY captured_at DESC
            LIMIT 1
        ";
        
        $currentResult = $this->db->queryOne($queryCurrent, array($trackId));
        
        if (!$firstResult || !$currentResult) {
            return array(
                'first_value' => 0,
                'current_value' => 0,
                'progression' => null
            );
        }
        
        $firstValue = (int)$firstResult['listen_count'];
        $currentValue = (int)$currentResult['listen_count'];
        
        // Calcul de la progression
        if ($firstValue == 0) {
            // Si la première valeur est 0, calculer comme une croissance absolue
            if ($currentValue > 0) {
                $progression = null; // N/A car division par zéro impossible
            } else {
                $progression = 0;
            }
        } else {
            $progression = (($currentValue - $firstValue) / $firstValue) * 100;
        }
        
        return array(
            'first_value' => $firstValue,
            'current_value' => $currentValue,
            'progression' => $progression
        );
    }
    
    /**
     * Récupère le top des progressions
     * @param int $days Nombre de jours
     * @param int $limit Nombre de résultats
     * @return array
     */
    public function getTopProgressions($days, $limit = 10) {
        $dateLimit = date('Y-m-d', strtotime("-{$days} days"));
        
        $query = "
            SELECT 
                t.id AS id,
                t.title AS title,
                t.image_url AS image_url,
                first_values.first_value AS first_value,
                current_values.current_value AS current_value,
                CASE 
                    WHEN first_values.first_value = 0 THEN NULL
                    ELSE ((current_values.current_value - first_values.first_value) * 100.0 / first_values.first_value)
                END as progression
            FROM tracks t
            INNER JOIN (
                SELECT 
                    th1.track_id,
                    th1.listen_count as first_value
                FROM track_history th1
                INNER JOIN (
                    SELECT track_id, MIN(captured_at) as min_date
                    FROM track_history
                    WHERE DATE(captured_at) >= ?
                    GROUP BY track_id
                ) first_dates ON th1.track_id = first_dates.track_id AND th1.captured_at = first_dates.min_date
            ) first_values ON first_values.track_id = t.id
            INNER JOIN (
                SELECT 
                    th2.track_id,
                    th2.listen_count as current_value
                FROM track_history th2
                INNER JOIN (
                    SELECT track_id, MAX(captured_at) as max_date
                    FROM track_history
                    GROUP BY track_id
                ) last_dates ON th2.track_id = last_dates.track_id AND th2.captured_at = last_dates.max_date
            ) current_values ON current_values.track_id = t.id
            WHERE t.available = 1
            AND first_values.first_value > 0
            AND current_values.current_value > first_values.first_value
            ORDER BY progression DESC
            LIMIT ?
        ";
        
        return $this->db->query($query, array($dateLimit, $limit));
    }
    
    /**
     * Récupère les statistiques globales
     * @return array
     */
    public function getGlobalStats() {
        return array(
            'total_tracks' => $this->trackRepository->countAll(),
            'available_tracks' => $this->trackRepository->countAvailable(),
            'deleted_tracks' => $this->trackRepository->countDeleted(),
            'total_listen_count' => $this->trackRepository->getTotalListenCount(),
            'last_sync' => $this->getLastSyncDate(),
            'most_listened' => $this->trackRepository->getMostListened(),
            'top_listened' => $this->trackRepository->getTopListened(5)
        );
    }
}
