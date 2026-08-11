<?php
/**
 * Classe SettingsRepository
 * Gère les paramètres de l'application
 */

class SettingsRepository {
    
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
     * Récupère une valeur de paramètre
     * @param string $key Clé du paramètre
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public function get($key, $default = null) {
        $query = "SELECT value FROM settings WHERE key = ?";
        $result = $this->db->queryOne($query, array($key));
        
        if ($result) {
            return $result['value'];
        }
        
        return $default;
    }
    
    /**
     * Définit une valeur de paramètre
     * @param string $key Clé du paramètre
     * @param mixed $value Valeur
     */
    public function set($key, $value) {
        // Vérifier si la clé existe
        $existing = $this->get($key);
        
        if ($existing !== null) {
            // Mise à jour
            $query = "UPDATE settings SET value = ?, updated_at = ? WHERE key = ?";
            $this->db->execute($query, array($value, date(DATE_FORMAT), $key));
        } else {
            // Insertion
            $query = "INSERT INTO settings (key, value, created_at, updated_at) VALUES (?, ?, ?, ?)";
            $now = date(DATE_FORMAT);
            $this->db->execute($query, array($key, $value, $now, $now));
        }
    }
}
