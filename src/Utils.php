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

    public static function getSabaFormOptions(): array
    {
        return [
            __('Selected entry')  => 'selected',
            __('With comments')   => 'comment',
            __('With trackbacks') => 'trackback',
        ];
    }

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

    public static function getSabaFormTypes(): array
    {
        $know = [
            'post'         => __('Entry'),
            'page'         => __('Page'),
            'pollsfactory' => __('Poll'),
            'eventhandler' => __('Event'),
        ];
        // todo: add behavior for unknow types

        $rs    = [];
        $types = App::postTypes()->getPostTypes();

        foreach ($types as $k => $v) {
            if (!$v['public_url']) {
                continue;
            }
            $rs[$know[$k] ?? __($k)] = $k;
        }

        return $rs;
    }
}
