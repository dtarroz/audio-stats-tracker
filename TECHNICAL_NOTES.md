# Documentation technique - Audio Stats Tracker

## ⚠️ Important : Adaptation de l'extracteur Audio.com

### Contexte

L'application utilise la classe `AudioExtractor.php` pour extraire les données depuis le profil Audio.com. Comme la structure HTML exacte du site n'est pas connue à l'avance, **il sera probablement nécessaire d'adapter les sélecteurs XPath** après avoir inspecté la page réelle.

### Fichier à adapter : `/classes/AudioExtractor.php`

#### Méthode concernée : `parseTracksFromHtml()`

Cette méthode utilise DOMXPath pour extraire :
- L'identifiant unique (audio_id)
- L'URL de la musique
- Le titre
- L'URL de l'image
- Le nombre d'écoutes

#### Comment adapter les sélecteurs

1. **Ouvrez la page du profil dans votre navigateur** :
   ```
   https://audio.com/dtarroz
   ```

2. **Inspectez le HTML** (clic droit > Inspecter l'élément) :
   - Identifiez les éléments contenant les musiques
   - Notez les classes CSS, les IDs ou la structure HTML
   - Repérez comment le titre, l'image et le nombre d'écoutes sont structurés

3. **Modifiez les sélecteurs XPath** dans la méthode `parseTracksFromHtml()` :

   ```php
   // Exemple actuel (à adapter) :
   $trackElements = $xpath->query("//a[contains(@href, '/dtarroz/')]");
   
   // Exemples possibles selon la structure réelle :
   
   // Si les musiques sont dans des divs avec classe "track" :
   $trackElements = $xpath->query("//div[@class='track']");
   
   // Si les musiques sont dans des articles :
   $trackElements = $xpath->query("//article[contains(@class, 'music')]");
   
   // Si les musiques sont dans une liste :
   $trackElements = $xpath->query("//ul[@class='track-list']/li");
   ```

4. **Adaptez l'extraction de chaque information** :

   ```php
   // Pour le titre :
   // Actuel :
   $title = trim($element->textContent);
   
   // Selon la structure :
   $titleElement = $xpath->query(".//h3[@class='track-title']", $element);
   $title = $titleElement->length > 0 ? trim($titleElement->item(0)->textContent) : '';
   
   // Pour l'image :
   $imgElements = $xpath->query(".//img[@class='track-cover']", $element);
   
   // Pour le nombre d'écoutes :
   $listenElements = $xpath->query(".//*[@class='play-count']", $element);
   ```

### Exemples de structures HTML possibles

#### Structure type 1 : Cartes de musique

```html
<div class="track-card">
    <a href="/dtarroz/my-song-123">
        <img src="/images/cover.jpg" class="track-cover">
        <h3 class="track-title">Titre de la musique</h3>
        <span class="play-count">1.2k</span>
    </a>
</div>
```

Adaptation :
```php
$trackElements = $xpath->query("//div[@class='track-card']");
$trackUrl = $xpath->query(".//a", $element)->item(0)->getAttribute('href');
$title = $xpath->query(".//h3[@class='track-title']", $element)->item(0)->textContent;
$imageUrl = $xpath->query(".//img[@class='track-cover']", $element)->item(0)->getAttribute('src');
$listenText = $xpath->query(".//*[@class='play-count']", $element)->item(0)->textContent;
```

#### Structure type 2 : Liste de musiques

```html
<ul class="music-list">
    <li data-track-id="123">
        <div class="track-info">
            <a href="/dtarroz/my-song-123">Titre</a>
            <img src="cover.jpg">
        </div>
        <div class="track-stats">
            <span class="listens">1200 plays</span>
        </div>
    </li>
</ul>
```

Adaptation :
```php
$trackElements = $xpath->query("//ul[@class='music-list']/li");
$audioId = $element->getAttribute('data-track-id');
$linkElement = $xpath->query(".//div[@class='track-info']/a", $element)->item(0);
$title = $linkElement->textContent;
$trackUrl = $linkElement->getAttribute('href');
$imageUrl = $xpath->query(".//img", $element)->item(0)->getAttribute('src');
$listenText = $xpath->query(".//*[@class='listens']", $element)->item(0)->textContent;
```

### Test de l'extraction

Pour tester si vos sélecteurs fonctionnent :

1. **Créez un fichier de test** `/test_extractor.php` :

```php
<?php
require_once 'config/config.php';
require_once 'classes/Logger.php';
require_once 'classes/AudioExtractor.php';

$extractor = new AudioExtractor();
$result = $extractor->extractAllTracks();

echo "<pre>";
print_r($result);
echo "</pre>";
```

2. **Exécutez-le** via navigateur ou CLI :
```bash
php test_extractor.php
```

3. **Vérifiez** que les données retournées sont correctes

### Débogage

Si l'extraction ne fonctionne pas :

1. **Affichez le HTML brut** :
   ```php
   $html = $this->fetchPage($url);
   echo htmlspecialchars($html);
   ```

2. **Testez vos XPath en ligne** :
   - Copiez le HTML
   - Utilisez un testeur XPath en ligne
   - Validez vos sélecteurs

3. **Ajoutez des logs** :
   ```php
   Logger::info("Nombre d'éléments trouvés : " . $trackElements->length);
   ```

### Conversion du nombre d'écoutes

La méthode `parseListenCount()` gère déjà les formats courants :
- `120` → 120
- `1.2k` → 1200
- `15.8k` → 15800
- `2M` → 2000000

Si Audio.com utilise un format différent, adaptez cette méthode.

### Gestion des URLs relatives

Si les URLs sont relatives (ex: `/dtarroz/song` au lieu de `https://audio.com/dtarroz/song`), le code les convertit automatiquement :

```php
if (strpos($trackUrl, 'http') !== 0) {
    $trackUrl = AUDIO_BASE_URL . ltrim($trackUrl, '/');
}
```

### Points de vigilance

1. **Pagination** : L'application parcourt automatiquement toutes les pages (`?page=1`, `?page=2`, etc.)
2. **Rate limiting** : Une pause de 0.5 secondes est ajoutée entre chaque page
3. **User-Agent** : Modifiable dans `config/config.php`
4. **Timeout** : Par défaut 30 secondes, modifiable dans `config/config.php`

## 🔍 Autres adaptations possibles

### Changement du profil Audio.com

Dans `/config/config.php` :

```php
// Changer le profil à suivre
define('AUDIO_PROFILE', 'autre-profil');
```

### Modification de la fréquence de synchronisation

Vous pouvez créer une tâche cron pour synchroniser automatiquement :

```bash
# Toutes les heures
0 * * * * php /path/to/audio-stats/actions/sync.php > /dev/null 2>&1
```

Pour cela, créez un fichier `/cron_sync.php` :

```php
<?php
// Script CLI pour la synchronisation automatique
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/TrackRepository.php';
require_once __DIR__ . '/classes/Track.php';
require_once __DIR__ . '/classes/AudioExtractor.php';

$db = new Database(DATABASE_PATH);
$trackRepository = new TrackRepository($db);
$extractor = new AudioExtractor();

$trackRepository->markAllAsUnavailable();
$result = $extractor->extractAllTracks();

if ($result['success']) {
    foreach ($result['tracks'] as $trackData) {
        $track = $trackRepository->findByAudioId($trackData['audio_id']);
        if ($track) {
            $trackRepository->update($track->id, $trackData);
            $trackId = $track->id;
        } else {
            $trackId = $trackRepository->create($trackData);
        }
        $trackRepository->addHistory($trackId, $trackData['listen_count']);
    }
    Logger::info("Synchronisation cron terminée : " . count($result['tracks']) . " musiques");
}
```

## 📞 Support technique

Si vous rencontrez des difficultés :

1. Activez le mode debug dans `config/config.php` :
   ```php
   define('DISPLAY_ERRORS', true);
   ```

2. Consultez les logs détaillés dans `/logs/application.log`

3. Vérifiez que toutes les extensions PHP sont activées :
   ```bash
   php -m | grep -E 'pdo|curl|dom'
   ```

---

**Note** : Cette documentation technique est fournie pour faciliter l'adaptation de l'application à la structure réelle du site Audio.com.
