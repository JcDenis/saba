<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use Dotclear\App;

/**
 * @brief       saba utils class.
 * @ingroup     saba
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Utils
{
    /**
     * @return  array{q: string, q_opt: array<int, string>, q_cat: array<int, int>, q_age: string, q_user: array<int, string>, q_order: string, q_rev: string, q_type: array<int, string>}
     */
    public static function getSabaDefaultPostsOptions(): array
    {
        return [
            'q'       => '',
            'q_opt'   => [],
            'q_cat'   => [],
            'q_age'   => '0,0',
            'q_user'  => [],
            'q_order' => 'date',
            'q_rev'   => '0',
            'q_type'  => [],
        ];
    }

    /**
     * @return  array<string, string>
     */
    public static function getSabaFormOptions(): array
    {
        return [
            __('Selected entry')  => 'selected',
            __('With comments')   => 'comment',
            __('With trackbacks') => 'trackback',
        ];
    }

    /**
     * @return  array<string, string>
     */
    public static function getSabaFormOrders(): array
    {
        return [
            __('Title')            => 'title',
            __('Selected entry')   => 'selected',
            __('Author')           => 'author',
            __('Date')             => 'date',
            __('Update')           => 'update',
            __('Comments count')   => 'comment',
            __('Trackbacks count') => 'trackback',
        ];
    }

    /**
     * @return  array<string, string>
     */
    public static function getSabaFormAges(): array
    {
        return [
            __('All')                => '0,0',
            __('Less than a month')  => '0,2592000',
            __('From 1 to 6 month')  => '2592000,15552000',
            __('From 6 to 12 month') => '15552000,31536000',
            __('More than a year')   => '31536000,0',
        ];
    }

    /**
     * @return  array<string, string>
     */
    public static function getSabaFormTypes(): array
    {
        $know = [
            'post' => __('Entry'),
            'page' => __('Page'),
        ];

        $rs    = [];
        $types = App::postTypes()->getPostTypes();

        // only entries and pages, other post types have their own search
        foreach ($know as $k => $v) {
            if (!isset($types[$k]) || !$types[$k]['public_url']) {
                continue;
            }
            $rs[$v] = $k;
        }

        return $rs;
    }
}
