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
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::phpCompliant();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (is_null(dcCore::app()->blog) || !dcCore::app()->blog->settings->get(My::id())->get('active')) {
            return false;
        }

        if (dcCore::app()->blog->settings->get(My::id())->get('error')) {
            dcCore::app()->url->registerError([UrlHandler::class, 'error']);
        }

        dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), My::path() . DIRECTORY_SEPARATOR . 'default-templates');

        dcCore::app()->addBehaviors([
            'templateCustomSortByAlias' => [FrontendBehaviors::class, 'templateCustomSortByAlias'],
            'urlHandlerBeforeGetData'   => [FrontendBehaviors::class, 'urlHandlerBeforeGetData'],
            'coreBlogBeforeGetPosts'    => [FrontendBehaviors::class, 'coreBlogBeforeGetPosts'],
            'initWidgets'               => [Widgets::class, 'initWidgets'],
        ]);

        return true;
    }
}
