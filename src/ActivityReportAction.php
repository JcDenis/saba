<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Plugin\activityReport\{
    Action,
    ActivityReport,
    Group
};

/**
 * @brief       saba plugin activityReport class.
 * @ingroup     saba
 *
 * Add 404 to activity report
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ActivityReportAction extends Process
{
    public static function init(): bool
    {
        return self::status(true);
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $group = new Group(My::id(), My::name());

        $group->add(new Action(
            'saba404',
            __('404 error (saba)'),
            __('New 404 error page at "%s"'),
            'sabaBeforeErrorDocument',
            function () {
                $logs = [App::blog()->url() . urldecode($_SERVER['QUERY_STRING'])];
                ActivityReport::instance()->addLog(My::id(), 'saba404', $logs);
            }
        ));

        ActivityReport::instance()->groups->add($group);

        return true;
    }
}
