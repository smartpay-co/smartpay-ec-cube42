<?php

namespace Plugin\Smartpay;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'smartpay' => [
                'name' => 'smartpay.admin.config.title',
                'icon' => 'fa-credit-card',
                'children' => [
                    'smartpay_admin_config' => [
                        'name' => 'smartpay.admin.nav.config',
                        'url' => 'smartpay_admin_config'
                    ]
                ]
            ]
        ];
    }
}
