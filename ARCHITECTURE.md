# Architecture de l'application - Audio Stats Tracker

## 📐 Architecture globale

L'application suit une **architecture MVC (Model-View-Controller) légère** adaptée à PHP 5.4.45+ sans framework.

```
┌─────────────┐
│  Navigateur │
└──────┬──────┘
       │ HTTP Request
       ▼
┌─────────────────────────────────────────┐
│           index.php (Contrôleur)        │
│  - Initialisation                       │
│  - Chargement des classes               │
│  - Exécution des migrations             │
│  - Préparation des données              │
│  - Inclusion des vues                   │
└──────┬──────────────────────────────────┘
       │
       ├──────────────────┬────────────────┬─────────────┐
       ▼                  ▼                ▼             ▼
┌─────────────┐    ┌─────────────┐  ┌───────────┐  ┌──────────┐
│   Models    │    │ Repositories│  │  Services │  │  Views   │
│             │    │             │  │           │  │          │
│ Track.php   │    │ TrackRepo   │  │ Statistics│  │ header   │
│             │◄───│ Settings    │  │ Audio     │  │ dashboard│
│             │    │ Repository  │  │ Extractor │  │ tracklist│
└─────────────┘    └──────┬──────┘  └─────┬─────┘  │ footer   │
                          │                │        └────┬─────┘
                          ▼                ▼             │
                   ┌──────────────┐  ┌──────────┐      │
                   │   Database   │  │  Logger  │      │
                   │   (SQLite)   │  │          │      │
                   └──────────────┘  └──────────┘      │
                                                        │
       ┌────────────────────────────────────────────────┘
       │ HTML/CSS/JS
       ▼
┌────────────────────────────────────────┐
│        Actions AJAX (API interne)     │
│  - sync.php (cachée)                   │
│  - api_sync.php (API REST publique)    │
│  - search.php                          │
│  - history.php                         │
│  - top_progressions.php                │
└────────────────────────────────────────┘

       │
       │ Synchronisation externe
       ▼
┌────────────────────────────────────────┐
│   Scripts externes (bookmarklet,      │
│   extension navigateur, cron...)       │
│   → POST HTML vers api_sync.php        │
└────────────────────────────────────────┘
```

## 🗂️ Organisation des fichiers

### Couche de présentation (Views)

```
views/
├── header.php      → En-tête HTML, balises <head>, CSS
├── footer.php      → Pied de page, scripts JS
├── dashboard.php   → Statistiques globales, bouton sync
└── track_list.php  → Liste des musiques, filtres, graphiques
```

**Responsabilité** : Affichage uniquement, aucune logique métier

### Couche métier (Models & Services)

```
classes/
├── Track.php              → Modèle de données d'une musique
├── TrackRepository.php    → Accès aux données des musiques
├── SettingsRepository.php → Gestion des paramètres
├── Statistics.php         → Calculs statistiques
├── AudioExtractor.php     → Extraction depuis Audio.com
├── Database.php           → Gestion de la connexion SQLite
├── Migration.php          → Système de migrations
└── Logger.php             → Journalisation
```

**Responsabilité** : Logique métier, accès aux données, traitements

### Couche contrôleur (Actions)

```
actions/
├── sync.php              → Synchronisation (Server-Sent Events) - cachée
├── api_sync.php          → API REST pour synchronisation externe
├── search.php            → Recherche et tri
├── history.php           → Historique d'une musique
└── top_progressions.php  → Top des progressions
```

**Responsabilité** : Point d'entrée AJAX/API, orchestration, réponses JSON

#### L'API REST (api_sync.php)

Endpoint public pour synchronisation externe :

**Input** :
- Méthode : POST
- Content-Type : application/json
- Body : `{"html": "<html>...</html>"}`

**Processing** :
1. Validation du HTML reçu
2. Extraction via `AudioExtractor::extractFromHtml()`
3. Création/mise à jour des pistes
4. Ajout de l'historique
5. Logs détaillés

**Output** :
```json
{
  "success": true/false,
  "message": "...",
  "stats": {
    "tracks_found": 10,
    "tracks_added": 0,
    "tracks_updated": 10,
    "errors": 0
  },
  "errors": []
}
```

