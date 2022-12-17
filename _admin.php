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
dcCore::app()->blog->settings->addNamespace(basename(__DIR__));

# widget
require __DIR__ . '/_widgets.php';

# behaviors
dcCore::app()->addBehavior('adminBlogPreferencesFormV2', function ($blog_settings) {
    echo
    '<div class="fieldset">' .
    '<h4 id="saba_params">' . __('Search Across Blog Archive') . '</h4>' .
    '<p><label class="classic">' .
    form::checkbox('saba_active', '1', (bool) $blog_settings->__get(basename(__DIR__))->active) .
    __('Enable advanced search on this blog') . '</label></p>' .
    '<p><label class="classic">' .
    form::checkbox('saba_error', '1', (bool) $blog_settings->_get(basename(__DIR__))->error) .
    __('Enable suggestion for page 404') . '</label></p>' .
    '<p class="form-note">' .
    __('This suggests visitors some posts on page 404.') .
    '</p>' .
    '</div>';
});

dcCore::app()->addBehavior('adminBeforeBlogSettingsUpdate', function ($blog_settings) {
    $blog_settings->__get(basename(__DIR__))->put('active', !empty($_POST['saba_active']));
    $blog_settings->__get(basename(__DIR__))->put('error', !empty($_POST['saba_error']));
});
