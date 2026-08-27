# Audio Stats Tracker

Application web PHP pour suivre l'évolution du nombre d'écoutes de ces musiques sur Audio.com.

## 🎯 Fonctionnalités

- 📊 Historique complet des écoutes
- 📈 Graphiques d'évolution interactifs (Chart.js)
- 🏆 Top 5 des musiques les plus écoutées
- 📈 Top 5 des progressions (15 jours)
- 🔍 Recherche et tri des musiques
- ✅ Filtre des écoutes du jour
- 📊 Suivi des écoutes quotidiennes
- 📱 Interface responsive (ordinateur, tablette, mobile)
- 🗄️ Base de données SQLite
- 🔄 Système de migrations automatique
- 📝 Journalisation complète
- 🔌 API REST pour synchronisation externe

## 📋 Prérequis

- **PHP 5.4.45 ou supérieur** (testé sur PHP 5.4.45)
- Extensions PHP requises :
  - PDO
  - PDO_SQLITE
  - cURL
  - DOM
  - libxml
- Serveur web (Apache, Nginx, etc.) ou hébergement mutualisé

## 🚀 Installation

### Installation en local

1. Clonez ou téléchargez le projet
2. Placez les fichiers dans votre répertoire web (ex: `/var/www/html/audio-stats`)
3. Ouvrez votre navigateur sur `http://localhost/audio-stats`
4. L'application initialise automatiquement la base de données et les migrations

### Installation sur hébergement mutualisé (FTP)

1. Téléchargez tous les fichiers via FTP dans le répertoire de votre choix
2. Assurez-vous que les dossiers suivants sont accessibles en écriture :
   - `/database/`
   - `/logs/`
3. Accédez à l'URL de votre application dans votre navigateur
4. La base de données et les migrations se créent automatiquement

## ⚙️ Configuration

Éditez le fichier `config/config.php` pour personnaliser :

```php
// Profil Audio.com à suivre
define('AUDIO_PROFILE', 'dtarroz');

// User-Agent pour les requêtes cURL
define('CURL_USER_AGENT', '...');

// Timeout des requêtes
define('CURL_TIMEOUT', 30);

// Format de date
define('DATE_FORMAT', 'Y-m-d H:i:s');
```

## 📁 Structure du projet

```
/
├── index.php                  # Point d'entrée principal
├── .htaccess                  # Protection et configuration Apache
├── config/
│   └── config.php            # Configuration de l'application
├── classes/                   # Classes PHP (MVC léger)
│   ├── Database.php          # Gestion de la base de données
│   ├── Migration.php         # Système de migrations
│   ├── AudioExtractor.php    # Extraction des données Audio.com
│   ├── Track.php             # Modèle Track
│   ├── TrackRepository.php   # Repository des musiques
│   ├── Statistics.php        # Calculs statistiques
│   ├── SettingsRepository.php # Paramètres
│   └── Logger.php            # Journalisation
├── actions/                   # Actions AJAX
│   ├── sync.php              # Synchronisation (cachée)
│   ├── api_sync.php          # API REST pour sync externe
│   ├── search.php            # Recherche/tri
│   ├── history.php           # Historique
│   └── top_progressions.php  # Top progressions
├── views/                     # Vues
│   ├── header.php
│   ├── footer.php
│   ├── dashboard.php
│   └── track_list.php
├── assets/
│   ├── css/
│   │   └── style.css         # Styles responsive
│   └── js/
│       └── app.js            # JavaScript
├── migrations/
│   └── 001_initial.sql       # Migration initiale
├── database/
│   └── database.sqlite       # Base SQLite (créée auto)
└── logs/
    └── application.log       # Logs (créé auto)
```

## 🎵 Utilisation

### Synchronisation via l'API

L'application utilise une **API REST** pour synchroniser les données. La synchronisation manuelle via l'interface est désactivée pour éviter les blocages HTTP 403 d'Audio.com.

#### Méthode recommandée : Script externe

**Prérequis** : Un script externe (extension navigateur, bookmarklet, ou script local) qui envoie le HTML de la page Audio.com à l'API.

**Endpoint API** : `POST /actions/api_sync.php`

**Format de la requête** :
```javascript
// Depuis JavaScript (extension navigateur, bookmarklet)
fetch('https://votre-domaine.com/audio/actions/api_sync.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        html: document.documentElement.outerHTML  // HTML de la page audio.com
    })
})
.then(response => response.json())
.then(data => {
    console.log('Synchronisation:', data.message);
    console.log('Pistes trouvées:', data.stats.tracks_found);
    console.log('Pistes ajoutées:', data.stats.tracks_added);
    console.log('Pistes mises à jour:', data.stats.tracks_updated);
});
```

**Exemple avec cURL** :
```bash
# Récupérer le HTML d'Audio.com
html=$(curl "https://audio.com/dtarroz")

# L'envoyer à l'API
curl -X POST https://votre-domaine.com/audio/actions/api_sync.php \
  -H "Content-Type: application/json" \
  -d "{\"html\":\"$(echo $html | sed 's/"/\\"/g')\"}"
```

**Réponse en cas de succès** :
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

