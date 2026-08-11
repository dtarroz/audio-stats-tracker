# API Documentation - Audio Stats Tracker

## 🔌 Vue d'ensemble

L'API REST permet de synchroniser les données en envoyant le HTML de la page Audio.com directement depuis un script externe (bookmarklet, extension navigateur, cron job, etc.).

**Endpoint principal** : `POST /actions/api_sync.php`

**Avantages** :
- ✅ Pas de blocage HTTP 403 (le HTML vient du navigateur authentifié)
- ✅ CORS activé (fonctionne depuis n'importe quel domaine)
- ✅ Peut être automatisé
- ✅ Logs détaillés

## 📡 Endpoint : Synchronisation

### POST /actions/api_sync.php

Synchronise les données en parsant le HTML fourni.

#### Requête

**Headers** :
```
Content-Type: application/json
```

**Body** :
```json
{
  "html": "<html>...</html>"
}
```

Ou en POST classique :
```
html=<html>...</html>
```

#### Réponse

**Succès (200)** :
```json
{
  "success": true,
  "message": "Synchronisation terminée avec succès. 10 piste(s) mise(s) à jour.",
  "stats": {
    "tracks_found": 10,
    "tracks_added": 0,
    "tracks_updated": 10,
    "errors": 0
  },
  "errors": []
}
```

**Erreur (500)** :
```json
{
  "success": false,
  "error": "Aucune donnée extraite du HTML fourni.",
  "details": {
    "file": "AudioExtractor.php",
    "line": 125
  }
}
```

#### Codes de statut

- **200** : Succès
- **500** : Erreur serveur (parsing, base de données, etc.)
- **405** : Méthode non autorisée (uniquement POST accepté)

## 💻 Exemples d'utilisation

### JavaScript (Fetch API)

```javascript
// Récupérer le HTML de la page courante
const html = document.documentElement.outerHTML;

// Envoyer à l'API
fetch('https://votre-domaine.com/audio/actions/api_sync.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ html })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('✅', data.message);
    console.log('Statistiques:', data.stats);
  } else {
    console.error('❌', data.error);
  }
})
.catch(error => {
  console.error('Erreur réseau:', error);
});
```

### cURL

```bash
# Récupérer le HTML (nécessite un navigateur headless pour pages JS)
html=$(curl -s "https://audio.com/dtarroz")

# Envoyer à l'API (échapper les quotes)
curl -X POST https://votre-domaine.com/audio/actions/api_sync.php \
  -H "Content-Type: application/json" \
  -d "{\"html\":\"$(echo $html | sed 's/"/\\"/g')\"}"
```

### Python

```python
import requests
from selenium import webdriver

# Option 1 : Avec Selenium (pour JS)
driver = webdriver.Chrome()
driver.get("https://audio.com/dtarroz")
html = driver.page_source
driver.quit()

# Option 2 : Avec requests (HTML statique uniquement)
# html = requests.get("https://audio.com/dtarroz").text

# Envoyer à l'API
response = requests.post(
    "https://votre-domaine.com/audio/actions/api_sync.php",
    json={"html": html}
)

data = response.json()
if data["success"]:
    print(f"✅ {data['message']}")
    print(f"Pistes trouvées: {data['stats']['tracks_found']}")
else:
    print(f"❌ Erreur: {data['error']}")
```

### Node.js

```javascript
const fetch = require('node-fetch');
const puppeteer = require('puppeteer');

(async () => {
  // Récupérer le HTML avec Puppeteer
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  await page.goto('https://audio.com/dtarroz');
  const html = await page.content();
  await browser.close();

  // Envoyer à l'API
  const response = await fetch('https://votre-domaine.com/audio/actions/api_sync.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ html })
  });

  const data = await response.json();
  console.log(data.success ? `✅ ${data.message}` : `❌ ${data.error}`);
})();
```

### PHP

```php
<?php
// Récupérer le HTML (nécessite un service headless pour JS)
$html = file_get_contents('https://audio.com/dtarroz');

// Envoyer à l'API
$ch = curl_init('https://votre-domaine.com/audio/actions/api_sync.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['html' => $html]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo $data['success'] ? "✅ {$data['message']}" : "❌ {$data['error']}";
```

## ⚙️ Configuration serveur

### Limites PHP

L'API ajuste automatiquement :

```php
set_time_limit(60);         // 60 secondes max
ini_set('memory_limit', '256M'); // 256 Mo de mémoire
```

Si vous avez un fichier HTML très volumineux, augmentez ces limites dans `actions/api_sync.php`.

### CORS

Par défaut, l'API accepte les requêtes de tous les domaines :

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
```

Pour restreindre à audio.com :

```php
header('Access-Control-Allow-Origin: https://audio.com');
```

### Rate Limiting (recommandé)

Ajoutez un rate limiting pour éviter les abus :

```php
// Dans api_sync.php, après les vérifications initiales
$settingsRepo = new SettingsRepository($db);
$lastSync = $settingsRepo->get('last_sync_time');

if ($lastSync && (time() - strtotime($lastSync)) < 300) {
    throw new Exception('Veuillez attendre 5 minutes entre chaque synchronisation.');
}

// Après la synchronisation réussie
$settingsRepo->set('last_sync_time', date('Y-m-d H:i:s'));
```

## 🔍 Debugging

### Logs

Tous les logs sont dans `/logs/application.log` :

```
[2026-08-02 10:30:15] [INFO] Synchronisation API démarrée - Taille HTML: 753253 octets
[2026-08-02 10:30:15] [INFO] Database initialisée
[2026-08-02 10:30:15] [INFO] AudioExtractor initialisé
[2026-08-02 10:30:15] [INFO] Début de l'extraction...
[2026-08-02 10:30:15] [INFO] parseTracksFromHtml - playsCount extraits: 10
[2026-08-02 10:30:15] [INFO] parseTracksFromHtml - Éléments trouvés: 10
[2026-08-02 10:30:15] [INFO] Musique extraite: Eclipse Hearts (ID: 1869057159311993, Plays: 9)
[2026-08-02 10:30:15] [INFO] Piste mise à jour: Eclipse Hearts (ID: 1869057159311993)
[2026-08-02 10:30:15] [INFO] Synchronisation terminée avec succès.
```

### Tester avec test_api.html

Une page de test est fournie :

1. Ouvrez `/test_api.html` dans votre navigateur
2. Collez le HTML d'Audio.com
3. Cliquez sur "Envoyer"
4. Voir la réponse JSON

### Erreurs courantes

#### "Aucune donnée extraite du HTML fourni"

**Cause** : Le HTML ne contient pas d'éléments `data-test-audio-id`.

**Solutions** :
- Vérifiez que vous êtes bien sur la page de profil (ex: audio.com/dtarroz)
- Attendez que la page soit complètement chargée (JavaScript doit s'exécuter)
- Vérifiez dans la console : `document.querySelectorAll('[data-test-audio-id]').length`

#### "Missing argument 2 for TrackRepository::update()"

**Cause** : Bug corrigé dans la version 1.0.1.

**Solution** : Mettez à jour `api_sync.php` avec la version corrigée.

#### "Erreur de connexion"

**Cause** : L'API n'est pas accessible ou CORS bloqué.

**Solutions** :
- Vérifiez que l'URL est correcte
- Vérifiez que HTTPS est utilisé (requis pour fetch depuis audio.com)
- Consultez la console navigateur pour les erreurs CORS

#### Timeout / Page blanche

**Cause** : Le parsing du HTML est trop long.

**Solutions** :
- Augmentez `set_time_limit(120)` dans `api_sync.php`
- Augmentez `memory_limit` à `512M`
- Divisez le HTML en plusieurs requêtes (une par page)

## 📊 Format des données extraites

### Structure des pistes

Chaque piste extraite contient :

```php
[
    'audio_id' => '1869057159311993',          // ID Audio.com (unique)
    'title' => 'Eclipse Hearts',               // Titre de la musique
    'track_url' => 'https://audio.com/dtarroz/audio/eclipse-hearts',
    'image_url' => 'https://audio.com/s3w/audio.com.static/audio/image/...',
    'listen_count' => 9                        // Nombre d'écoutes actuel
]
```

### Méthode d'extraction

1. **Extraction des playsCount** (regex sur JSON échappé) :
   ```regex
   \\"id\\":\\"(\d+)\\".{50,5000}?\\"authorUsername\\\\":\\\\"dtarroz\\\\".{50,5000}?\\"playsCount\\\\":(\d+)
   ```

2. **Extraction des éléments HTML** (XPath) :
   ```xpath
   //div[contains(@data-test-audio-id, 'audio-card-')]
   ```

3. **Assemblage** : Match audio_id entre les deux sources

## 🔒 Sécurité

### Protection des données

- ✅ Requêtes préparées PDO (pas d'injection SQL)
- ✅ Échappement HTML dans les vues
- ✅ Logs sans données sensibles
- ✅ Pas de stockage de cookies/tokens

### HTTPS recommandé

L'API transmet le HTML complet de la page. **Utilisez toujours HTTPS** pour votre application.

### Validation des données

Toutes les données extraites sont validées :

```php
if (empty($audioId) || empty($title)) {
    continue; // Ignore les données incomplètes
}
```

### Gestion des erreurs

Les erreurs PHP sont catchées et converties en JSON :

```php
try {
    // Traitement
} catch (Exception $e) {
    Logger::error("Erreur: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
```

## 📈 Monitoring

### Métriques à surveiller

1. **Taux de succès** :
   ```bash
   grep "Synchronisation terminée avec succès" logs/application.log | wc -l
   ```

2. **Erreurs** :
   ```bash
   grep "\[ERROR\]" logs/application.log
   ```

3. **Temps d'exécution** :
   - Ajoutez des timestamps dans les logs
   - Mesurez le temps entre "API démarrée" et "terminée"

4. **Taille des HTML** :
   ```bash
   grep "Taille HTML" logs/application.log
   ```

### Alertes recommandées

- ❌ Taux d'erreur > 5%
- ⚠️ Temps d'exécution > 30s
- ⚠️ Aucune synchronisation depuis 24h
- ❌ Erreurs "Aucune donnée extraite" répétées

## 🚀 Automatisation

### Cron job (serveur)

```bash
# Tous les jours à 2h du matin
0 2 * * * /usr/local/bin/sync-audio.sh
```

**sync-audio.sh** :
```bash
#!/bin/bash
# Nécessite un navigateur headless (Puppeteer, Selenium, etc.)

# Récupérer le HTML
html=$(node get-audio-html.js)

# Envoyer à l'API
curl -X POST https://votre-domaine.com/audio/actions/api_sync.php \
  -H "Content-Type: application/json" \
  -d "{\"html\":\"$(echo $html | sed 's/"/\\"/g')\"}" \
  >> /var/log/audio-sync.log 2>&1
```

### GitHub Actions

```yaml
name: Sync Audio Stats
on:
  schedule:
    - cron: '0 2 * * *'  # Tous les jours à 2h

jobs:
  sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup Node
        uses: actions/setup-node@v2
      - name: Install dependencies
        run: npm install puppeteer
      - name: Sync
        run: node sync.js
        env:
          API_URL: ${{ secrets.API_URL }}
```

---

**Version API** : 1.0.1  
**Dernière mise à jour** : Août 2026
