<?php
/**
 * @brief saba, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and Contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'saba',
    'Search across blog archive',
    'Jean-Christian Denis and Contributors',
    '2022.11.12',
    [
        'requires'    => [['core', '2.24']],
        'permissions' => dcAuth::PERMISSION_ADMIN,
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/saba',
        'details'     => 'https://plugins.dotaddict.org/dc2/details/saba',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/saba/master/dcstore.xml',
    ]
);
