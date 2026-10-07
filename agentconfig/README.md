# Agent Config (GLPI 11 plugin)

Préconfigure l'agent **GLPI Android Inventory** via QR Code ou deep link :
URL du serveur, identifiants, TAG, type d'équipement, client OAuth dédié
(portée `inventory`), inventaire automatique paramétré.

Compatible **GLPI 11.0.x** (testé sur 11.0.11), PHP 8.1+.

## Format des données

Le payload envoyé à l'agent est exactement :

```json
{
  "URL": "https://glpi.example.com",
  "TAG": "...",
  "LOGIN": "...",
  "PASSWORD": "...",
  "ASSET_ITEMTYPE": "Computer",
  "ASSET_SERIAL": "...",            // optionnel
  "ASSET_NAME": "...",              // optionnel
  "CLIENT_ID": "...",               // optionnel (OAuth)
  "CLIENT_SECRET": "...",           // optionnel (OAuth)
  "ANDROID_AUTOMATIC_INVENTORY": "1",   // optionnel
  "ANDROID_FREQUENCY": "Day|Week|Month", // optionnel
  "ANDROID_START_ON_BOOT": "1"          // optionnel
}
```

* **QR Code** : contenu = `base64(json)` brut (pas une URL).
* **Deep link** : `https://deeplink-agent.glpi-project.org/?data=<base64(json)>`
  (seul host App Links déclaré dans le manifest de l'agent).

## Installation

GLPI 11 n'a **pas** de dépôt ZIP local (la marketplace ne fait que du
téléchargement réseau) : le plugin se déploie en copiant le dossier.

```bash
# sur le serveur GLPI (les entrées du ZIP utilisent des '/')
unzip agentconfig-1.0.0.zip -d /var/www/glpi/plugins/
chown -R www-data:www-data /var/www/glpi/plugins/agentconfig
```

Puis, dans l'interface GLPI : **Configuration > Plugins > Agent Config >
Installer** puis **Activer** — ou en console :

```bash
php bin/console plugin:install agentconfig
php bin/console plugin:activate agentconfig
```

Ensuite, ouvrir **Plugins > Agent Config** (menu « Plugins » de la barre
latérale).

Au chargement, le plugin crée :

* les réglages par défaut (contexte `plugin:agentconfig` de `glpi_configs`) ;
* le droit dédié `agentconfig::qrcode` sur les profils ayant droit `config`
  (profils « helpdesk » exclus).

## Utilisation

1. Renseigner l'URL GLPI, le TAG, les identifiants, le type d'équipement.
2. *Enregistrer* : le QR Code et le deep link sont générés à droite.
3. Onglet **QR code** sur un Ordinateur / Téléphone : QR pré-rempli avec le
   numéro de série et le nom de l'équipement, téléchargement PNG, deep link.
4. Bouton **Créer / réutiliser le client OAuth** : crée automatiquement un
   client OAuth (`authorization_code` + `client_credentials`, portée
   `inventory`) et stocke l'identifiant/le secret (chiffrés GLPIKey).
5. Bouton **Exiger OAuth pour l'inventaire** : passe
   `inventory.auth_required = client_credentials`.
   ⚠️ **Casse les agents non-OAuth** — à activer seulement si tous les agents
   utilisent OAuth.

## Notes de sécurité

* Mot de passe et secret OAuth chiffrés via `GLPIKey` dans `glpi_configs`.
* Les pages QR exigent le droit `agentconfig::qrcode`, la page de
  configuration le droit `config`.
* Le QR Code contient des identifiants : à imprimer/afficher avec
  précaution (équipements en stock, atelier, etc.).

## Désinstallation

`plugin:uninstall agentconfig` supprime les réglages du plugin et son droit,
mais **conserve** le client OAuth créé dans `glpi_oauthclients` et
`inventory.auth_required` (réglages cœur GLPI, à remettre à la main si besoin).

## Développement

```bash
# dépendances (endroid/qr-code)
docker run --rm -v "$PWD:/app" -w /app composer:2 install

# tests d'installation
php bin/console plugin:uninstall agentconfig
php bin/console plugin:install agentconfig
php bin/console plugin:activate agentconfig
```

Gabarits : `templates/` (namespace Twig `@agentconfig`).
Classes : `src/` (PSR-4 `GlpiPlugin\Agentconfig\`, autoloadé par GLPI).
