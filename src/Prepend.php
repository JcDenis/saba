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
use Dotclear\Core\Process;
use Dotclear\Plugin\activityReport\{
    Action,
    ActivityReport,
    Group
};

class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // log frontend page 404 intercepted by saba
        if (defined('ACTIVITY_REPORT') && ACTIVITY_REPORT == 3) {
            $group = new Group(My::id(), My::name());
            $group->add(new Action(
                'saba404',
                __('404 error (saba)'),
                __('New 404 error page at "%s"'),
                'sabaBeforeErrorDocument',
                function () {
                    $url = is_null(dcCore::app()->blog) ? '' : dcCore::app()->blog->url;

                    $logs = [$url . urldecode($_SERVER['QUERY_STRING'])];
                    ActivityReport::instance()->addLog(My::id(), 'saba404', $logs);
                }
            ));
            ActivityReport::instance()->groups->add($group);
        }

        return true;
    }
}
