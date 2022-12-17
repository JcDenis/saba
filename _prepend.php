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

if (defined('ACTIVITY_REPORT_V2')) {
    dcCore::app()->activityReport->addAction(
        'blog',
        'saba404',
        __('404 error (saba)'),
        __('New 404 error page at "%s"'),
        'sabaBeforeErrorDocument',
        function () {
            $logs = [dcCore::app()->blog->url . urldecode($_SERVER['QUERY_STRING'])];
            dcCore::app()->activityReport->addLog('blog', 'saba404', $logs);
        }
    );
}
