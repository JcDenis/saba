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
declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use dcCore;
use dcUrlHandlers;
use Exception;

class UrlHandler extends dcUrlHandlers
{
    public static function error(?string $args, string $type, Exception $e): void
    {
        if ($e->getCode() == 404) {
            $q = explode('/', (string) $args);
            if (count($q) < 2) {
                return;
            }

            dcCore::app()->callBehavior('sabaBeforeErrorDocument');

            # Clean URI
            $_GET['q']               = implode('%20', $q);
            $_SERVER['QUERY_STRING'] = '';

            # Claim comes from 404
            $GLOBALS['_from_error'] = true;

            # Serve saba
            self::serveDocument('saba_404.html');

            # stop here
            exit(1);
        }
    }
}
