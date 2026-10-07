<?php

/**
 * Install / uninstall hooks for the Agent Config plugin.
 */

function plugin_agentconfig_install(): bool
{
    global $CFG_GLPI;

    Config::setConfigurationValues('plugin:agentconfig', [
        'server_url'              => $CFG_GLPI['url_base'] ?? '',
        'tag'                     => '',
        'login'                   => '',
        'password_enc'            => '',
        'asset_itemtype'          => 'Computer',
        'asset_serial'            => '',
        'asset_name'              => '',
        'auto_inventory'          => 0,
        'frequency'               => 'Day',
        'start_on_boot'           => 0,
        'oauth_enabled'           => 0,
        'oauth_client_id'         => '',
        'oauth_client_secret_enc' => '',
    ]);

    plugin_agentconfig_install_rights();

    return true;
}

function plugin_agentconfig_install_rights(): bool
{
    global $DB;

    $right = GlpiPlugin\Agentconfig\Settings::RIGHT;

    $eligible = [];
    $rows = $DB->request([
        'SELECT' => ['profiles_id', 'rights'],
        'FROM'   => ProfileRight::getTable(),
        'WHERE'  => [
            'name' => ['config', Computer::class],
        ],
    ]);
    foreach ($rows as $row) {
        if (((int) $row['rights']) & READ) {
            $eligible[(int) $row['profiles_id']] = true;
        }
    }

    if ($eligible === []) {
        return true;
    }

    $excluded = [];
    $rows = $DB->request([
        'SELECT' => ['id'],
        'FROM'   => Profile::getTable(),
        'WHERE'  => ['interface' => 'helpdesk'],
    ]);
    foreach ($rows as $row) {
        $excluded[(int) $row['id']] = true;
    }

    $existing = [];
    $rows = $DB->request([
        'SELECT' => ['profiles_id'],
        'FROM'   => ProfileRight::getTable(),
        'WHERE'  => ['name' => $right],
    ]);
    foreach ($rows as $row) {
        $existing[(int) $row['profiles_id']] = true;
    }

    foreach (array_keys($eligible) as $profiles_id) {
        if (isset($excluded[$profiles_id]) || isset($existing[$profiles_id])) {
            continue;
        }
        $DB->insert(ProfileRight::getTable(), [
            'profiles_id' => $profiles_id,
            'name'        => $right,
            'rights'      => READ,
        ]);
    }

    return true;
}

function plugin_agentconfig_uninstall(): bool
{
    global $DB;

    Config::deleteConfigurationValues('plugin:agentconfig', [
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
    ]);

    $DB->delete(ProfileRight::getTable(), [
        'name' => GlpiPlugin\Agentconfig\Settings::RIGHT,
    ]);

    return true;
}