**Réponse en cas d'erreur** :
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

#### Bookmarklet de synchronisation

Créez un bookmarklet dans votre navigateur :

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

**Instructions** :
1. Sur audio.com/dtarroz, cliquez sur le bookmarklet
2. L'API synchronise automatiquement
3. Un message de confirmation s'affiche

📖 **Documentation complète** : Voir [BOOKMARKLET.md](BOOKMARKLET.md) pour :
- Instructions détaillées d'installation
- Personnalisation (console.log, notifications, etc.)
- Création d'une extension Chrome/Firefox
- Script mobile (iOS/Android)

### Première synchronisation

Utilisez l'un des moyens ci-dessus (API, bookmarklet) pour effectuer votre première synchronisation.

### Consultation des données

1. Accédez au **Tableau de bord** pour voir :
   - Nombre total de musiques
   - Nombre d'écoutes totales
   - Top 5 des musiques les plus écoutées
   - Top 5 des progressions (filtrable par période)

2. Consultez la **Liste des musiques** pour :
   - Voir toutes vos pistes avec leurs graphiques
   - Rechercher par titre
   - Trier par différents critères

### Recherche et tri

- **Recherche** : Saisissez un mot-clé dans la barre de recherche
- **Filtre écoutes du jour** : Cochez la case pour n'afficher que les musiques ayant eu des écoutes lors de la dernière journée
- **Tri disponible** :
  - Ordre Audio.com (récent en premier)
  - Titre A → Z / Z → A
  - Nombre d'écoutes (décroissant)
  - Progression (décroissant) - **par défaut**

### Top 5 et progressions

Le tableau de bord affiche côte à côte :

1. **Top 5 des écoutes** : Les 5 musiques avec le plus d'écoutes actuelles
2. **Top 5 des progressions (15 jours)** : Les 5 musiques avec la plus forte progression sur les 15 derniers jours
   - Affiche le nombre d'écoutes gagnées (+X)

### Écoutes du jour

- La statistique "**Écoutes du jour**" affiche le nombre total d'écoutes gagnées depuis la dernière synchronisation
- Calculé en faisant la différence entre les valeurs de la dernière et l'avant-dernière capture
- Permet de suivre l'activité récente de vos musiques

### Graphiques

