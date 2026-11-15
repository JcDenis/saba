<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use Dotclear\App;
use Exception;

/**
 * @brief       saba frontend URL handler class.
 * @ingroup     saba
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class UrlHandler
{
    public static function error(?string $args, string $type, Exception $e): void
    {
        if ($e->getCode() == 404) {
            $q = array_values(array_filter(explode('/', (string) $args), fn (string $v): bool => trim($v) !== ''));
            if ($q === []) {
                return;
            }

            App::behavior()->callBehavior('sabaBeforeErrorDocument');

            # Clean URI
            $_GET['q']               = implode('%20', $q);
            $_SERVER['QUERY_STRING'] = '';

            # Claim comes from 404
            $GLOBALS['_from_error'] = true;

            # Serve saba, as a page not found
            http_response_code(404);
            App::url()::serveDocument('saba_404.html', 'text/html', false, false);

            # stop here
            exit(1);
        }
    }
}