**Sécurité** :
- Headers CORS activés (`Access-Control-Allow-Origin: *`)
- Timeout augmenté (60s)
- Memory limit augmenté (256M)
- Gestion d'erreur complète avec try-catch
- Logs de toutes les étapes

### Configuration

```
config/
└── config.php → Toutes les constantes de l'application
```

### Base de données

```
migrations/
└── 001_initial.sql → Création des tables initiales

database/
└── database.sqlite → Base SQLite (auto-créée)
```

### Assets

```
assets/
├── css/
│   └── style.css → Styles responsive
└── js/
    └── app.js    → JavaScript vanilla (compatible IE11)
```

## 🔄 Flux de données

### Flux principal (Chargement de la page)

```
1. Navigateur → index.php
2. index.php → Charge config.php
3. index.php → Initialise Database
4. index.php → Exécute migrations (Migration.php)
5. index.php → Récupère statistiques (Statistics.php)
6. index.php → Inclut views/header.php
7. index.php → Inclut views/dashboard.php (affiche stats)
8. index.php → Inclut views/track_list.php
9. index.php → Inclut views/footer.php
10. Navigateur reçoit HTML complet
11. JavaScript app.js s'exécute
12. app.js → Appelle actions/search.php (AJAX)
13. app.js → Affiche les musiques
14. app.js → Charge les graphiques (Chart.js)
```

### Flux de synchronisation (ancien - caché)

**Note** : La synchronisation automatique interne est désactivée pour éviter les blocages HTTP 403 d'Audio.com.

```
1. Clic sur bouton "Synchroniser" (caché)
2. JavaScript → EventSource vers actions/sync.php
3. sync.php → TrackRepository.markAllAsUnavailable()
4. sync.php → AudioExtractor.extractAllTracks()
5. AudioExtractor → Parcourt pages 1, 2, 3... via cURL
6. AudioExtractor → Parse HTML avec DOMDocument + XPath
7. AudioExtractor → Retourne tableau de musiques
8. sync.php → Pour chaque musique :
   - Cherche si existe (TrackRepository.findByAudioId)
   - Crée ou met à jour (create/update)
   - Ajoute historique (addHistory)
   - Envoie message de progression (Server-Sent Events)
9. sync.php → Fin, événement "done"
10. JavaScript → Recharge la page
```

### Flux de synchronisation via API (méthode actuelle)

```
1. Script externe (bookmarklet/extension) récupère le HTML de audio.com
2. Script → POST /actions/api_sync.php avec {"html": "..."}
3. api_sync.php → Vérifie la requête POST
4. api_sync.php → Lit le HTML depuis JSON body
5. api_sync.php → AudioExtractor.extractFromHtml(html)
6. AudioExtractor → Parse HTML avec DOMDocument + XPath
   - Étape 1 : Extraction playsCount depuis JSON échappé (regex)
   - Étape 2 : Extraction éléments data-test-audio-id (XPath)
   - Étape 3 : Assemblage des données (ID + titre + image + URL + plays)
7. AudioExtractor → Retourne tableau de musiques
8. api_sync.php → Pour chaque musique :
   - Cherche si existe (TrackRepository.findByAudioId)
   - Si existe : update(id, data) + addHistory()
   - Si nouveau : create(data) + addHistory()
   - Compteurs : tracksAdded++, tracksUpdated++
   - Logs : "Piste mise à jour: Titre (ID: xxx)"
9. api_sync.php → Retourne JSON avec stats
10. Script → Affiche message de confirmation
11. Utilisateur → Recharge la page pour voir les données
```

**Avantages de cette approche** :
- ✅ Pas de blocage HTTP 403 (le HTML vient du navigateur authentifié)
- ✅ Fonctionne depuis n'importe quel domaine (CORS)
- ✅ Peut être automatisé (extension navigateur, cron avec headless browser)
- ✅ Logs complets pour debugging

### Flux de recherche/tri

```
1. Utilisateur tape dans la recherche ou change le tri
2. JavaScript (debounce 500ms)
3. app.js → GET actions/search.php?search=...&orderBy=...&days=...
4. search.php → TrackRepository.findAll(filters)
5. search.php → Statistics.calculateProgression() si nécessaire
6. search.php → Retourne JSON
7. app.js → displayTracks(tracks)
8. app.js → Pour chaque musique : loadTrackChart()
9. app.js → GET actions/history.php?track_id=...&days=...
10. history.php → TrackRepository.getHistory()
11. history.php → Retourne JSON
12. app.js → renderChart() avec Chart.js
```

