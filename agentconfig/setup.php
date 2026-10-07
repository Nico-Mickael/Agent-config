<?php

/**
 * ---------------------------------------------------------------------
 * Agent Config plugin for GLPI
 * Preconfigure the GLPI Android Inventory agent (QR code / deep link).
 * ---------------------------------------------------------------------
 */

define('PLUGIN_AGENTCONFIG_VERSION', '1.0.0');

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

function plugin_init_agentconfig(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Glpi\Plugin\Hooks::CONFIG_PAGE]['agentconfig'] = 'front/config.form.php';

    $PLUGIN_HOOKS[Glpi\Plugin\Hooks::MENU_TOADD]['agentconfig'] = [
        'plugins' => [GlpiPlugin\Agentconfig\Menu::class],
    ];

    Plugin::registerClass(
        GlpiPlugin\Agentconfig\EquipmentTab::class,
        ['addtabon' => [Computer::class, Phone::class]]
    );
}

function plugin_version_agentconfig(): array
{
    return [
        'name'        => 'Agent Config',
        'version'     => PLUGIN_AGENTCONFIG_VERSION,
        'author'      => 'Agent Config contributors',
        'license'     => 'GPL-3.0-or-later',
        'homepage'    => 'https://github.com/glpi-project',
        'requirements' => [
            'glpi' => [
                'min' => '11.0.0',
                'max' => '11.0.99',
            ],
            'php' => [
                'min' => '8.1',
            ],
        ],
    ];
}

function plugin_check_prerequisites_agentconfig(): bool
{
    if (version_compare(GLPI_VERSION, '11.0.0', '<') || version_compare(GLPI_VERSION, '11.0.99', '>')) {
        echo 'This plugin requires GLPI >= 11.0.0 and < 11.1.0';
        return false;
    }

    return true;
}

function plugin_check_config_agentconfig(): bool
{
    return true;
}
