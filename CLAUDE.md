# CLAUDE.md

Règles pour le développement avec Claude Code

## Gestion de version

- **Seulement les modifications sur un fichier `.js` ou `.css` doit être accompagnée d'une incrémentation du numéro de version dans `config/config.php` (`APP_VERSION`).**
- **Ajout de fonctionnalité → incrémentation du numéro majeur** (ex: `1.3.0` → `1.4.0`)
- **Correction de bug → incrémentation du numéro mineur** (ex: `1.3.0` → `1.3.1`)

Le fichier de configuration se trouve dans `config/config.php`, ligne : `define('APP_VERSION', 'X.Y.Z');`

## Git

- **Ne jamais faire de commit git sans autorisation explicite de l'utilisateur.**
