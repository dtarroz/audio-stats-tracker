# 🛡️ Problème HTTP 403 Forbidden - Audio.com

## 🔴 Problème identifié

Audio.com retourne **HTTP 403 (Forbidden)** lorsque votre serveur essaie d'accéder à la page.

Cela signifie que **audio.com détecte et bloque les requêtes automatisées** (bots/scrapers) pour protéger leur site.

## ✅ Solution 1 : Headers HTTP améliorés (IMPLÉMENTÉ)

J'ai mis à jour `AudioExtractor.php` avec des headers HTTP réalistes qui simulent un navigateur Chrome moderne :

```php
$headers = array(
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
    'Accept-Encoding: gzip, deflate, br',
    'Cache-Control: max-age=0',
    'Upgrade-Insecure-Requests: 1',
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-User: ?1'
);
```

### 📤 Test :

1. **Uploadez les fichiers modifiés** :
   - `classes/AudioExtractor.php` (headers ajoutés)
   - `test_connexion.php` (diagnostic mis à jour)

2. **Re-testez avec** : `test_connexion.php`
   - Si le code HTTP passe de **403 à 200** ✅ → Problème résolu !
   - Si toujours **403** ❌ → Passez à la Solution 2

---

## 🔄 Solution 2 : Délai entre les requêtes

Audio.com pourrait bloquer si trop de requêtes arrivent rapidement.

### Modification à faire dans `AudioExtractor.php` :

Après la ligne 271 (`$html = $this->fetchPage($url);`), ajoutez :

```php
// Attendre 2-3 secondes entre chaque page pour éviter le blocage
sleep(2);
```

---

## 🍪 Solution 3 : Utiliser des cookies de session

Audio.com pourrait vérifier la présence de cookies.

### Modification à faire dans `AudioExtractor.php` :

Dans la méthode `fetchPage()`, ajoutez :

```php
// Fichier pour stocker les cookies
$cookieFile = sys_get_temp_dir() . '/audio_cookies.txt';
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
```

---

## 🌐 Solution 4 : Proxy ou IP différente

Si audio.com bloque votre adresse IP serveur, vous pouvez :

1. **Utiliser un proxy HTTP** (si votre hébergeur le propose)
2. **Tester depuis votre ordinateur en local** (pour vérifier que le code fonctionne)
3. **Changer d'hébergeur** (certains hébergeurs ont des IP blacklistées)

---

## 🔧 Solution 5 : Méthode alternative (si rien ne fonctionne)

Si audio.com bloque systématiquement votre serveur, vous pouvez :

### Option A : Exécution locale + Upload manuel

1. Exécutez la synchronisation sur votre ordinateur (en local avec PHP)
2. Exportez la base de données SQLite
3. Uploadez la base sur le serveur via FTP

### Option B : API audio.com (si disponible)

Vérifiez si audio.com propose une API publique :
- Allez sur https://audio.com/docs ou https://audio.com/api
- Utilisez leur API officielle si elle existe
- L'API serait légale et ne serait pas bloquée

### Option C : Extension navigateur + Export

1. Créez une extension Chrome qui extrait les données depuis votre navigateur
2. Exportez les données en JSON
3. Importez-les dans l'application

---

## 📋 Tests à faire dans l'ordre

### ✅ Test 1 : Headers améliorés (DÉJÀ FAIT)

Uploadez les fichiers modifiés et testez `test_connexion.php`

### ⏱️ Test 2 : Si toujours 403, ajoutez des délais

```php
// Dans extractAllTracks(), après chaque fetchPage()
sleep(rand(2, 4)); // Délai aléatoire entre 2 et 4 secondes
```

### 🍪 Test 3 : Si toujours 403, ajoutez les cookies

Voir Solution 3 ci-dessus

### 🌍 Test 4 : Test depuis votre ordinateur

Installez PHP en local (XAMPP, WAMP, MAMP) et testez si le script fonctionne sur votre machine.

Si ça fonctionne en local mais pas sur le serveur → Problème d'IP bloquée

---

## ⚖️ Note légale importante

**Le web scraping peut être contre les conditions d'utilisation d'audio.com.**

Recommandations :
1. ✅ **Vérifiez les conditions d'utilisation** d'audio.com
2. ✅ **Limitez la fréquence** de synchronisation (1 fois par jour maximum)
3. ✅ **Respectez le robots.txt** : https://audio.com/robots.txt
4. ✅ **Utilisez l'API officielle** si elle existe

---

## 🎯 Prochaine étape

**Re-testez maintenant** avec les fichiers modifiés (headers améliorés) :

```
https://votre-site.com/test_connexion.php
```

**Résultats attendus :**

✅ **Si HTTP 200** → L'application devrait fonctionner !
❌ **Si toujours HTTP 403** → Essayez les Solutions 2-5 ci-dessus

Envoyez-moi le nouveau résultat de `test_connexion.php` après avoir uploadé les fichiers modifiés !