## 🏗️ Patterns et principes

### Separation of Concerns

Chaque classe a **une seule responsabilité** :

- `Database` : Connexion et requêtes SQL
- `TrackRepository` : Accès aux données des musiques
- `Statistics` : Calculs et agrégations
- `AudioExtractor` : Extraction de données externes
- `Logger` : Journalisation

### Repository Pattern

Les repositories (`TrackRepository`, `SettingsRepository`) encapsulent l'accès aux données :

```php
// ✅ Bon (via Repository)
$track = $trackRepository->findByAudioId('my-song-123');

// ❌ Mauvais (SQL direct dans le contrôleur)
$stmt = $db->query("SELECT * FROM tracks WHERE audio_id = ?");
```

### Dependency Injection

Les dépendances sont injectées via le constructeur :

```php
class TrackRepository {
    private $db;
    
    public function __construct($db) {
        $this->db = $db; // Injection
    }
}
```

### Single Point of Configuration

Toutes les constantes dans `config/config.php` :

```php
// ✅ Bon
define('AUDIO_PROFILE', 'dtarroz');
// Utilisé partout : AUDIO_PROFILE

// ❌ Mauvais
$profile = 'dtarroz'; // Dupliqué dans plusieurs fichiers
```

## 🗄️ Schéma de base de données

```sql
┌─────────────────────────┐
│       tracks            │
├─────────────────────────┤
│ id (PK)                 │
│ audio_id (UNIQUE)       │
│ title                   │
│ track_url               │
│ image_url               │
│ available (0/1)         │
│ created_at              │
│ updated_at              │
└────────┬────────────────┘
         │
         │ 1:N
         │
┌────────▼────────────────┐
│   track_history         │
├─────────────────────────┤
│ id (PK)                 │
│ track_id (FK)           │
│ listen_count            │
│ captured_at             │
└─────────────────────────┘

┌─────────────────────────┐
│      settings           │
├─────────────────────────┤
│ id (PK)                 │
│ key (UNIQUE)            │
│ value                   │
│ created_at              │
│ updated_at              │
└─────────────────────────┘

┌─────────────────────────┐
│   schema_version        │
├─────────────────────────┤
│ version (PK)            │
│ applied_at              │
└─────────────────────────┘
```

### Relations

- Une **musique** (`tracks`) possède plusieurs **enregistrements historiques** (`track_history`)
- Relation 1:N avec clé étrangère `track_id`
- Cascade on delete : si une musique est supprimée, son historique aussi

### Index

```sql
-- Recherche rapide par audio_id
CREATE INDEX idx_tracks_audio_id ON tracks(audio_id);

-- Filtrage par disponibilité
CREATE INDEX idx_tracks_available ON tracks(available);

-- Jointures avec l'historique
CREATE INDEX idx_track_history_track_id ON track_history(track_id);

-- Filtrage par date
CREATE INDEX idx_track_history_captured_at ON track_history(captured_at);
```

## 📦 Système de migrations

```
Démarrage → Migration.php
                │
                ▼
    Existe schema_version ?
        │           │
       NON         OUI
        │           │
        ▼           ▼
    Créer       Lire version actuelle
    table           │
        │           ▼
        └──→  Lister fichiers migrations/
                    │
                    ▼
            Filtrer versions > actuelle
                    │
                    ▼
            Pour chaque migration :
                - BEGIN TRANSACTION
                - Exécuter SQL
                - INSERT schema_version
                - COMMIT
```

### Ajouter une migration

1. Créer `/migrations/002_description.sql`
2. Numéroter séquentiellement
3. Au prochain chargement → appliquée automatiquement

## 🎨 Frontend (JavaScript)

### Architecture JS

```javascript
// État global
var app = {
    currentPeriod: 30,
    currentSort: 'default',
    currentSearch: '',
    tracks: [],
    charts: {}
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initSyncButton();
    initTopProgressionsTabs();
    initFilters();
    loadTracks();
    loadTopProgressions(30);
});
```

### Communication AJAX

```javascript
// XMLHttpRequest (compatible IE)
var xhr = new XMLHttpRequest();
xhr.open('GET', url, true);
xhr.onload = function() {
    if (xhr.status === 200) {
        var data = JSON.parse(xhr.responseText);
        // Traiter les données
    }
};
xhr.send();
```

