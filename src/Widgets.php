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
use dcUtils;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

class Widgets
{
    /**
     * Widget initialisation.
     *
     * @param  WidgetsStack $w WidgetsStack instance
     */
    public static function initWidgets(WidgetsStack $w): void
    {
        $w
            ->create(
                'saba',
                __('Advanced search'),
                [self::class, 'parseWidget'],
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

    /**
     * Public part for widget
     *
     * @param  WidgetsElement $w WidgetsElement instance
     */
    public static function parseWidget(WidgetsElement $w): string
    {
        if (is_null(dcCore::app()->blog)
            || is_null(dcCore::app()->ctx)
            || !dcCore::app()->blog->settings->get(My::id())->get('active')
            || !dcCore::app()->blog->settings->get(My::id())->get('error') && dcCore::app()->url->type == '404'
            || $w->__get('offline')
        ) {
            return '';
        }

        $saba_options = dcCore::app()->ctx->__get('saba_options') ?? [];
        if (!is_array($saba_options) || empty($saba_options)) {
            $saba_options = Utils::getSabaDefaultPostsOptions();
        }
        $res = '';

        # advanced search only on search page
        if (dcCore::app()->url->type == 'search') {
            # order
            if (!$w->__get('saba_filter_orders')) {
                $ct = '';

                foreach (Utils::getSabaFormOrders() as $k => $v) {
                    $ct .= '<li><label><input name="q_order" type="radio" value="' .
                        $v . '" ' .
                        ($v == $saba_options['q_order'] ? 'checked="checked" ' : '') .
                        '/> ' . Html::escapeHTML($k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $ct .= '<li><label><input name="q_rev" type="checkbox" value="1" ' .
                        (!empty($saba_options['q_rev']) ? 'checked="checked" ' : '') .
                        '/> ' . __('Reverse order') . '</label></li>';
                    $res .= $w->renderTitle(__('Filter order')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # options
            if (!$w->__get('saba_filter_options')) {
                $ct = '';
                $rm = explode(',', (string) $w->__get('saba_remove_options'));

                foreach (Utils::getSabaFormOptions() as $k => $v) {
                    if (in_array($v, $rm)) {
                        continue;
                    }
                    $ct .= '<li><label><input name="q_opt[]" type="checkbox" value="' .
                        $v . '" ' .
                        (in_array($v, $saba_options['q_opt']) ? 'checked="checked" ' : '') .
                        '/> ' . Html::escapeHTML($k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter options')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # ages
            if (!$w->__get('saba_filter_ages')) {
                $ct = '';

                foreach (Utils::getSabaFormAges() as $k => $v) {
                    $ct .= '<li><label><input name="q_age" type="radio" value="' .
                        $v . '" ' .
                        ($v == $saba_options['q_age'] ? 'checked="checked" ' : '') .
                        '/> ' . Html::escapeHTML($k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by age')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # types
            if (!$w->__get('saba_filter_types')) {
                $ct = '';
                $rm = explode(',', $w->__get('saba_remove_types'));

                foreach (Utils::getSabaFormTypes() as $k => $v) {
                    if (in_array($v, $rm)) {
                        continue;
                    }
                    $ct .= '<li><label><input name="q_type[]" type="checkbox" value="' .
                        $v . '" ' .
                        (in_array($v, $saba_options['q_type']) ? 'checked="checked" ' : '') .
                        '/> ' . Html::escapeHTML($k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by type')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # categories
            if (!$w->__get('saba_filter_categories')) {
                $ct = '';
                $rm = explode(',', $w->__get('saba_remove_categories'));
                $rs = dcCore::app()->blog->getCategories();

                while ($rs->fetch()) {
                    if (in_array($rs->f('cat_id'), $rm) || in_array($rs->f('cat_url'), $rm)) {
                        continue;
                    }
                    $ct .= '<li><label><input name="q_cat[]" type="checkbox" value="' .
                        $rs->f('cat_id') . '" ' .
                        (in_array($rs->f('cat_id'), $saba_options['q_cat']) ? 'checked="checked" ' : '') .
                        '/> ' . Html::escapeHTML($rs->f('cat_title')) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by category')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # authors
            if (!$w->__get('saba_filter_authors')) {
                $ct = '';
                $rm = explode(',', $w->__get('saba_remove_authors'));
                $rs = dcCore::app()->blog->getPostsUsers();

                while ($rs->fetch()) {
                    if (in_array($rs->f('user_id'), $rm)) {
                        continue;
                    }
                    $ct .= sprintf(
                        '<li><label><input name="q_user[]" type="checkbox" value="%s" %s/> %s</label></li>',
                        $rs->f('user_id'),
                        in_array($rs->f('user_id'), $saba_options['q_user']) ? 'checked="checked" ' : '',
                        Html::escapeHTML(dcUtils::getUserCN($rs->f('user_id'), $rs->f('user_name'), $rs->f('user_firstname'), $rs->f('user_displayname')))
                    );
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by author')) . sprintf('<ul>%s</ul>', $ct);
                }
            }
        }

        return $w->renderDiv(
            (bool) $w->__get('content_only'),
            $w->__get('class'),
            'id="search"',
            ($w->__get('title') ? $w->renderTitle('<label for="q">' . Html::escapeHTML($w->__get('title')) . '</label>') : '') .
            '<form action="' . dcCore::app()->blog->url . '" method="get" role="search">' .
            '<p><input type="text" size="10" maxlength="255" id="q" name="q" value="' .
            Html::escapeHTML($saba_options['q']) . '" ' .
            ($w->__get('placeholder') ? 'placeholder="' . Html::escapeHTML($w->__get('placeholder')) . '"' : '') .
            ' aria-label="' . __('Search') . '"/> ' .
            '<input type="submit" class="submit" value="ok" title="' . __('Search') . '" /></p>' .
            $res .
            '</form>'
        );
    }
}
