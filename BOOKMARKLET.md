# Bookmarklet de synchronisation - Audio Stats Tracker

## 🔖 Installation

### Étape 1 : Créer le bookmarklet

1. **Méthode glisser-déposer** :
   - Glissez ce lien vers votre barre de favoris : <a href="javascript:(function(){fetch('https://VOTRE-DOMAINE.com/audio/actions/api_sync.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({html:document.documentElement.outerHTML})}).then(r=>r.json()).then(d=>alert(d.success?d.message:'Erreur: '+d.error)).catch(e=>alert('Erreur réseau: '+e));})();">📊 Sync Audio Stats</a>

2. **Méthode manuelle** :
   - Créez un nouveau favori dans votre navigateur
   - Nom : `📊 Sync Audio Stats`
   - URL : (voir code ci-dessous)

### Code du bookmarklet

**⚠️ Remplacez `VOTRE-DOMAINE.com/audio` par votre URL réelle !**

```javascript
javascript:(function(){
  fetch('https://VOTRE-DOMAINE.com/audio/actions/api_sync.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({html: document.documentElement.outerHTML})
  })
  .then(r => r.json())
  .then(d => alert(d.success ? d.message : 'Erreur: ' + d.error))
  .catch(e => alert('Erreur réseau: ' + e));
})();
```

