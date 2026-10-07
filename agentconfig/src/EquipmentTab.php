<?php

namespace GlpiPlugin\Agentconfig;

use CommonDBTM;
use CommonGLPI;
use Computer;
use Phone;
use Session;

class EquipmentTab extends CommonDBTM
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!self::canViewQR($item)) {
            return false;
        }

        return __('QR code');
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!self::canViewQR($item)) {
            return false;
        }

        $root = $GLOBALS['CFG_GLPI']['root_doc'];

        $query = http_build_query([
            'itemtype' => $item::class,
            'id'       => (int) $item->fields['id'],
        ]);

        $qr_url  = $root . '/plugins/agentconfig/front/qrcode.php?' . $query;
        $dl_url  = $root . '/plugins/agentconfig/front/qrcode.php?' . $query . '&download=1';

        $overrides = [
            'ASSET_ITEMTYPE' => $item::class,
            'ASSET_SERIAL'   => (string) ($item->fields['serial'] ?? ''),
            'ASSET_NAME'     => (string) ($item->fields['name'] ?? ''),
        ];

        $payload   = Settings::encodedPayload($overrides);
        $deep_link = Settings::deepLink($overrides);

        echo '<div class="text-center agentconfig-qr-tab">';
        echo '<img src="' . htmlescape($qr_url) . '" alt="' . htmlescape(__('QR code')) . '"'
            . ' style="width:260px;height:260px;" class="img-thumbnail">';

        echo '<div class="mt-2">';
        echo '<a class="btn btn-sm btn-outline-primary" href="' . htmlescape($dl_url) . '">';
        echo '<i class="ti ti-download"></i> ' . htmlescape(__('Download PNG')) . '</a> ';
        echo '<button type="button" class="btn btn-sm btn-outline-secondary" data-clipboard-text="'
            . htmlescape($deep_link) . '">';
        echo '<i class="ti ti-link"></i> ' . htmlescape(__('Copy deep link')) . '</button>';
        echo '</div>';

        echo '<div class="mt-2"><label class="form-label">' . htmlescape(__('Configuration payload (base64)'))
            . '</label>';
        echo '<textarea class="form-control" rows="3" readonly>' . htmlescape($payload) . '</textarea>';
        echo '</div>';

        echo '<div class="mt-2 text-break"><small><a href="' . htmlescape($deep_link) . '" target="_blank" rel="noopener">'
            . htmlescape($deep_link) . '</a></small></div>';

        echo '</div>';

        return true;
    }

    private static function canViewQR(CommonGLPI $item): bool
    {
        if (!($item instanceof Computer) && !($item instanceof Phone)) {
            return false;
        }

        if (!Session::haveRight(Settings::RIGHT, READ)) {
            return false;
        }

        if (($item->fields['id'] ?? 0) <= 0) {
            return false;
        }

        return true;
    }
}
