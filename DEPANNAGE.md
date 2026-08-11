# 🔧 Guide de dépannage - Erreur 500

## Étapes à suivre dans l'ordre :

### 1️⃣ Test basique PHP (PRIORITÉ)

**Action :** Accédez à `https://votre-site.com/test.php`

**Résultats possibles :**
- ✅ Affiche "PHP fonctionne ! Version : X.X.X" → PHP OK, passez à l'étape 2
- ❌ Erreur 500 → Le problème vient du .htaccess, passez à l'étape 2a

---

### 2a️⃣ Si test.php donne erreur 500 : Problème .htaccess

**Action :** Sur votre serveur FTP :
1. Renommez `.htaccess` en `.htaccess.old` (pour le désactiver)
2. Uploadez `.htaccess.minimal` et renommez-le en `.htaccess`
3. Testez à nouveau `test.php`

**Si ça fonctionne maintenant :**
- Le problème venait du .htaccess original
- Gardez `.htaccess.minimal` ou utilisez `.htaccess.compatible`

---

### 2️⃣ Diagnostic complet

**Action :** Accédez à `https://votre-site.com/diagnostic.php`

**Ce fichier va vérifier :**
- ✅ Version PHP (minimum 5.6 requis)
- ✅ Extensions PHP (PDO, SQLite, cURL, DOM)
- ✅ Permissions des dossiers
- ✅ Existence des fichiers
- ✅ Capacité d'écriture dans /database/

**Notez tous les ❌ affichés**

---

### 3️⃣ Problèmes courants et solutions

#### Problème A : "Extension pdo_sqlite manquante"
**Solution :** Contactez votre hébergeur pour activer l'extension SQLite (très courant sur hébergements mutualisés)

#### Problème B : "Dossier database pas accessible en écriture"
**Solution via FTP :**
1. Clic droit sur le dossier `/database/`
2. Propriétés/Permissions
3. Mettre **755** (rwxr-xr-x) ou **777** (rwxrwxrwx) si 755 ne fonctionne pas

#### Problème C : "PHP version < 5.6"
**Solution :** 
- Dans votre panneau d'hébergement (cPanel, Plesk, etc.)
- Cherchez "Version PHP" ou "PHP Settings"
- Sélectionnez PHP 7.4 ou 8.0+

#### Problème D : "config.php erreur"
**Vérifiez que le fichier `/config/config.php` existe bien**
**Vérifiez qu'il n'y a pas d'erreur de syntaxe**

---

### 4️⃣ Vérifier les logs d'erreur

**Méthode 1 : Via FTP**
Cherchez un fichier nommé :
- `error_log` (à la racine)
- `php_error.log`
- Dans le dossier `/logs/`

**Méthode 2 : Via cPanel**
- Section "Statistiques" ou "Metrics"
- "Erreurs" ou "Error Log"

**Envoyez-moi le contenu des dernières erreurs**

---

### 5️⃣ Test index.php

**Action :** Accédez à `https://votre-site.com/index.php`

**Si erreur 500 :**
- Consultez `diagnostic.php` d'abord
- Vérifiez que le dossier `/database/` existe et est accessible en écriture
- Vérifiez que tous les fichiers sont bien uploadés (31 fichiers au total)

---

## 📋 Checklist des fichiers à uploader

```
✅ .htaccess (ou .htaccess.minimal si problème)
✅ index.php
✅ check_install.php
✅ diagnostic.php (nouveau)
✅ test.php (nouveau)

✅ /config/config.php

✅ /classes/ (8 fichiers .php)
   - Database.php
   - Migration.php
   - Logger.php
   - AudioExtractor.php
   - Track.php
   - TrackRepository.php
   - Statistics.php
   - SettingsRepository.php

✅ /actions/ (4 fichiers .php)
   - sync.php
   - search.php
   - history.php
   - top_progressions.php

✅ /views/ (4 fichiers .php)
   - track_list.php
   - header.php
   - footer.php
   - sync_progress.php

✅ /migrations/001_initial.sql

✅ /assets/css/style.css
✅ /assets/js/app.js

✅ /database/ (dossier vide avec permissions 755)
✅ /logs/ (dossier vide avec permissions 755)
```

**NE PAS uploader :**
- ❌ example.txt (752 Ko, inutile)
- ❌ test_*.php (fichiers de test)
- ❌ *.py (scripts Python)
- ❌ README.md (optionnel)

---

## 🆘 Besoin d'aide ?

**Envoyez-moi :**
1. Le résultat de `test.php`
2. Le résultat complet de `diagnostic.php` (capture d'écran)
3. Les dernières lignes du fichier `error_log` si disponible
4. Le nom de votre hébergeur (OVH, O2Switch, Hostinger, etc.)
