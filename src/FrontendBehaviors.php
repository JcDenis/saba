<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Ctx;
use Dotclear\Helper\Date;
use Dotclear\Helper\Text;

/**
 * @brief       saba frontend behaviors class.
 * @ingroup     saba
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FrontendBehaviors
{
    /**
     * @param   ArrayObject<string, mixed>  $alias
     */
    public static function templateCustomSortByAlias(ArrayObject $alias): void
    {
        $alias['post'] = [
            'title'     => 'post_title',
            'selected'  => 'post_selected',
            'author'    => 'user_id',
            'date'      => 'post_dt',
            'update'    => 'post_upddt',
            'id'        => 'post_id',
            'comment'   => 'nb_comment',
            'trackback' => 'nb_trackback',
        ];
    }

    public static function urlHandlerBeforeGetData(Ctx $_): void
    {
        if (!App::blog()->isDefined()) {
            return;
        }

        $options = Utils::getSabaDefaultPostsOptions();
        $q       = is_string($_GET['q'] ?? null) ? $_GET['q'] : '';

        if (1 < strlen($q)) {
            # pagintaion
            $_page_number = App::frontend()->getPageNumber();
            if ($_page_number < 1) {
                $_page_number = 1;
            }
            $limit = App::frontend()->context()->__get('nb_entry_per_page');
            $limit = is_numeric($limit) ? (int) $limit : 10;

            # search string
            /**
             * @var ArrayObject<string, mixed>
             */
            $params = new ArrayObject([
                'limit'  => [(($_page_number - 1) * $limit), $limit],
                'search' => rawurldecode($q),
            ]);

            $options      = self::getPostsParams($params);
            $options['q'] = rawurldecode($q);

            # count
            App::frontend()->search       = rawurldecode($q);
            App::frontend()->search_count = App::blog()->getPosts($params, true)->intField(0);

            # get posts
            $posts = App::blog()->getPosts($params);
            if ($posts->isEmpty()) { // hack: don't breack context
                $params = ['limit' => $params['limit']];
                $posts  = App::blog()->getPosts($params);
            }
            App::frontend()->context()->__set('post_params', $params);
            App::frontend()->context()->__set('posts', $posts);

            unset($params);
        }
        App::frontend()->context()->__set('saba_options', $options);
    }

    /**
     * @param   ArrayObject<string, mixed>  $params
     *
     * @return  array{q: string, q_opt: array<int, string>, q_cat: array<int, int>, q_age: string, q_user: array<int, string>, q_order: string, q_rev: string, q_type: array<int, string>}
     */
    public static function getPostsParams(ArrayObject $params): array
    {
        $sql        = is_string($params['sql'] ?? null) ? $params['sql'] : '';
        $post_types = [];

        # retreive _GET
        $qs = is_string($_SERVER['QUERY_STRING'] ?? null) ? $_SERVER['QUERY_STRING'] : '';
        $qs = (string) preg_replace('#(^|/)page/([0-9]+)#', '', $qs);
        parse_str($qs, $get);

        # search string
        $options      = Utils::getSabaDefaultPostsOptions();
        $options['q'] = is_string($params['search'] ?? null) ? $params['search'] : '';

        # options
        if (!empty($get['q_opt']) && is_array($get['q_opt'])) {
            if (in_array('selected', $get['q_opt'])) {
                $options['q_opt'][]      = 'selected';
                $params['post_selected'] = 1;
            }
            if (in_array('comment', $get['q_opt'])) {
                $options['q_opt'][] = 'comment';
                $sql .= 'AND nb_comment > 0 ';
            }
            if (in_array('trackback', $get['q_opt'])) {
                $options['q_opt'][] = 'trackback';
                $sql .= 'AND nb_trackback > 0 ';
            }
        }

        # categories
        if (!empty($get['q_cat']) && is_array($get['q_cat'])) {
            $cats = [];
            foreach ($get['q_cat'] as $v) {
                $v = is_numeric($v) ? abs((int) $v) : 0;
                if (!$v) {
                    continue;
                }
                $cats[]             = 'C.cat_id = ' . $v;
                $options['q_cat'][] = $v;
            }
            if (!empty($cats)) {
                $sql .= 'AND (' . implode(' OR ', $cats) . ') ';
            }
        }

        # post types
        if (!empty($get['q_type']) && is_array($get['q_type'])) {
            $types = App::postTypes()->getPostTypes();
            foreach ($get['q_type'] as $v) {
                if (!is_string($v) || !isset($types[$v])) {
                    continue;
                }
                $options['q_type'][] = $v;
                $post_types[]        = $v;
            }
        } else {
            $post_types[] = 'post';
        }

        # age
        $ages = Utils::getSabaFormAges();
        if (!empty($get['q_age']) && is_string($get['q_age']) && in_array($get['q_age'], $ages)) {
            $age              = explode(',', $get['q_age']);
            $ts               = time();
            $options['q_age'] = $get['q_age'];

            if (!empty($age[0])) {
                $sql .= "AND P.post_dt < '" .
                    Date::str('%Y-%m-%d %H:%M:%S', $ts - (int) $age[0]) . "' ";
            }
            if (!empty($age[1])) {
                $sql .= "AND P.post_dt > '" .
                    Date::str('%Y-%m-%d %H:%M:%S', $ts - (int) $age[1]) . "' ";
            }
        }

        # user
        if (!empty($get['q_user']) && is_array($get['q_user'])) {
            $users = [];
            foreach ($get['q_user'] as $v) {
                if (!is_string($v) || $v === '') {
                    continue;
                }
                $users[]             = "U.user_id = '" . App::db()->con()->escapeStr($v) . "'";
                $options['q_user'][] = $v;
            }
            if ($users !== []) {
                $sql .= 'AND (' . implode(' OR ', $users) . ') ';
            }
        }

        #order
        $sort = 'desc';
        if (!empty($get['q_rev'])) {
            $options['q_rev'] = '1';
            $sort             = 'asc';
        }
        $orders = Utils::getSabaFormOrders();
        if (!empty($get['q_order']) && is_string($get['q_order']) && in_array($get['q_order'], $orders)) {
            $options['q_order'] = $get['q_order'];
            /**
             * @var ArrayObject<string, mixed>
             */
            $attr            = new ArrayObject(['sortby' => $get['q_order'], 'order' => $sort]);
            $params['order'] = App::frontend()->template()->getSortByStr($attr, 'post'); //?! post_type
        }

        $params['sql']       = $sql;
        $params['post_type'] = $post_types;

        return $options;
    }

    /**
     * Ajouter la condition "ou" à la recherche.
     *
     * @param   ArrayObject<string, mixed>  $p
     */
    public static function coreBlogBeforeGetPosts(ArrayObject $p): void
    {
        if (empty($p['search'])) {
            return;
        }

        self::getPostsParams($p);

        $OR = [];
        # decoupe un peu plus la recherche
        $search = is_string($p['search']) ? $p['search'] : '';
        $splits = preg_split("#[\s//,-_]+#", $search);
        if (!$splits) {
            $splits = explode(',', $search);
        }
        foreach ($splits as $sentence) {
            $AND   = [];
            $words = Text::splitWords($sentence);
            foreach ($words as $word) {
                $AND[] = "post_words LIKE '%" . App::db()->con()->escapeStr((string) $word) . "%'";
            }
            if (!empty($AND)) {
                $OR[] = ' (' . implode(' AND ', $AND) . ') ';
            }
        }
        if (!empty($OR)) {
            $p['search'] = '';
            $p['sql']    = (is_string($p['sql'] ?? null) ? $p['sql'] : '') . 'AND (' . implode(' OR ', $OR) . ') ';
        }
    }
}
