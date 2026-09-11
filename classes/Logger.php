<?php
/**
 * Classe Logger
 * Gère l'écriture des logs dans un fichier
 */

class Logger {
    
    /**
     * Écrit un message dans le fichier de log
     * @param string $message Message à logger
     * @param string $level Niveau de log (INFO, WARNING, ERROR)
     */
    private static function log($message, $level = 'INFO') {
        if ((!LOG_ERRORS && $level !== 'INFO') || !ENABLE_APPLICATION_LOGS) {
            return;
        }
        
        $logFile = LOG_FILE;
        $timestamp = date(DATE_FORMAT);
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        // Créer le dossier de logs si nécessaire
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Écrire dans le fichier
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }
    
    /**
     * Log un message d'information
     * @param string $message
     */
    public static function info($message) {
        self::log($message, 'INFO');
    }
    
    /**
     * Log un avertissement
     * @param string $message
     */
    public static function warning($message) {
        self::log($message, 'WARNING');
    }
    
    /**
     * Log une erreur
     * @param string $message
     */
    public static function error($message) {
        self::log($message, 'ERROR');
    }
}
