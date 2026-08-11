<?php
/**
 * Classe Track
 * Représente une musique
 */

class Track {
    
    /**
     * @var int
     */
    public $id;
    
    /**
     * @var string
     */
    public $audioId;
    
    /**
     * @var string
     */
    public $title;
    
    /**
     * @var string
     */
    public $trackUrl;
    
    /**
     * @var string
     */
    public $imageUrl;
    
    /**
     * @var bool
     */
    public $available;
    
    /**
     * @var string
     */
    public $createdAt;
    
    /**
     * @var string
     */
    public $updatedAt;
    
    /**
     * @var int Nombre d'écoutes actuel
     */
    public $currentListenCount;
    
    /**
     * Constructeur à partir d'un tableau de données
     * @param array $data
     */
    public function __construct($data = array()) {
        if (!empty($data)) {
            $this->id = isset($data['id']) ? (int)$data['id'] : null;
            $this->audioId = isset($data['audio_id']) ? $data['audio_id'] : '';
            $this->title = isset($data['title']) ? $data['title'] : '';
            $this->trackUrl = isset($data['track_url']) ? $data['track_url'] : '';
            $this->imageUrl = isset($data['image_url']) ? $data['image_url'] : '';
            $this->available = isset($data['available']) ? (bool)$data['available'] : true;
            $this->createdAt = isset($data['created_at']) ? $data['created_at'] : '';
            $this->updatedAt = isset($data['updated_at']) ? $data['updated_at'] : '';
            $this->currentListenCount = isset($data['current_listen_count']) ? (int)$data['current_listen_count'] : 0;
        }
    }
    
    /**
     * Convertit l'objet en tableau pour l'insertion en base
     * @return array
     */
    public function toArray() {
        return array(
            'audio_id' => $this->audioId,
            'title' => $this->title,
            'track_url' => $this->trackUrl,
            'image_url' => $this->imageUrl,
            'available' => $this->available ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        );
    }
}
