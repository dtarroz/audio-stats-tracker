-- Migration initiale
-- Création des tables principales

-- Table des musiques
CREATE TABLE IF NOT EXISTS tracks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    audio_id TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    track_url TEXT NOT NULL,
    image_url TEXT,
    available INTEGER DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

-- Table de l'historique des écoutes
CREATE TABLE IF NOT EXISTS track_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    track_id INTEGER NOT NULL,
    listen_count INTEGER NOT NULL DEFAULT 0,
    captured_at TEXT NOT NULL,
    FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
);

-- Table des paramètres
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    value TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_tracks_audio_id ON tracks(audio_id);
CREATE INDEX IF NOT EXISTS idx_tracks_available ON tracks(available);
CREATE INDEX IF NOT EXISTS idx_track_history_track_id ON track_history(track_id);
CREATE INDEX IF NOT EXISTS idx_track_history_captured_at ON track_history(captured_at);
CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(key);
