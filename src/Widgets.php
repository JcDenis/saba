<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief       saba widgets class.
 * @ingroup     saba
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
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
                self::parseWidget(...),
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
        if (!App::blog()->isDefined()
            || !App::blog()->settings()->get(My::id())->get('active')
            || !App::blog()->settings()->get(My::id())->get('error') && App::url()->getType() == '404'
            || $w->__get('offline')
        ) {
            return '';
        }

        $ctx          = App::frontend()->context()->__get('saba_options');
        $saba_options = Utils::getSabaDefaultPostsOptions();
        if (is_array($ctx)) {
            foreach (['q', 'q_age', 'q_order', 'q_rev'] as $k) {
                if (isset($ctx[$k]) && is_string($ctx[$k])) {
                    $saba_options[$k] = $ctx[$k];
                }
            }
            foreach (['q_opt', 'q_user', 'q_type'] as $k) {
                if (isset($ctx[$k]) && is_array($ctx[$k])) {
                    $saba_options[$k] = array_values(array_filter($ctx[$k], is_string(...)));
                }
            }
            if (isset($ctx['q_cat']) && is_array($ctx['q_cat'])) {
                $saba_options['q_cat'] = array_values(array_filter($ctx['q_cat'], is_int(...)));
            }
        }
        $str = fn (string $k): string => is_string($v = $w->__get($k)) ? $v : '';
        $res = '';

        # advanced search only on search page
        if (App::url()->getType() == 'search') {
            # order
            if (!$w->__get('saba_filter_orders')) {
                $ct = '';

                foreach (Utils::getSabaFormOrders() as $k => $v) {
                    $ct .= '<li><label><input name="q_order" type="radio" value="' .
                        $v . '" ' .
                        ($v == $saba_options['q_order'] ? 'checked="checked" ' : '') .
                        '/> ' . Html::escapeHTML((string) $k) . '</label></li>';
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
                $rm = explode(',', $str('saba_remove_options'));

                foreach (Utils::getSabaFormOptions() as $k => $v) {
                    if (in_array($v, $rm)) {
                        continue;
                    }
                    $ct .= '<li><label><input name="q_opt[]" type="checkbox" value="' .
                        $v . '" ' .
                        (in_array($v, $saba_options['q_opt']) ? 'checked="checked" ' : '') .
                        '/> ' . Html::escapeHTML((string) $k) . '</label></li>';
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
                        '/> ' . Html::escapeHTML((string) $k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by age')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # types
            if (!$w->__get('saba_filter_types')) {
                $ct = '';
                $rm = explode(',', $str('saba_remove_types'));

                foreach (Utils::getSabaFormTypes() as $k => $v) {
                    if (in_array($v, $rm)) {
                        continue;
                    }
                    $ct .= '<li><label><input name="q_type[]" type="checkbox" value="' .
                        $v . '" ' .
                        (in_array($v, $saba_options['q_type']) ? 'checked="checked" ' : '') .
                        '/> ' . Html::escapeHTML((string) $k) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by type')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # categories
            if (!$w->__get('saba_filter_categories')) {
                $ct = '';
                $rm = explode(',', $str('saba_remove_categories'));
                $rs = App::blog()->getCategories();

                while ($rs->fetch()) {
                    if (in_array((string) $rs->intField('cat_id'), $rm) || in_array($rs->strField('cat_url'), $rm)) {
                        continue;
                    }
                    $ct .= '<li><label><input name="q_cat[]" type="checkbox" value="' .
                        $rs->intField('cat_id') . '" ' .
                        (in_array($rs->intField('cat_id'), $saba_options['q_cat'], true) ? 'checked="checked" ' : '') .
                        '/> ' . Html::escapeHTML($rs->strField('cat_title')) . '</label></li>';
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by category')) . sprintf('<ul>%s</ul>', $ct);
                }
            }

            # authors
            if (!$w->__get('saba_filter_authors')) {
                $ct = '';
                $rm = explode(',', $str('saba_remove_authors'));
                $rs = App::blog()->getPostsUsers();

                while ($rs->fetch()) {
                    if (in_array($rs->strField('user_id'), $rm)) {
                        continue;
                    }
                    $ct .= sprintf(
                        '<li><label><input name="q_user[]" type="checkbox" value="%s" %s/> %s</label></li>',
                        Html::escapeHTML($rs->strField('user_id')),
                        in_array($rs->strField('user_id'), $saba_options['q_user'], true) ? 'checked="checked" ' : '',
                        Html::escapeHTML(App::users()->getUserCN($rs->strField('user_id'), $rs->strField('user_name'), $rs->strField('user_firstname'), $rs->strField('user_displayname')))
                    );
                }
                if (!empty($ct)) {
                    $res .= $w->renderTitle(__('Filter by author')) . sprintf('<ul>%s</ul>', $ct);
                }
            }
        }

        return $w->renderDiv(
            (bool) $w->__get('content_only'),
            $str('class'),
            'id="search"',
            ($str('title') !== '' ? $w->renderTitle('<label for="q">' . Html::escapeHTML($str('title')) . '</label>') : '') .
            '<form action="' . App::blog()->url() . '" method="get" role="search">' .
            '<p><input type="text" size="10" maxlength="255" id="q" name="q" value="' .
            Html::escapeHTML($saba_options['q']) . '" ' .
            ($str('placeholder') !== '' ? 'placeholder="' . Html::escapeHTML($str('placeholder')) . '"' : '') .
            ' aria-label="' . __('Search') . '"/> ' .
            '<input type="submit" class="submit" value="ok" title="' . __('Search') . '" /></p>' .
            $res .
            '</form>'
        );
    }
}
