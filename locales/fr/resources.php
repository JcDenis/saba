<?php
/**
 * @file
 * @brief       The plugin saba locales resources
 * @ingroup     saba
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!empty($_REQUEST['module']) && $_REQUEST['module'] == 'saba') {
    \Dotclear\App::backend()->resources()->set('help', 'core_plugins_conf', __DIR__ . '/help/help.html');
}
