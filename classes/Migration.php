<?php
/**
 * Classe Migration
 * Gère l'application des migrations SQL
 */

class Migration {
    
    /**
     * @var Database Instance de base de données
     */
    private $db;
    
    /**
     * @var string Chemin vers les fichiers de migration
     */
    private $migrationsPath;
    
    /**
     * Constructeur
     * @param Database $db Instance de base de données
     */
    public function __construct($db) {
        $this->db = $db;
        $this->migrationsPath = MIGRATIONS_PATH;
    }
    
    /**
     * Initialise la table schema_version si elle n'existe pas
     */
    private function initializeVersionTable() {
        $query = "
            CREATE TABLE IF NOT EXISTS schema_version (
                version INTEGER PRIMARY KEY,
                applied_at TEXT NOT NULL
            )
        ";
        $this->db->execute($query);
        
        Logger::info('Table schema_version initialisée');
    }
    
    /**
     * Récupère la version actuelle du schéma
     * @return int Version actuelle (0 si aucune migration appliquée)
     */
    private function getCurrentVersion() {
        if (!$this->db->tableExists('schema_version')) {
            return 0;
        }
        
        $query = "SELECT MAX(version) as version FROM schema_version";
        $result = $this->db->queryOne($query);
        
        return $result && $result['version'] ? (int)$result['version'] : 0;
    }
    
    /**
     * Liste tous les fichiers de migration disponibles
     * @return array Tableau des migrations avec leur numéro
     */
    private function listMigrationFiles() {
        $migrations = array();
        
        if (!is_dir($this->migrationsPath)) {
            Logger::warning('Dossier des migrations introuvable: ' . $this->migrationsPath);
            return $migrations;
        }
        
        $files = scandir($this->migrationsPath);
        
        foreach ($files as $file) {
            // Format attendu: 001_description.sql
            if (preg_match('/^(\d+)_.*\.sql$/', $file, $matches)) {
                $version = (int)$matches[1];
                $migrations[$version] = $this->migrationsPath . '/' . $file;
            }
        }
        
        // Trier par version
        ksort($migrations);
        
        return $migrations;
    }
    
    /**
     * Applique une migration
     * @param int $version Numéro de version
     * @param string $filePath Chemin vers le fichier SQL
     */
    private function applyMigration($version, $filePath) {
        Logger::info("Application de la migration {$version}: {$filePath}");
        
        // Lire le contenu du fichier
        if (!file_exists($filePath)) {
            throw new Exception("Fichier de migration introuvable: {$filePath}");
        }
        
        $sql = file_get_contents($filePath);
        
        if (empty($sql)) {
            throw new Exception("Fichier de migration vide: {$filePath}");
        }
        
        // Exécuter la migration dans une transaction
        try {
            $this->db->beginTransaction();
            
            // Séparer les requêtes par point-virgule
            $queries = explode(';', $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $this->db->execute($query);
                }
            }
            
            // Enregistrer la version
            $this->db->execute(
                "INSERT INTO schema_version (version, applied_at) VALUES (?, ?)",
                array($version, date(DATE_FORMAT))
            );
            
            $this->db->commit();
            
            Logger::info("Migration {$version} appliquée avec succès");
            
        } catch (Exception $e) {
            $this->db->rollback();
            Logger::error("Erreur lors de l'application de la migration {$version}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Exécute toutes les migrations manquantes
     * @return int Nombre de migrations appliquées
     */
    public function migrate() {
        // Initialiser la table de version
        $this->initializeVersionTable();
        
        // Récupérer la version actuelle
        $currentVersion = $this->getCurrentVersion();
        Logger::info("Version actuelle du schéma: {$currentVersion}");
        
        // Lister les migrations disponibles
        $migrations = $this->listMigrationFiles();
        
        if (empty($migrations)) {
            Logger::info('Aucune migration disponible');
            return 0;
        }
        
        // Appliquer les migrations manquantes
        $appliedCount = 0;
        
        foreach ($migrations as $version => $filePath) {
            if ($version > $currentVersion) {
                $this->applyMigration($version, $filePath);
                $appliedCount++;
            }
        }
        
        if ($appliedCount > 0) {
            Logger::info("{$appliedCount} migration(s) appliquée(s)");
        } else {
            Logger::info('Aucune migration à appliquer');
        }
        
        return $appliedCount;
    }
}
