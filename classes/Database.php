<?php
/**
 * Classe Database
 * Gère la connexion à la base de données SQLite et l'exécution des requêtes
 */

class Database {
    
    /**
     * @var PDO Instance de connexion PDO
     */
    private $connection;
    
    /**
     * @var string Chemin vers la base de données
     */
    private $databasePath;
    
    /**
     * Constructeur
     * @param string $databasePath Chemin vers la base SQLite
     */
    public function __construct($databasePath) {
        $this->databasePath = $databasePath;
        $this->connect();
    }
    
    /**
     * Établit la connexion à la base de données
     * Crée la base si elle n'existe pas
     */
    private function connect() {
        try {
            // Créer le dossier si nécessaire
            $dir = dirname($this->databasePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Connexion PDO
            $this->connection = new PDO('sqlite:' . $this->databasePath);
            
            // Configuration PDO
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Activer les clés étrangères
            $this->connection->exec('PRAGMA foreign_keys = ON');
            
        } catch (PDOException $e) {
            Logger::error('Erreur de connexion à la base de données: ' . $e->getMessage());
            throw new Exception('Impossible de se connecter à la base de données');
        }
    }
    
    /**
     * Récupère l'instance de connexion PDO
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute une requête SELECT et retourne tous les résultats
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return array Résultats
     */
    public function query($query, $params = array()) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Logger::error('Erreur lors de l\'exécution de la requête: ' . $e->getMessage());
            throw new Exception('Erreur lors de l\'exécution de la requête');
        }
    }
    
    /**
     * Execute une requête SELECT et retourne un seul résultat
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|false Résultat ou false
     */
    public function queryOne($query, $params = array()) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            Logger::error('Erreur lors de l\'exécution de la requête: ' . $e->getMessage());
            throw new Exception('Erreur lors de l\'exécution de la requête');
        }
    }
    
    /**
     * Execute une requête INSERT, UPDATE ou DELETE
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return int Nombre de lignes affectées
     */
    public function execute($query, $params = array()) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            Logger::error('Erreur lors de l\'exécution de la requête: ' . $e->getMessage());
            throw new Exception('Erreur lors de l\'exécution de la requête');
        }
    }
    
    /**
     * Récupère l'ID de la dernière insertion
     * @return int
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Démarre une transaction
     */
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }
    
    /**
     * Valide une transaction
     */
    public function commit() {
        $this->connection->commit();
    }
    
    /**
     * Annule une transaction
     */
    public function rollback() {
        $this->connection->rollBack();
    }
    
    /**
     * Vérifie si une table existe
     * @param string $tableName Nom de la table
     * @return bool
     */
    public function tableExists($tableName) {
        $query = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
        $result = $this->queryOne($query, array($tableName));
        return $result !== false;
    }
}
