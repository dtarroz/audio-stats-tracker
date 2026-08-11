<?php
/**
 * Classe TrackRepository
 * Gère l'accès aux données des musiques
 */

class TrackRepository {
    
    /**
     * @var Database
     */
    private $db;
    
    /**
     * Constructeur
     * @param Database $db
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Trouve une musique par son audio_id
     * @param string $audioId
     * @return Track|null
     */
    public function findByAudioId($audioId) {
        $query = "
            SELECT t.*, 
                   COALESCE((SELECT listen_count 
                    FROM track_history 
                    WHERE track_id = t.id 
                    ORDER BY captured_at DESC 
                    LIMIT 1), 0) as current_listen_count
            FROM tracks t
            WHERE audio_id = ?
        ";
        
        $result = $this->db->queryOne($query, array($audioId));
        
        if ($result) {
            return new Track($result);
        }
        
        return null;
    }
    
    /**
     * Crée une nouvelle musique
     * @param array $data Données de la musique
     * @return int ID de la musique créée
     */
    public function create($data) {
        $now = date(DATE_FORMAT);
        
        $query = "
            INSERT INTO tracks (audio_id, title, track_url, image_url, available, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $this->db->execute($query, array(
            $data['audio_id'],
            $data['title'],
            $data['track_url'],
            isset($data['image_url']) ? $data['image_url'] : '',
            1, // available = true
            $now,
            $now
        ));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Met à jour une musique existante
     * @param int $id ID de la musique
     * @param array $data Nouvelles données
     */
    public function update($id, $data) {
        $query = "
            UPDATE tracks 
            SET title = ?,
                track_url = ?,
                image_url = ?,
                available = ?,
                updated_at = ?
            WHERE id = ?
        ";
        
        $this->db->execute($query, array(
            $data['title'],
            $data['track_url'],
            isset($data['image_url']) ? $data['image_url'] : '',
            isset($data['available']) ? $data['available'] : 1,
            date(DATE_FORMAT),
            $id
        ));
    }
    
    /**
     * Marque toutes les musiques comme non disponibles
     */
    public function markAllAsUnavailable() {
        $query = "UPDATE tracks SET available = 0, updated_at = ?";
        $this->db->execute($query, array(date(DATE_FORMAT)));
    }
    
    /**
     * Récupère toutes les musiques avec filtres et tri
     * @param array $filters Filtres (search, orderBy)
     * @return array
     */
    public function findAll($filters = array()) {
        $where = array();
        $params = array();
        $orderBy = "t.audio_id DESC"; // Par défaut: ordre Audio.com (plus récentes en premier)
        
        // Filtre de recherche
        if (!empty($filters['search'])) {
            $where[] = "t.title LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        
        // Ordre de tri
        if (!empty($filters['orderBy'])) {
            switch ($filters['orderBy']) {
                case 'title_asc':
                    $orderBy = "t.title ASC";
                    break;
                case 'title_desc':
                    $orderBy = "t.title DESC";
                    break;
                case 'listen_count':
                    $orderBy = "current_listen_count DESC";
                    break;
                case 'progression':
                    // Sera trié en PHP après calcul, utiliser ordre par défaut
                    $orderBy = "t.audio_id DESC";
                    break;
                default:
                    $orderBy = "t.audio_id DESC";
            }
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $query = "
            SELECT t.*, 
                   COALESCE((SELECT listen_count 
                    FROM track_history 
                    WHERE track_id = t.id 
                    ORDER BY captured_at DESC 
                    LIMIT 1), 0) as current_listen_count
            FROM tracks t
            {$whereClause}
            ORDER BY {$orderBy}
        ";
        
        $results = $this->db->query($query, $params);
        $tracks = array();
        
        foreach ($results as $result) {
            $tracks[] = new Track($result);
        }
        
        return $tracks;
    }
    
    /**
     * Compte le nombre total de musiques
     * @return int
     */
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM tracks";
        $result = $this->db->queryOne($query);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Compte le nombre de musiques disponibles
     * @return int
     */
    public function countAvailable() {
        $query = "SELECT COUNT(*) as count FROM tracks WHERE available = 1";
        $result = $this->db->queryOne($query);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Compte le nombre de musiques supprimées
     * @return int
     */
    public function countDeleted() {
        $query = "SELECT COUNT(*) as count FROM tracks WHERE available = 0";
        $result = $this->db->queryOne($query);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Ajoute un enregistrement dans l'historique
     * @param int $trackId ID de la musique
     * @param int $listenCount Nombre d'écoutes
     */
    public function addHistory($trackId, $listenCount) {
        $query = "
            INSERT INTO track_history (track_id, listen_count, captured_at)
            VALUES (?, ?, ?)
        ";
        
        $this->db->execute($query, array(
            $trackId,
            $listenCount,
            date(DATE_FORMAT)
        ));
    }
    
    /**
     * Récupère l'historique d'une musique
     * @param int $trackId ID de la musique
     * @param int $days Nombre de jours (0 = tous)
     * @return array
     */
    public function getHistory($trackId, $days = 0) {
        $params = array($trackId);
        
        if ($days > 0) {
            // Limiter exactement au nombre de jours demandé
            $query = "
                SELECT 
                    DATE(captured_at) as date,
                    MAX(listen_count) as listen_count
                FROM track_history
                WHERE track_id = ?
                GROUP BY DATE(captured_at)
                ORDER BY date DESC
                LIMIT ?
            ";
            $params[] = $days;
            
            // Récupérer les résultats et les inverser pour avoir l'ordre chronologique
            $results = $this->db->query($query, $params);
            return array_reverse($results);
        } else {
            // Sans filtre, retourner tout l'historique
            $query = "
                SELECT 
                    DATE(captured_at) as date,
                    MAX(listen_count) as listen_count
                FROM track_history
                WHERE track_id = ?
                GROUP BY DATE(captured_at)
                ORDER BY date ASC
            ";
            
            return $this->db->query($query, $params);
        }
    }
    
    /**
     * Compte le nombre total d'enregistrements dans l'historique
     * @return int
     */
    public function countHistoryRecords() {
        $query = "SELECT COUNT(*) as count FROM track_history";
        $result = $this->db->queryOne($query);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Calcule le nombre total d'écoutes actuelles de toutes les musiques
     * @return int
     */
    public function getTotalListenCount() {
        $query = "
            SELECT SUM(
                COALESCE((SELECT listen_count 
                    FROM track_history 
                    WHERE track_id = t.id 
                    ORDER BY captured_at DESC 
                    LIMIT 1), 0)
            ) as total
            FROM tracks t
        ";
        $result = $this->db->queryOne($query);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Récupère la musique la plus écoutée
     * @return Track|null
     */
    public function getMostListened() {
        $query = "
            SELECT t.*, 
                   COALESCE((SELECT listen_count 
                    FROM track_history 
                    WHERE track_id = t.id 
                    ORDER BY captured_at DESC 
                    LIMIT 1), 0) as current_listen_count
            FROM tracks t
            WHERE t.available = 1
            ORDER BY current_listen_count DESC
            LIMIT 1
        ";
        
        $result = $this->db->queryOne($query);
        
        if ($result) {
            return new Track($result);
        }
        
        return null;
    }
    
    /**
     * Récupère les N musiques les plus écoutées
     * @param int $limit Nombre de musiques à retourner
     * @return array
     */
    public function getTopListened($limit = 3) {
        $query = "
            SELECT t.*, 
                   COALESCE((SELECT listen_count 
                    FROM track_history 
                    WHERE track_id = t.id 
                    ORDER BY captured_at DESC 
                    LIMIT 1), 0) as current_listen_count
            FROM tracks t
            WHERE t.available = 1
            ORDER BY current_listen_count DESC
            LIMIT ?
        ";
        
        $results = $this->db->query($query, array($limit));
        $tracks = array();
        
        foreach ($results as $result) {
            $tracks[] = new Track($result);
        }
        
        return $tracks;
    }
}
