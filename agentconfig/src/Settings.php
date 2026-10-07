<?php

namespace GlpiPlugin\Agentconfig;

use Config;
use GLPIKey;

class Settings
{
    public const CONTEXT = 'plugin:agentconfig';

    public const RIGHT = 'agentconfig::qrcode';

    public const DEEP_LINK_BASE = 'https://deeplink-agent.glpi-project.org/';

    public const KEYS = [
        'server_url',
        'tag',
        'login',
        'password_enc',
        'asset_itemtype',
        'asset_serial',
        'asset_name',
        'auto_inventory',
        'frequency',
        'start_on_boot',
        'oauth_enabled',
        'oauth_client_id',
        'oauth_client_secret_enc',
    ];

    public static function all(): array
    {
        $values = Config::getConfigurationValues(self::CONTEXT);

        foreach (self::KEYS as $key) {
            if (!isset($values[$key])) {
                $values[$key] = '';
            }
        }

        return $values;
    }

    public static function save(array $values): void
    {
        $to_save = [];

        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $to_save[$key] = $values[$key];
            }
        }

        if ($to_save !== []) {
            Config::setConfigurationValues(self::CONTEXT, $to_save);
        }
    }

    public static function saveFromPost(array $input): void
    {
        $password = trim((string) ($input['password'] ?? ''));
        $secret   = trim((string) ($input['oauth_client_secret'] ?? ''));

        $to_save = [
            'server_url'     => trim((string) ($input['server_url'] ?? '')),
            'tag'            => trim((string) ($input['tag'] ?? '')),
            'login'          => trim((string) ($input['login'] ?? '')),
            'asset_itemtype' => (string) ($input['asset_itemtype'] ?? '') === 'Phone' ? 'Phone' : 'Computer',
            'asset_serial'   => trim((string) ($input['asset_serial'] ?? '')),
            'asset_name'     => trim((string) ($input['asset_name'] ?? '')),
            'auto_inventory' => !empty($input['auto_inventory']) ? 1 : 0,
            'frequency'      => in_array($input['frequency'] ?? '', ['Day', 'Week', 'Month'], true)
                ? $input['frequency']
                : 'Day',
            'start_on_boot'  => !empty($input['start_on_boot']) ? 1 : 0,
            'oauth_enabled'  => !empty($input['oauth_enabled']) ? 1 : 0,
            'oauth_client_id' => trim((string) ($input['oauth_client_id'] ?? '')),
        ];

        if ($password !== '') {
            $to_save['password_enc'] = (new GLPIKey())->encrypt($password);
        }

        if ($secret !== '') {
            $to_save['oauth_client_secret_enc'] = (new GLPIKey())->encrypt($secret);
        }

        self::save($to_save);
    }

    public static function decryptValue(string $encrypted): string
    {
        if ($encrypted === '') {
            return '';
        }

        try {
            $value = (new GLPIKey())->decrypt($encrypted);
        } catch (\Throwable $e) {
            return '';
        }

        return is_string($value) ? $value : '';
    }

    /**
     * Build the JSON payload expected by the GLPI Android Inventory agent.
     *
     * @param array $overrides Values forced for a specific equipment
     */
    public static function payload(array $overrides = []): array
    {
        $settings = self::all();

        $url = (string) ($overrides['URL'] ?? '');
        if ($url === '') {
            $url = (string) $settings['server_url'];
        }
        if ($url === '') {
            $url = (string) ($GLOBALS['CFG_GLPI']['url_base'] ?? '');
        }

        $itemtype = (string) ($overrides['ASSET_ITEMTYPE'] ?? $settings['asset_itemtype']);
        if ($itemtype !== 'Phone') {
            $itemtype = 'Computer';
        }

        $payload = [
            'URL'            => $url,
            'TAG'            => (string) ($overrides['TAG'] ?? $settings['tag']),
            'LOGIN'          => (string) ($overrides['LOGIN'] ?? $settings['login']),
            'PASSWORD'       => (string) ($overrides['PASSWORD'] ?? self::decryptValue((string) $settings['password_enc'])),
            'ASSET_ITEMTYPE' => $itemtype,
        ];

        $serial = (string) ($overrides['ASSET_SERIAL'] ?? $settings['asset_serial']);
        $name   = (string) ($overrides['ASSET_NAME'] ?? $settings['asset_name']);

        if ($serial !== '') {
            $payload['ASSET_SERIAL'] = $serial;
        }
        if ($name !== '') {
            $payload['ASSET_NAME'] = $name;
        }

        if (!empty($settings['oauth_enabled'])
            && (string) $settings['oauth_client_id'] !== ''
            && (string) $settings['oauth_client_secret_enc'] !== ''
        ) {
            $payload['CLIENT_ID']     = (string) $settings['oauth_client_id'];
            $payload['CLIENT_SECRET'] = self::decryptValue((string) $settings['oauth_client_secret_enc']);
        }

        if (!empty($settings['auto_inventory'])) {
            $payload['ANDROID_AUTOMATIC_INVENTORY'] = '1';
            $payload['ANDROID_FREQUENCY'] = in_array($settings['frequency'], ['Day', 'Week', 'Month'], true)
                ? (string) $settings['frequency']
                : 'Day';
            $payload['ANDROID_START_ON_BOOT'] = !empty($settings['start_on_boot']) ? '1' : '0';
        }

        return $payload;
    }

    /**
     * Raw base64(JSON) string: this is exactly what must be encoded in the QR code.
     */
    public static function encodedPayload(array $overrides = []): string
    {
        return base64_encode(json_encode(self::payload($overrides)));
    }

    public static function deepLink(array $overrides = []): string
    {
        return self::DEEP_LINK_BASE . '?data=' . rawurlencode(self::encodedPayload($overrides));
    }
}
