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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

# settings namespace
dcCore::app()->blog->settings->addNamespace('saba');

# widget
require_once __DIR__ . '/_widgets.php';

# behaviors
dcCore::app()->addBehavior(
    'adminBlogPreferencesFormV2',
    ['adminSaba', 'adminBlogPreferencesForm']
);
dcCore::app()->addBehavior(
    'adminBeforeBlogSettingsUpdate',
    ['adminSaba', 'adminBeforeBlogSettingsUpdate']
);

# add settings to admin blog pref page
class adminSaba
{
    public static function adminBlogPreferencesForm($blog_settings)
    {
        echo
        '<div class="fieldset">' .
        '<h4 id="saba_params">' . __('Search Across Blog Archive') . '</h4>' .
        '<p><label class="classic">' .
        form::checkbox('saba_active', '1', (bool) $blog_settings->saba->active) .
        __('Enable advanced search on this blog') . '</label></p>' .
        '<p><label class="classic">' .
        form::checkbox('saba_error', '1', (bool) $blog_settings->saba->error) .
        __('Enable suggestion for page 404') . '</label></p>' .
        '<p class="form-note">' .
        __('This suggests visitors some posts on page 404.') .
        '</p>' .
        '</div>';
    }

    public static function adminBeforeBlogSettingsUpdate($blog_settings)
    {
        $blog_settings->saba->put('active', !empty($_POST['saba_active']));
        $blog_settings->saba->put('error', !empty($_POST['saba_error']));
    }
}