- Chaque musique possède son propre graphique d'évolution
- Filtres de période : 7, **15 (par défaut)**, 30, 60, 90 jours ou tout l'historique
- Survol pour voir les détails (date et nombre d'écoutes)
- Les graphiques sont générés à la demande pour optimiser les performances

### Calcul de la progression

La progression est calculée ainsi :

```
Progression = ((Valeur actuelle - Première valeur de la période) / Première valeur) × 100
```

**Exemple** :
- Une musique avait **8 écoutes** il y a 30 jours
- Elle a maintenant **9 écoutes**
- Progression = ((9 - 8) / 8) × 100 = **+12.5%**

**Cas particuliers** :
- Si la première valeur est **0** : la progression affiche **N/A**
- Si la valeur actuelle est égale à la première : progression de **0%**
- Seules les musiques avec une progression positive apparaissent dans le Top progressions

**Note technique** : Les progressions sont calculées avec les valeurs chronologiques (première et dernière de la période), pas avec MIN/MAX pour garantir l'exactitude.

## 🔧 Maintenance

### Ajouter une migration

1. Créez un fichier dans `/migrations/` avec le format : `002_nom_migration.sql`
2. Numérotez séquentiellement (002, 003, etc.)
3. La migration s'appliquera automatiquement au prochain chargement

Exemple `002_add_index.sql` :

```sql
CREATE INDEX idx_track_history_date ON track_history(DATE(captured_at));
```

### Consulter les logs

Les logs sont dans `/logs/application.log` :

```
[2026-07-26 10:30:15] [INFO] Application démarrée
[2026-07-26 10:30:20] [INFO] Début de la synchronisation
```

### Sauvegarde

Pour sauvegarder vos données :

1. Téléchargez le fichier `/database/database.sqlite`
2. Conservez également `/logs/application.log` si nécessaire

## 🎨 Personnalisation

### Modifier les couleurs

Éditez `/assets/css/style.css` :

```css
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Modifier le nombre de musiques affichées

Dans `/config/config.php` :

```php
define('TRACKS_PER_PAGE', 50);
```

### Mettre à jour les assets (CSS/JS) et forcer le rechargement

Les fichiers CSS et JS utilisent un système de versioning pour éviter les problèmes de cache :

```html
<link rel="stylesheet" href="assets/css/style.css?v=1.0.1">
<script src="assets/js/app.js?v=1.0.1"></script>
```

**Après modification du CSS ou JS** :
1. Ouvrez `/config/config.php`
2. Incrémentez `APP_VERSION` :
   ```php
   define('APP_VERSION', '1.0.2'); // était 1.0.1
   ```
3. Les navigateurs téléchargeront automatiquement la nouvelle version

## 📄 Licence

Ce projet est libre d'utilisation pour un usage personnel ou commercial.

## 📞 Support

En cas de problème :

1. Consultez les logs dans `/logs/application.log`
2. Vérifiez la configuration dans `config/config.php`
3. Assurez-vous que toutes les extensions PHP sont activées

## 🎉 Crédits

- **Chart.js** : https://www.chartjs.org/
- **Audio.com** : Source des données

## 📋 Changelog

### Version 1.2.0 (2026-08-22)

**Nouvelles fonctionnalités :**
- ✅ Ajout d'un filtre "**Écoutes du jour uniquement**" dans la zone de recherche
  - Permet de filtrer les musiques qui ont eu des écoutes entre la dernière et l'avant-dernière journée de synchronisation
  - Case à cocher facilement accessible pour un filtrage rapide

**Améliorations :**
- 📊 La carte "Supprimées" a été remplacée par "**Écoutes du jour**"
  - Affiche désormais le nombre total d'écoutes gagnées sur la dernière journée
  - Calcul basé sur la différence entre la dernière journée de sync et la journée précédente
- 📈 Le Top 5 des progressions est maintenant **fixé à 15 jours**
  - Suppression des onglets 7/30/90 jours pour simplifier l'interface
  - Le titre indique clairement "Top 5 des progressions (15 jours)"
- 📊 Les graphiques des musiques affichent par défaut **15 jours** au lieu de 30
  - Période plus pertinente pour suivre les tendances récentes
  - Les autres périodes (7, 30, 60, 90 jours, tout) restent disponibles

**Corrections de bugs :**
- 🐛 Correction du calcul des "Écoutes du jour"
  - Compare maintenant la dernière **journée** de synchronisation avec la **journée précédente**
  - Auparavant comparait la toute dernière sync avec l'avant-dernière sync (même jour)
  - Résultat : affichage correct du nombre d'écoutes quotidiennes
- 🐛 Correction du filtre "Écoutes du jour uniquement"
  - Utilise la même logique que la carte (comparaison jour par jour)
  - Affiche maintenant toutes les musiques avec des écoutes sur la journée
- 🐛 Correction de la cohérence entre progression et graphiques
  - La progression affichée dans les cards correspond désormais exactement aux données du graphique
  - Utilise les **N derniers jours de synchronisation** au lieu d'une période calendaire
  - Garantit que la valeur de départ de la progression = première valeur visible sur le graphique
- 🐛 Correction de la checkbox Firefox
  - La case "Écoutes du jour uniquement" se décoche automatiquement au rechargement (F5)
  - Fix pour Firefox qui conservait l'état coché sans appliquer le filtre

### Version 1.1.0 (Août 2026)

**Améliorations** :
- ✅ Affichage de la progression en nombre d'écoutes au lieu du pourcentage
  - Exemple : "+10" au lieu de "+12.5%"
  - Plus intuitif et direct à comprendre
  - Affiche "+0" pour les musiques sans nouvelles écoutes
- ✅ Tri des progressions amélioré
  - Inclut désormais toutes les musiques (même celles avec 0)

### Version 1.0.1 (Août 2026)

**Nouvelles fonctionnalités** :
- ✅ API REST pour synchronisation externe (`/actions/api_sync.php`)
- ✅ Top 5 des écoutes et progressions côte à côte
- ✅ Système de versioning des assets (cache busting)

**Améliorations** :
- ✅ Calcul de progression corrigé (chronologique au lieu de MIN/MAX)
- ✅ Gestion des valeurs NULL dans les requêtes (COALESCE)
- ✅ Détection et élimination des doublons
- ✅ Protection contre le cache Firefox (valeur par défaut forcée)
- ✅ Extraction améliorée (limite pattern étendue à 5000 chars)
- ✅ Logs détaillés à chaque étape
- ✅ Gestion d'erreur renforcée avec détails

**Corrections** :
- 🐛 Pistes avec 0 écoutes invisibles → historique créé même pour 0
- 🐛 Ordre incorrect → tri par audio_id au lieu de id
- 🐛 Doublons dans la liste → unset($track) après foreach par référence
- 🐛 Top progressions vide → sous-requêtes chronologiques
- 🐛 Erreur 500 avec Statistics.php → accolade en trop supprimée
- 🐛 API ne retourne rien → timeout et memory_limit augmentés

### Version 1.0.0 (Juillet 2026)

- 🎉 Version initiale

---

## 📚 Documentation complète

Pour aller plus loin, consultez :

- **[BOOKMARKLET.md](BOOKMARKLET.md)** : Instructions détaillées pour le bookmarklet de synchronisation
  - Installation et personnalisation
  - Extension navigateur Chrome/Firefox
  - Scripts mobile iOS/Android
  - Dépannage et sécurité

- **[API.md](API.md)** : Documentation complète de l'API REST
  - Endpoints et formats de requête/réponse
  - Exemples en JavaScript, Python, Node.js, PHP, cURL
  - Configuration serveur (CORS, rate limiting)
  - Debugging et monitoring
  - Automatisation (cron, GitHub Actions)

- **[ARCHITECTURE.md](ARCHITECTURE.md)** : Architecture technique détaillée
  - Structure MVC et flux de données
  - Schéma de base de données
  - Patterns de code et optimisations
  - Corrections de bugs importantes
  - Meilleures pratiques apprises
