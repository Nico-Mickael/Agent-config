<?php

namespace GlpiPlugin\Agentconfig;

use Session;

class Menu
{
    public static function getIcon(): string
    {
        return 'ti ti-qrcode';
    }

    public static function getMenuContent(): array|false
    {
        if (!Session::haveRight('config', READ)) {
            return false;
        }

        $page = '/plugins/agentconfig/front/config.form.php';

        return [
            'title'   => 'Agent Config',
            'page'    => $page,
            'icon'    => self::getIcon(),
            'links'   => ['search' => $page],
            'default' => $page,
        ];
    }
}