**Version minifiée (à copier dans l'URL du favori)** :

```
javascript:(function(){fetch('https://VOTRE-DOMAINE.com/audio/actions/api_sync.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({html:document.documentElement.outerHTML})}).then(r=>r.json()).then(d=>alert(d.success?d.message:'Erreur: '+d.error)).catch(e=>alert('Erreur réseau: '+e));})();
```

## 🚀 Utilisation

1. Allez sur **https://audio.com/dtarroz** (ou votre profil)
2. Attendez que la page soit complètement chargée
3. Cliquez sur votre bookmarklet **"📊 Sync Audio Stats"**
4. Une alerte affichera le résultat :
   - ✅ "Synchronisation terminée avec succès. X piste(s) mise(s) à jour."
   - ❌ "Erreur: [message d'erreur]"

## 🔧 Personnalisation

### Afficher plus de détails

```javascript
javascript:(function(){
  fetch('https://VOTRE-DOMAINE.com/audio/actions/api_sync.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({html: document.documentElement.outerHTML})
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      alert('✅ ' + d.message + '\n\n' +
            'Pistes trouvées: ' + d.stats.tracks_found + '\n' +
            'Pistes ajoutées: ' + d.stats.tracks_added + '\n' +
            'Pistes mises à jour: ' + d.stats.tracks_updated);
    } else {
      alert('❌ Erreur: ' + d.error);
    }
  })
  .catch(e => alert('⚠️ Erreur réseau: ' + e));
})();
```

### Console.log au lieu d'alert

```javascript
javascript:(function(){
  fetch('https://VOTRE-DOMAINE.com/audio/actions/api_sync.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({html: document.documentElement.outerHTML})
  })
  .then(r => r.json())
  .then(d => console.log('Sync result:', d))
  .catch(e => console.error('Sync error:', e));
})();
```

### Notification navigateur (au lieu d'alert)

```javascript
javascript:(function(){
  fetch('https://VOTRE-DOMAINE.com/audio/actions/api_sync.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({html: document.documentElement.outerHTML})
  })
  .then(r => r.json())
  .then(d => {
    if (Notification.permission === 'granted') {
      new Notification('Audio Stats', {
        body: d.success ? d.message : 'Erreur: ' + d.error,
        icon: 'https://audio.com/favicon.ico'
      });
    } else {
      alert(d.success ? d.message : 'Erreur: ' + d.error);
    }
  })
  .catch(e => alert('Erreur réseau: ' + e));
})();
```

## 🌐 Extension navigateur (Chrome/Firefox)

Pour automatiser la synchronisation, créez une extension :

### manifest.json

```json
{
  "manifest_version": 3,
  "name": "Audio Stats Sync",
  "version": "1.0",
  "description": "Synchronise automatiquement les stats Audio.com",
  "permissions": ["activeTab", "scripting"],
  "action": {
    "default_popup": "popup.html",
    "default_icon": "icon.png"
  }
}
```

### popup.html

```html
<!DOCTYPE html>
<html>
<head>
  <title>Audio Stats Sync</title>
  <style>
    body { width: 300px; padding: 20px; font-family: Arial; }
    button { width: 100%; padding: 10px; font-size: 16px; cursor: pointer; }
    #result { margin-top: 10px; }
  </style>
</head>
<body>
  <h2>📊 Audio Stats Sync</h2>
  <button id="sync">🔄 Synchroniser</button>
  <div id="result"></div>
  <script src="popup.js"></script>
</body>
</html>
```

### popup.js

```javascript
document.getElementById('sync').addEventListener('click', async () => {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  
  const result = await chrome.scripting.executeScript({
    target: { tabId: tab.id },
    func: () => document.documentElement.outerHTML
  });
  
  const html = result[0].result;
  
  fetch('https://VOTRE-DOMAINE.com/audio/actions/api_sync.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ html })
  })
  .then(r => r.json())
  .then(d => {
    document.getElementById('result').innerHTML = 
      d.success 
        ? '✅ ' + d.message 
        : '❌ ' + d.error;
  })
  .catch(e => {
    document.getElementById('result').innerHTML = '⚠️ Erreur: ' + e;
  });
});
```

## 📱 Script mobile (iOS/Android)

### iOS (Raccourci Siri)

1. Ouvrez l'app **Raccourcis**
2. Créez un nouveau raccourci
3. Ajoutez l'action **"Obtenir le contenu de la page web"**
4. Ajoutez l'action **"Obtenir le contenu de l'URL"**
   - URL : `https://VOTRE-DOMAINE.com/audio/actions/api_sync.php`
   - Méthode : POST
   - Corps : `{"html": "Contenu de la page web"}`
   - En-têtes : `Content-Type: application/json`
5. Ajoutez l'action **"Afficher le résultat"**

### Android (Tasker)

1. Créer une tâche "Audio Sync"
2. Action : HTTP Request
   - Méthode : POST
   - URL : `https://VOTRE-DOMAINE.com/audio/actions/api_sync.php`
   - Body : `{"html": "%html"}`
   - Headers : `Content-Type: application/json`

## 🔒 Sécurité

### HTTPS obligatoire

Le bookmarklet envoie le HTML complet de la page. **Utilisez toujours HTTPS** pour votre application.

### CORS

L'API autorise les requêtes cross-origin (`Access-Control-Allow-Origin: *`). Si vous voulez restreindre :

```php
// Dans actions/api_sync.php
header('Access-Control-Allow-Origin: https://audio.com');
```

### Rate limiting

Pour éviter les abus, ajoutez un rate limiting :

```php
// Limiter à 1 sync toutes les 5 minutes
$lastSync = getSetting('last_sync_time');
if ($lastSync && (time() - strtotime($lastSync)) < 300) {
    throw new Exception('Attendez 5 minutes entre chaque synchronisation');
}
```

## 🐛 Dépannage

### Le bookmarklet ne fait rien

1. Vérifiez que vous êtes sur **audio.com**
2. Ouvrez la console (F12) pour voir les erreurs
3. Vérifiez que l'URL de l'API est correcte

### Erreur CORS

Vérifiez que les headers CORS sont présents dans `api_sync.php` :

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
```

### Erreur 500

1. Consultez `/logs/application.log`
2. Vérifiez que PHP a assez de mémoire (`memory_limit`)
3. Vérifiez que le timeout est suffisant

### Aucune piste trouvée

Vérifiez que le HTML contient bien des éléments `data-test-audio-id` :

```javascript
// Dans la console sur audio.com
document.querySelectorAll('[data-test-audio-id]').length
```

Si ça retourne 0, le HTML n'est pas chargé complètement. Attendez quelques secondes avant de cliquer sur le bookmarklet.
