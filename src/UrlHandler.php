<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Exception;

/**
 * @brief       saba frontend URL handler class.
 * @ingroup     saba
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class UrlHandler extends Url
{
    public static function error(?string $args, string $type, Exception $e): void
    {
        if ($e->getCode() == 404) {
            $q = explode('/', (string) $args);
            if (count($q) < 2) {
                return;
            }

            App::behavior()->callBehavior('sabaBeforeErrorDocument');

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
