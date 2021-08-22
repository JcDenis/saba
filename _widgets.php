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

if (!defined('DC_RC_PATH')) {
    return null;
}

$core->addBehavior('initWidgets', ['sabaWidget', 'setWidget']);

class sabaWidget
{
    public static function setWidget($w)
    {
        global $core;

        $w
            ->create(
                'saba',
                __('Advanced search'),
                array('sabaWidget', 'getWidget'),
                null,
                __('Add more search options on public side')
            )
            ->addTitle(__('Search'))
            ->setting(
                'saba_filter_types',
                __('Disable filter on post types'),
                0,
                'check'
            )
            ->setting('saba_remove_types', __('Hidden types:'), '')
            ->setting(
                'saba_filter_options',
                __('Disable filter on post options'),
                0,
                'check'
            )
            ->setting('saba_remove_options', __('Hidden options:'), '')
            ->setting(
                'saba_filter_categories',
                __('Disable filter on categories'),
                0,
                'check'
            )
            ->setting('saba_remove_categories', __('Hidden categories:'), '')
            ->setting(
                'saba_filter_authors',
                __('Disable filter on authors'),
                0,
                'check'
            )
            ->setting('saba_remove_authors', __('Hidden authors:'), '')
            ->setting(
                'saba_filter_orders',
                __('Disable filter on order'),
                0,
                'check'
            )
            ->setting(
                'saba_filter_ages',
                __('Disable filter on age'),
                0,
                'check'
            )
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    public static function getWidget($w)
    {
        global $core, $_ctx;
        
        $core->blog->settings->addNamespace('saba');

        if (!$core->blog->settings->saba->active) {
            return;
        }

        if (!$core->blog->settings->saba->error && $core->url->type == '404') {
            return;
        }

        if ($w->offline) {
            return;
        }

        # title and search
        $res = 
            ($w->title ? $w->renderTitle('<label for="q">' . html::escapeHTML($w->title) . '</label>') : '') .
            '<form action="' . $core->blog->url . '" method="get" role="search">' .
            '<p><input type="text" size="10" maxlength="255" id="q" name="q" value="' .
            html::escapeHTML($_ctx->saba_options["q"]) . '" ' .
            ($w->placeholder ? 'placeholder="' . html::escapeHTML($w->placeholder) . '"' : '') .
            ' aria-label="' . __('Search') . '"/> ' .
            '<input type="submit" class="submit" value="ok" title="' . __('Search') . '" /></p>' ;

        # advenced search only on search page
        if ($core->url->type == 'search') {

            # order
            if (!$w->saba_filter_orders) {
                $ct = '';

                foreach(tplSaba::getSabaFormOrders() as $k => $v) {
                    $ct .= 
                        '<li><label><input name="q_order" type="radio" value="' .
                        $v . '" ' .
                        ($v == $_ctx->saba_options['q_order'] ? 'checked="checked" ' : '') .
                        '/> ' . html::escapeHTML($k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $ct .= '<li><label><input name="q_rev" type="checkbox" value="1" ' . 
                        (!empty($_ctx->saba_options['q_rev']) ? 'checked="checked" ' : '') . 
                        '/> ' . __('Reverse order') . '</label></li>';
                    $res .= $w->renderTitle(__('Filter order')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # options
            if (!$w->saba_filter_options) {
                $ct = '';
                $rm = explode(',', $w->saba_remove_options);

                foreach(tplSaba::getSabaFormOptions() as $k => $v) {
                    if (in_array($v, $rm)) {
                        continue;
                    }
                    $ct .= 
                        '<li><label><input name="q_opt[]" type="checkbox" value="' .
                        $v . '" ' .
                        (in_array($v, $_ctx->saba_options['q_opt']) ? 'checked="checked" ' : '') .
                        '/> ' . html::escapeHTML($k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter options')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # ages
            if (!$w->saba_filter_ages) {
                $ct = '';

                foreach(tplSaba::getSabaFormAges() as $k => $v) {
                    $ct .= 
                        '<li><label><input name="q_age" type="radio" value="' .
                        $v . '" ' .
                        ($v == $_ctx->saba_options['q_age'] ? 'checked="checked" ' : '') .
                        '/> ' . html::escapeHTML($k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by age')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # types
            if (!$w->saba_filter_types) {
                $ct = '';
                $rm = explode(',', $w->saba_remove_types);

                foreach(tplSaba::getSabaFormTypes() as $k => $v) {
                    if (in_array($v, $rm)) {
                        continue;
                    }
                    $ct .= 
                        '<li><label><input name="q_type[]" type="checkbox" value="' .
                        $v . '" ' .
                        (in_array($v, $_ctx->saba_options['q_type']) ? 'checked="checked" ' : '') .
                        '/> ' . html::escapeHTML($k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by type')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # categories
            if (!$w->saba_filter_categories) {
                $ct = '';
                $rm = explode(',', $w->saba_remove_categories);
                $rs = $core->blog->getCategories();

                while ($rs->fetch()) {
                    if (in_array($rs->cat_id, $rm) || in_array($rs->cat_url, $rm)) {
                                continue;
                    }
                    $ct .= 
                        '<li><label><input name="q_cat[]" type="checkbox" value="' .
                        $rs->cat_id . '" ' .
                        (in_array($rs->cat_id, $_ctx->saba_options['q_cat']) ? 'checked="checked" ' : '') .
                        '/> ' . html::escapeHTML($rs->cat_title) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by category')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # authors
            if (!$w->saba_filter_authors) {
                $ct = '';
                $rm = explode(',', $w->saba_remove_authors);
                $rs = $core->blog->getPostsUsers();

                while ($rs->fetch()) {
                    if (in_array($rs->user_id, $rm)) {
                                continue;
                    }
                    $ct .= 
                        '<li><label><input name="q_user[]" type="checkbox" value="' .
                        $rs->user_id . '" ' .
                        (in_array($rs->user_id, $_ctx->saba_options['q_user']) ? 'checked="checked" ' : '') .
                        '/> ' . html::escapeHTML(dcUtils::getUserCN($rs->user_id, $rs->user_name, $rs->user_firstname, $rs->user_displayname)) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by author')) . sprintf('<ul>%s</ul>', $ct);
                }
            }
        }

        $res .= '</form>';

        return $w->renderDiv($w->content_only, $w->class, 'id="search"', $res);
    }
}