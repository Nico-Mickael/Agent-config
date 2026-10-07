<?php

namespace GlpiPlugin\Agentconfig;

use Config;
use GLPIKey;
use OAuthClient;
use Session;

class Actions
{
    /**
     * Create (or reuse) a dedicated OAuth client with the `inventory` scope.
     */
    public static function createOAuthClient(): void
    {
        global $DB;

        $client = new OAuthClient();

        $existing = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => OAuthClient::getTable(),
            'WHERE'  => ['name' => 'Agent Config (inventory)'],
        ]);
        foreach ($existing as $row) {
            if ($client->getFromDB((int) $row['id'])) {
                self::storeOAuthClient($client);
                Session::addMessageAfterRedirect(__('Existing OAuth client reused for Agent Config.'));

                return;
            }
        }

        $newid = $client->add([
            'name'            => 'Agent Config (inventory)',
            'comment'         => 'OAuth client created by the Agent Config plugin (inventory scope).',
            'grants'          => ['authorization_code', 'client_credentials'],
            'scopes'          => ['inventory'],
            'is_active'       => 1,
            'is_confidential' => 1,
            'redirect_uri'    => ['/api.php/oauth2/redirection'],
        ]);

        if ($newid) {
            $client->getFromDB($newid);
            self::storeOAuthClient($client);
            Session::addMessageAfterRedirect(__('OAuth client created with the "inventory" scope.'));
        } else {
            Session::addMessageAfterRedirect(__('Unable to create the OAuth client.'), false, ERROR);
        }
    }

    public static function storeOAuthClient(OAuthClient $client): void
    {
        $key = new GLPIKey();

        $encrypted_secret = (string) ($client->fields['secret'] ?? '');
        $secret = '';
        if ($encrypted_secret !== '') {
            try {
                $decrypted = $key->decrypt($encrypted_secret);
                $secret = is_string($decrypted) ? $decrypted : '';
            } catch (\Throwable $e) {
                $secret = '';
            }
        }

        Settings::save([
            'oauth_enabled'           => 1,
            'oauth_client_id'         => (string) ($client->fields['identifier'] ?? ''),
            'oauth_client_secret_enc' => $secret !== '' ? $key->encrypt($secret) : '',
        ]);
    }

    /**
     * Enable/disable OAuth client_credentials authentication for the inventory endpoint.
     */
    public static function setInventoryAuth(bool $enabled): void
    {
        Config::setConfigurationValues('inventory', [
            'auth_required' => $enabled ? \Glpi\Inventory\Conf::CLIENT_CREDENTIALS : 'none',
        ]);

        if ($enabled) {
            Session::addMessageAfterRedirect(
                'Inventory authentication set to OAuth (client credentials). Agents not using OAuth will be rejected.'
            );
        } else {
            Session::addMessageAfterRedirect('Inventory authentication restored to "none".');
        }
    }
}
