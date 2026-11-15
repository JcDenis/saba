<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       saba frontend class.
 * @ingroup     saba
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Frontend
{
    use TraitProcess;

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

        // Templates of the theme templates set first, then generic ones (themes are loaded after plugins)
        App::behavior()->addBehavior('publicPrependV2', function (): void {
            $theme  = (string) App::blog()->settings()->get('system')->getStr('theme');
            $tplset = App::themes()->moduleInfo($theme, 'tplset');
            $path   = My::path() . DIRECTORY_SEPARATOR . 'default-templates';
            if (is_string($tplset) && $tplset !== '' && is_dir($path . DIRECTORY_SEPARATOR . $tplset)) {
                App::frontend()->template()->appendPath($path . DIRECTORY_SEPARATOR . $tplset);
            }
            App::frontend()->template()->appendPath($path);
        });

        App::behavior()->addBehaviors([
            'templateCustomSortByAlias' => FrontendBehaviors::templateCustomSortByAlias(...),
            'urlHandlerBeforeGetData'   => FrontendBehaviors::urlHandlerBeforeGetData(...),
            'coreBlogBeforeGetPosts'    => FrontendBehaviors::coreBlogBeforeGetPosts(...),
            'initWidgets'               => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
