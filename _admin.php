<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of saba, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

# settings namespace
$core->blog->settings->addNamespace('saba');

# widget
require_once dirname(__FILE__) . '/_widgets.php';

# behaviors
$core->addBehavior(
    'adminBlogPreferencesForm',
    ['adminSaba', 'adminBlogPreferencesForm']
);
$core->addBehavior(
    'adminBeforeBlogSettingsUpdate',
    ['adminSaba', 'adminBeforeBlogSettingsUpdate']
);

# add settings to admin blog pref page
class adminSaba
{
    public static function adminBlogPreferencesForm($core, $blog_settings)
    {
        echo
        '<div class="fieldset">' .
        '<h4 id="saba_params">' . __('Search Across Blog Archive') . '</h4>' .
        '<p><label class="classic">' .
        form::checkbox('saba_active', '1', (boolean) $blog_settings->saba->active) . 
        __('Enable advanced search on this blog') . '</label></p>' .
        '<p><label class="classic">' .
        form::checkbox('saba_error', '1', (boolean) $blog_settings->saba->error) . 
        __('Enable suggestion for page 404') . '</label></p>' .
        '<p class="form-note">' .
        __("This suggests visitors some posts on page 404.") .
        '</p>' .
        '</div>';
    }

    public static function adminBeforeBlogSettingsUpdate($blog_settings)
    {
        $blog_settings->saba->put('active', !empty($_POST['saba_active']));
        $blog_settings->saba->put('error', !empty($_POST['saba_error']));
    }
}