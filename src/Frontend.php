<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       saba frontend class.
 * @ingroup     saba
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!My::settings()->get('active')) {
            return false;
        }

        if (My::settings()->get('error')) {
            App::url()->registerError(UrlHandler::error(...));
        }

        App::frontend()->template()->appendPath(My::path() . DIRECTORY_SEPARATOR . 'default-templates');

        App::behavior()->addBehaviors([
            'templateCustomSortByAlias' => FrontendBehaviors::templateCustomSortByAlias(...),
            'urlHandlerBeforeGetData'   => FrontendBehaviors::urlHandlerBeforeGetData(...),
            'coreBlogBeforeGetPosts'    => FrontendBehaviors::coreBlogBeforeGetPosts(...),
            'initWidgets'               => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
