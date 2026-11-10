-- Migration 2: ajout de la colonne likes_count dans track_history
ALTER TABLE track_history ADD COLUMN likes_count INTEGER NOT NULL DEFAULT 0;