### Server-Sent Events (Synchronisation)

```javascript
var eventSource = new EventSource('actions/sync.php');
eventSource.onmessage = function(event) {
    var data = JSON.parse(event.data);
    // Afficher progression
};
```

## 🔐 Sécurité

### Protection des fichiers sensibles

`.htaccess` :
```apache
RewriteRule ^(database|logs|migrations|classes|actions|config)/ - [F,L]
```

### Prévention des injections SQL

```php
// ✅ Bon : Requêtes préparées
$stmt = $db->prepare("SELECT * FROM tracks WHERE id = ?");
$stmt->execute(array($id));
```

### Échappement HTML

```php
// ✅ Bon
echo htmlspecialchars($track->title);

// En JavaScript
function escapeHtml(text) {
    var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;'};
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
```

### Gestion des erreurs

```php
// Pas d'erreur PHP brute en production
define('DISPLAY_ERRORS', false);

// Tout dans les logs
Logger::error($message);
```

## 📱 Responsive Design

### Approche Mobile-First

```css
/* Base : Mobile */
.tracks-grid {
    grid-template-columns: 1fr;
}

/* Tablette */
@media (min-width: 768px) {
    .tracks-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Desktop */
@media (min-width: 992px) {
    .tracks-grid {
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    }
}
```

### Graphiques adaptatifs

```javascript
new Chart(ctx, {
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});
```

## 🚀 Performance

### Optimisations

1. **Index SQL** : Recherches rapides sur audio_id, track_id, captured_at
2. **Agrégation en base** : Calculs SQL plutôt que PHP (COALESCE, sous-requêtes)
3. **Debounce** : Recherche après 500ms d'inactivité
4. **Lazy loading** : Graphiques chargés à la demande
5. **Cache busting** : Assets CSS/JS versionnés (style.css?v=1.0.1)
6. **Valeurs par défaut forcées** : Évite le cache Firefox sur les selects

### Requête optimisée

```php
// ✅ Bon : Une seule requête avec sous-requête pour éviter NULL
SELECT t.*, 
       COALESCE((SELECT listen_count FROM track_history 
                 WHERE track_id = t.id 
                 ORDER BY captured_at DESC LIMIT 1), 0) as current_listen_count
FROM tracks t

// ❌ Ancien : NULL possible, valeurs manquantes
SELECT t.*, 
       (SELECT listen_count FROM track_history 
        WHERE track_id = t.id 
        ORDER BY captured_at DESC LIMIT 1) as current_listen_count
FROM tracks t
```

### Calcul de progression corrigé

```php
// ✅ Bon : Valeurs chronologiques (ORDER BY date)
SELECT 
    th1.listen_count as first_value
FROM track_history th1
INNER JOIN (
    SELECT track_id, MIN(captured_at) as min_date
    FROM track_history
    WHERE DATE(captured_at) >= ?
    GROUP BY track_id
) first_dates ON th1.track_id = first_dates.track_id 
             AND th1.captured_at = first_dates.min_date

// ❌ Ancien : MIN/MAX sur les valeurs (incorrect)
SELECT MIN(listen_count) as first_value
FROM track_history
WHERE DATE(captured_at) >= ?
```

**Pourquoi c'est important** :
- MIN/MAX cherche la valeur minimale/maximale, pas la première chronologiquement
- Si les écoutes diminuent puis augmentent, MIN donne un mauvais point de départ
- La méthode chronologique garantit : première valeur = valeur à la date de début

## 🐛 Corrections de bugs importantes

### 1. Doublons dans la liste des musiques

**Problème** : Utilisation de `foreach` avec référence sans `unset()` après la boucle.

```php
// ❌ Ancien code (causait des doublons)
foreach ($tracks as &$track) {
    $track->progression = calculateProgression($track->id);
}
// $track reste une référence !

// ✅ Correction
foreach ($tracks as &$track) {
    $track->progression = calculateProgression($track->id);
}
unset($track); // CRITIQUE : Libère la référence
```

**Explication** : Sans `unset()`, `$track` reste une référence au dernier élément. Les itérations suivantes modifient ce dernier élément au lieu de créer de nouveaux éléments.

