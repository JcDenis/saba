<?php

declare(strict_types=1);

namespace Dotclear\Plugin\saba;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Helper\Text;

use Dotclear\Core\Frontend\Ctx;

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

        if (!empty($_GET['q']) && 1 < strlen($_GET['q'])) {
            # pagintaion
            $_page_number = App::frontend()->getPageNumber();
            if ($_page_number < 1) {
                $_page_number = 1;
            }
            $limit = (int) App::frontend()->context()->__get('nb_entry_per_page');

            $params = [
                'limit'  => [(($_page_number - 1) * $limit), $limit],
                'search' => rawurldecode($_GET['q']),
            ];

            # search string
            $params = new ArrayObject($params);

            $options      = self::getPostsParams($params);
            $options['q'] = rawurldecode($_GET['q']);

            # count
            App::frontend()->search = rawurldecode($_GET['q']);
            if (App::frontend()->search) {
                App::frontend()->search_count = App::blog()->getPosts($params, true)->f(0);
            }

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
     * @return  array<string, mixed>
     */
    public static function getPostsParams(ArrayObject $params): array
    {
        if (!isset($params['sql'])) {
            $params['sql'] = '';
        }

        $params['post_type'] = [];

        # retreive _GET
        $qs = $_SERVER['QUERY_STRING'];
        $qs = (string) preg_replace('#(^|/)page/([0-9]+)#', '', $qs);
        parse_str($qs, $get);

        # search string
        $options      = Utils::getSabaDefaultPostsOptions();
        $options['q'] = $params['search'];

        # options
        if (!empty($get['q_opt']) && is_array($get['q_opt'])) {
            if (in_array('selected', $get['q_opt'])) {
                $options['q_opt'][]      = 'selected';
                $params['post_selected'] = 1;
            }
            if (in_array('comment', $get['q_opt'])) {
                $options['q_opt'][] = 'comment';
                $params['sql']      = 'AND nb_comment > 0 ';
            }
            if (in_array('trackback', $get['q_opt'])) {
                $options['q_opt'][] = 'trackback';
                $params['sql']      = 'AND nb_trackback > 0';
            }
        }

        # categories
        if (!empty($get['q_cat']) && is_array($get['q_cat'])) {
            $cats = [];
            foreach ($get['q_cat'] as $v) {
                $v = abs((int) $v);
                if (!$v) {
                    continue;
                }
                $cats[]             = "C.cat_id = '" . $v . "'";
                $options['q_cat'][] = $v;
            }
            if (!empty($cats)) {
                $params['sql'] .= 'AND (' . implode(' OR ', $cats) . ') ';
            }
        }

        # post types
        if (!empty($get['q_type']) && is_array($get['q_type'])) {
            $types = App::postTypes()->getPostTypes();
            foreach ($get['q_type'] as $v) {
                if (!$types[$v]) {
                    continue;
                }
                $options['q_type'][]   = $v;
                $params['post_type'][] = $v;
            }
        } else {
            $params['post_type'][] = 'post';
        }

        # age
        $ages = Utils::getSabaFormAges();
        if (!empty($get['q_age']) && is_string($get['q_age']) && in_array($get['q_age'], $ages)) {
            $age              = explode(',', $get['q_age']);
            $ts               = time();
            $options['q_age'] = $get['q_age'];

            if ($age[0]) {
                $params['sql'] .= "AND P.post_dt < '" .
                    Date::str('%Y-%m-%d %H:%m:%S', $ts - (int) $age[0]) . "' ";
            }
            if ($age[1]) {
                $params['sql'] .= "AND P.post_dt > '" .
                    Date::str('%Y-%m-%d %H:%m:%S', $ts - (int) $age[1]) . "' ";
            }
        }

        # user
        if (!empty($get['q_user']) && is_array($get['q_user'])) {
            $users = [];
            foreach ($get['q_user'] as $v) {
                $users[]             = "U.user_id = '" . App::con()->escapeStr((string) $v) . "'";
                $options['q_user'][] = $v;
            }
            $params['sql'] .= 'AND (' . implode(' OR ', $users) . ') ';
        }

        #order
        $sort = 'desc';
        if (!empty($get['q_rev'])) {
            $options['q_rev'] = '1';
            $sort             = 'asc';
        }
        $orders = Utils::getSabaFormOrders();
        if (!empty($get['q_order']) && in_array($get['q_order'], $orders)) {
            $options['q_order'] = $get['q_order'];
            $params['order']    = App::frontend()->template()->getSortByStr(
                new ArrayObject(['sortby' => $get['q_order'], 'order' => $sort]),
                'post'
            ); //?! post_type
        }

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
        $splits = preg_split("#[\s//,-_]+#", $p['search']);
        if (!$splits) {
            $splits = explode(',', $p['search']);
        }
        foreach ($splits as $sentence) {
            $AND   = [];
            $words = Text::splitWords($sentence);
            foreach ($words as $word) {
                $AND[] = "post_words LIKE '%" . App::con()->escapeStr((string) $word) . "%'";
            }
            if (!empty($AND)) {
                $OR[] = ' (' . implode(' AND ', $AND) . ') ';
            }
        }
        if (!empty($OR)) {
            $p['search'] = '';
            $p['sql'] .= 'AND (' . implode(' OR ', $OR) . ') ';
        }
    }
}
