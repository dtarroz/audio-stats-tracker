# 🔧 Changer la version PHP sur votre hébergement

## Votre situation actuelle :
- ❌ PHP 5.4.45 (obsolète, fin de support en 2015)
- ✅ Minimum requis : PHP 5.6+
- 🎯 Recommandé : PHP 7.4 ou 8.0

---

## 🎯 Méthode 1 : Via le panneau d'hébergement (RECOMMANDÉ)

### Si vous avez **cPanel** :

1. Connectez-vous à cPanel
2. Cherchez dans la section "Software" ou "Logiciels"
3. Cliquez sur "**Select PHP Version**" ou "**MultiPHP Manager**"
4. Sélectionnez **PHP 7.4** ou **PHP 8.0** (ou la plus récente ≥ 5.6)
5. Cliquez sur "**Set as current**" ou "**Appliquer**"
6. Attendez 10-30 secondes

### Si vous avez **Plesk** :

1. Connectez-vous à Plesk
2. Allez dans "**Domaines**" → Votre domaine
3. Cliquez sur "**Paramètres PHP**"
4. Sélectionnez **PHP 7.4** ou **PHP 8.0**
5. Cliquez sur "**OK**" ou "**Appliquer**"

### Si vous avez **DirectAdmin** :

1. Connectez-vous à DirectAdmin
2. Allez dans "**PHP Version Selector**"
3. Choisissez **PHP 7.4** ou **PHP 8.0**
4. Cliquez sur "**Set**"

---

## 🎯 Méthode 2 : Via .htaccess

Si votre hébergeur le permet, vous pouvez forcer PHP via .htaccess.

### Test des syntaxes courantes :

Ajoutez **UNE SEULE** de ces lignes au début de votre `.htaccess` (essayez-les une par une) :

```apache
# Syntaxe 1 : EasyApache (cPanel)
AddHandler application/x-httpd-ea-php74 .php

# Syntaxe 2 : Alternative cPanel
AddHandler application/x-httpd-php74 .php

# Syntaxe 3 : CGI
AddType application/x-httpd-php74 .php

# Syntaxe 4 : Alt-PHP (CloudLinux)
AddHandler application/x-httpd-alt-php74 .php

# Syntaxe 5 : FastCGI
<FilesMatch \.php$>
    SetHandler php74-fcgi
</FilesMatch>
```

**Testez après chaque ajout** en accédant à `test.php`. Si erreur 500, supprimez la ligne et essayez la suivante.

---

## 🎯 Méthode 3 : Via fichier .user.ini

Créez un fichier `.user.ini` à la racine avec :

```ini
; Forcer PHP 7.4
engine = On
```

(Cette méthode fonctionne rarement, mais certains hébergeurs l'utilisent)

---

## ✅ Vérification

Après avoir changé la version :

1. Accédez à `https://votre-site.com/test.php`
   → Devrait afficher "PHP fonctionne ! Version : 7.x.x" ou "8.x.x"

2. Accédez à `https://votre-site.com/diagnostic.php`
   → Devrait afficher "✅ PHP 5.6+ OK"

3. Accédez à `https://votre-site.com/index.php`
   → L'application devrait se charger !

---

## 🆘 Si aucune méthode ne fonctionne

**Contactez le support de votre hébergeur** et demandez :

> Bonjour,
> 
> Mon site utilise actuellement PHP 5.4.45.
> J'ai besoin de passer à PHP 7.4 ou 8.0 pour faire fonctionner mon application.
> 
> Comment puis-je changer la version PHP pour le dossier `/audio/` ?
> 
> Merci

Ils vous indiqueront la procédure exacte pour votre hébergement.

---

## ⚠️ Note importante

L'application **devrait** fonctionner en PHP 5.4, mais :
- ❌ PHP 5.4 a des **failles de sécurité non patchées**
- ❌ PHP 5.4 est **2-3x plus lent** que PHP 7+
- ❌ PHP 5.4 **n'est plus maintenu** depuis 2015

**Passez IMPÉRATIVEMENT à PHP 7.4+ pour la sécurité de votre site.**

---

## 📞 Hébergeurs courants et leurs méthodes

| Hébergeur | Méthode | Documentation |
|-----------|---------|---------------|
| **OVH** | cPanel → MultiPHP | [Guide OVH](https://docs.ovh.com/fr/hosting/configurer-le-php-sur-son-hebergement-web-mutu-2014/) |
| **O2Switch** | cPanel → Select PHP | [Guide O2Switch](https://faq.o2switch.fr/hebergement-mutualise/tutoriels-cpanel/gestion-version-php) |
| **Hostinger** | hPanel → PHP Config | Panel → Configuration PHP |
| **Ionos** | Espace client → PHP | Paramètres → Version PHP |
| **LWS** | Panel LWS → PHP | Configuration → Version PHP |

---

**Uploadez `phpinfo.php` et accédez-y pour voir toutes les infos PHP de votre serveur.**