### 2. Pistes avec 0 écoutes invisibles

**Problème** : `NULL` dans current_listen_count rendait les pistes invisibles.

```php
// ❌ Ancien : Peut retourner NULL
(SELECT listen_count FROM track_history ...) as current_listen_count

// ✅ Correction : Force 0 au lieu de NULL
COALESCE((SELECT listen_count FROM track_history ...), 0) as current_listen_count
```

**Impact** : Les nouvelles pistes sans historique apparaissent avec 0 écoutes au lieu d'être cachées.

### 3. Ordre de tri incorrect

**Problème** : Tri par `id` (ordre d'insertion BDD) au lieu de `audio_id` (ordre Audio.com).

```php
// ❌ Ancien
ORDER BY t.id DESC

// ✅ Correction
ORDER BY t.audio_id DESC
```

**Explication** : Audio.com génère des IDs décroissants. Les nouvelles musiques ont des audio_id plus grands.

### 4. Extraction échoue pour certaines pistes

**Problème** : Pattern regex limité à 2000 caractères, certains playsCount étaient plus loin.

```php
// ❌ Ancien
.{50,2000}?\\\\"playsCount\\\\":(\d+)

// ✅ Correction
.{50,5000}?\\\\"playsCount\\\\":(\d+)
```

**Cas réel** : "Venom Halo" avait son playsCount à 3341 caractères de l'ID.

### 5. Top progressions vide

**Problème** : Utilisation de MIN/MAX au lieu des valeurs chronologiques.

```sql
-- ❌ Ancien (incorrect)
MIN(listen_count) as first_value

-- ✅ Correction (chronologique)
SELECT th1.listen_count as first_value
FROM track_history th1
INNER JOIN (
    SELECT track_id, MIN(captured_at) as min_date
    FROM track_history
    WHERE DATE(captured_at) >= ?
    GROUP BY track_id
) first_dates ON th1.track_id = first_dates.track_id 
             AND th1.captured_at = first_dates.min_date
```

### 6. Firefox garde l'ancien tri

**Problème** : Firefox restaure la valeur du select au rechargement (comportement natif).

```javascript
// ✅ Correction : Force la valeur par défaut
if (sortSelect) {
    sortSelect.value = 'default';
}
```

### 7. Assets CSS/JS en cache

**Problème** : Navigateurs gardent les anciennes versions en cache.

```html
<!-- ❌ Ancien -->
<link rel="stylesheet" href="assets/css/style.css">

<!-- ✅ Correction : Versioning -->
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo APP_VERSION; ?>">
```

**Utilisation** : Incrémenter `APP_VERSION` force le rechargement.

## 🔧 Patterns de debugging

### 1. Logs détaillés

```php
Logger::info("parseTracksFromHtml - Début");
Logger::info("parseTracksFromHtml - playsCount extraits: " . count($playsCounts));
Logger::info("parseTracksFromHtml - Éléments trouvés: " . $trackElements->length);
Logger::info("parseTracksFromHtml - Fin: " . count($tracks) . " pistes extraites");
```

**Avantage** : Permet d'identifier exactement où le code échoue.

### 2. Try-catch spécifiques

```php
try {
    $extractedData = $audioExtractor->extractFromHtml($htmlContent);
} catch (Exception $e) {
    Logger::error("Erreur pendant l'extraction: " . $e->getMessage());
    throw new Exception("Erreur d'extraction: " . $e->getMessage());
}
```

**Avantage** : Logs précis + propagation de l'erreur avec contexte.

### 3. Validation des données

```php
if (empty($extractedData)) {
    throw new Exception('Aucune donnée extraite. Vérifiez que le HTML contient des éléments data-test-audio-id.');
}
```

**Avantage** : Messages d'erreur explicites pour l'utilisateur.

### Comment tester

1. **Test de l'extracteur** :
   ```php
   $extractor = new AudioExtractor();
   $result = $extractor->extractAllTracks();
   var_dump($result);
   ```

2. **Test des migrations** :
   - Supprimer `database.sqlite`
   - Recharger `index.php`
   - Vérifier la création automatique

3. **Test des statistiques** :
   ```php
   $stats = $statistics->getGlobalStats();
   print_r($stats);
   ```

4. **Test de l'API** :
   - Utilisez `/test_api.html` pour tester l'API de synchronisation
   - Coller du HTML et voir la réponse JSON

5. **Test de progression** :
   ```php
   $progression = $statistics->calculateProgression($trackId, 30);
   echo "Progression: " . $progression['progression'] . "%\n";
   ```

## 📚 Meilleures pratiques apprises

### 1. Toujours utiliser COALESCE pour éviter NULL

```sql
-- ✅ Bon
COALESCE((SELECT ...), 0) as value

-- ❌ Risqué
(SELECT ...) as value
```

### 2. Unset les références après foreach

```php
// ✅ Bon
foreach ($items as &$item) {
    // Modification
}
unset($item);

// ❌ Risque de bugs
foreach ($items as &$item) {
    // Modification
}
```

### 3. Valeurs chronologiques > MIN/MAX pour progressions

```sql
-- ✅ Chronologique (correct)
WHERE captured_at = (SELECT MIN(captured_at) ...)

-- ❌ MIN/MAX (peut être incorrect)
MIN(listen_count)
```

### 4. Logs à chaque étape critique

```php
Logger::info("Avant opération");
// Opération
Logger::info("Après opération - résultat: " . $result);
```

### 5. Versioning des assets

```html
<!-- Toujours ajouter ?v= -->
<link href="style.css?v=<?php echo APP_VERSION; ?>">
```

### 6. Augmenter les limites pour opérations lourdes

```php
set_time_limit(60);
ini_set('memory_limit', '256M');
```

### 7. Validation explicite avec messages clairs

```php
if (empty($data)) {
    throw new Exception('Aucune donnée. Vérifiez X, Y, Z.');
}
```

### 8. API REST : Headers CORS + Gestion d'erreur

```php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

try {
    // Traitement
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
```

## 📝 Conventions de code

### Nommage

```php
// Classes : PascalCase
class TrackRepository {}

// Méthodes : camelCase
public function findByAudioId() {}

// Variables : camelCase
$trackRepository = new TrackRepository();

// Constantes : UPPER_SNAKE_CASE
define('DATABASE_PATH', '...');

// SQL : snake_case
CREATE TABLE track_history (...)
```

### Commentaires

```php
/**
 * Description de la classe
 */
class MyClass {
    /**
     * Description de la méthode
     * @param string $param Description du paramètre
     * @return array Description du retour
     */
    public function myMethod($param) {}
}
```

## 🔄 Évolutions possibles

### Ajout d'une fonctionnalité

1. Créer la migration SQL si besoin
2. Ajouter/modifier le modèle et repository
3. Créer l'action AJAX si nécessaire
4. Mettre à jour la vue
5. Ajouter le JavaScript

### Exemple : Ajouter un système de tags

1. **Migration** : `002_add_tags.sql`
   ```sql
   CREATE TABLE tags (...);
   CREATE TABLE track_tags (...);
   ```

2. **Repository** : `TagRepository.php`

3. **Action** : `actions/manage_tags.php`

4. **Vue** : Ajouter dans `track_list.php`

5. **JS** : Gestion des tags dans `app.js`

## 📋 Historique des versions

### Version 1.0.1 (Août 2026)

**Architecture** :
- Ajout de l'API REST publique (`/actions/api_sync.php`)
- Désactivation de la synchronisation automatique interne
- Flux de synchronisation externe via bookmarklet/extension

**Améliorations techniques** :
- Système de versioning des assets (cache busting)
- Gestion NULL améliorée (COALESCE partout)
- Calcul de progression chronologique (au lieu de MIN/MAX)
- Logs détaillés à chaque étape critique
- Gestion d'erreur complète avec try-catch spécifiques

**Corrections** :
- Fix doublons (unset après foreach par référence)
- Fix ordre de tri (audio_id au lieu de id)
- Fix extraction pattern (limite 5000 chars)
- Fix Firefox cache (valeur forcée)
- Fix top progressions vide (sous-requêtes chronologiques)

### Version 1.0.0 (Juillet 2026)

- Architecture initiale
- MVC léger sans framework
- Compatibilité PHP 5.4.45+

---

Cette architecture permet une **maintenabilité optimale** tout en restant **simple** et **compatible PHP 5.4.45+**. Les patterns appliqués (Repository, Dependency Injection, Separation of Concerns) assurent une évolution facile du code sans complexité inutile.
