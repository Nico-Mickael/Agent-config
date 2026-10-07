<?php

/**
 * Agent Config plugin: configuration page.
 */

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Agentconfig\Actions;
use GlpiPlugin\Agentconfig\Menu;
use GlpiPlugin\Agentconfig\Settings;

global $CFG_GLPI;

Session::checkRight('config', READ);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        Session::checkRight('config', UPDATE);
        Settings::saveFromPost($_POST);
        Session::addMessageAfterRedirect(__('Configuration saved.'));
    } elseif (isset($_POST['create_oauth'])) {
        Session::checkRight('config', UPDATE);
        Actions::createOAuthClient();
    } elseif (isset($_POST['toggle_inventory_auth'])) {
        Session::checkRight('config', UPDATE);
        Actions::setInventoryAuth(!empty($_POST['inventory_oauth']));
    }

    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/agentconfig/front/config.form.php');
}

$settings = Settings::all();

$qr_url  = $CFG_GLPI['root_doc'] . '/plugins/agentconfig/front/qrcode.php';
$dl_url  = $qr_url . '?download=1';

$inventory_auth = Config::getConfigurationValues('inventory', ['auth_required'])['auth_required'] ?? 'none';

Html::header(
    __('Agent Config'),
    $CFG_GLPI['root_doc'] . '/front/setup.php',
    'plugins',
    Menu::class
);

TemplateRenderer::getInstance()->display('@agentconfig/config.html.twig', [
    'settings'       => $settings,
    'qr_url'         => $qr_url,
    'dl_url'         => $dl_url,
    'payload'        => Settings::encodedPayload(),
    'deep_link'      => Settings::deepLink(),
    'inventory_auth' => $inventory_auth,
]);

Html::footer();
