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

if (!defined('DC_RC_PATH')) {
    return null;
}

# setting
$core->blog->settings->addNamespace('saba');

if (!$core->blog->settings->saba->active) {
    return null;
}

# translation
l10n::set(dirname(__FILE__) . '/locales/' . $_lang . '/public');

# widget
require_once dirname(__FILE__) . '/_widgets.php';

# template path
$core->tpl->setPath(
    $core->tpl->getPath(),
    dirname(__FILE__) . '/default-templates/'
);

# behavior
$core->addBehavior(
    'templateCustomSortByAlias',
    ['pubSaba', 'templateCustomSortByAlias']
);
$core->addBehavior(
    'urlHandlerBeforeGetData',
    ['pubSaba', 'urlHandlerBeforeGetData']
);
$core->addBehavior(
    'coreBlogBeforeGetPosts',
    ['pubSaba', 'coreBlogBeforeGetPosts']
);

# url
if ($core->blog->settings->saba->error) {
    $core->url->registerError(['urlSaba', 'error']);
}

class pubSaba
{
    public static function templateCustomSortByAlias($alias)
    {
        $alias['post'] = [
            'title' => 'post_title',
            'selected' => 'post_selected',
            'author' => 'user_id',
            'date' => 'post_dt',
            'update' => 'post_upddt',
            'id' => 'post_id',
            'comment' => 'nb_comment',
            'trackback' => 'nb_trackback'
        ];
    }

    public static function urlHandlerBeforeGetData($_ctx)
    {
        global $core;

        $options = tplSaba::getSabaDefaultPostsOptions();

        if (!empty($_GET['q']) && 1 < strlen($_GET['q'])) {

            # search string
            $params = new ArrayObject(['search' => rawurldecode($_GET['q'])]);

            $options = self::getPostsParams($params);
            $options['q'] = rawurldecode($_GET['q']);

            # count
            $GLOBALS['_search'] = rawurldecode($_GET['q']);
            if ($GLOBALS['_search']) {
                $GLOBALS['_search_count'] = $core->blog->getPosts($params, true)->f(0);
            }

            # pagintaion
            $_page_number = !isset($GLOBALS['_page_number']) ? 1 : $GLOBALS['_page_number'];
            $params['limit'] = $_ctx->nb_entry_per_page;
            $params['limit'] = [(($_page_number - 1) * $params['limit']), $params['limit']];

            # get posts
            $_ctx->post_params = $params;
            $_ctx->posts = $core->blog->getPosts($params);
            unset($params);
        }
        $_ctx->saba_options = $options;
    }

    public static function getPostsParams($params)
    {
        global $core;

        if (!isset($params['sql'])) {
            $params['sql'] = '';
        }

        $params['post_type'] = [];

        # retreive _GET
        $qs = $_SERVER['QUERY_STRING'];
        $qs = preg_replace('#(^|/)page/([0-9]+)#', '', $qs);
        parse_str($qs, $get);

        # search string
        $options = tplSaba::getSabaDefaultPostsOptions();
        $options['q'] = $params['search'];

        # options
        if (!empty($get['q_opt'])) {

            if (in_array('selected', $get['q_opt'])) {
                $options['q_opt'][] = 'selected';
                $params['post_selected'] = 1;
            }
            if (in_array('comment', $get['q_opt'])) {
                $options['q_opt'][] = 'comment';
                $params['sql'] = "AND nb_comment > 0 ";
            }
            if (in_array('trackback', $get['q_opt'])) {
                $options['q_opt'][] = 'trackback';
                $params['sql'] = "AND nb_trackback > 0";
            }
        }

        # categories
        if (!empty($get['q_cat'])) {
            $cats = array();
            foreach($get['q_cat'] as $v) {
                $v = abs((integer) $v);
                if (!$v) {
                    continue;
                }
                $cats[] = "C.cat_id = '" . $v . "'";
                $options['q_cat'][] = $v;
            }
            if (!empty($cats)) {
                $params['sql'] .= 'AND (' . implode(' OR ', $cats) . ') ';
            }
        }

        # post types
        if (!empty($get['q_type'])) {
            $types = $core->getPostTypes();
            foreach($get['q_type'] as $v) {
                if (!$types[$v]) {
                    continue;
                }
                $options['q_type'][] = $v;
                $params['post_type'][] = $v;
            }
        } else {
            $params['post_type'][] = 'post';
        }

        # age
        $ages = tplSaba::getSabaFormAges();
        if (!empty($get['q_age']) && in_array($get['q_age'], $ages)) {
            $age = explode(',', $get['q_age']);
            $ts = time();
            $options['q_age'] = $get['q_age'];

            if ($age[0]) {
                $params['sql'] .= "AND P.post_dt < '" .
                    dt::str('%Y-%m-%d %H:%m:%S', $ts - $age[0]) . "' ";
            }
            if ($age[1]) {
                $params['sql'] .= "AND P.post_dt > '" .
                    dt::str('%Y-%m-%d %H:%m:%S', $ts - $age[1]) . "' ";
            }
        }

        # user
        if (!empty($get['q_user'])) {
            $users = array();
            foreach($get['q_user'] as $v) {
                $users[] = "U.user_id = '" . $core->con->escape($v) . "'";
                $options['q_user'][] = $v;
            }
            if (!empty($users)) {
                $params['sql'] .= 'AND (' . implode(' OR ', $users) . ') ';
            }
        }

        #order
        $sort = 'desc';
        if (!empty($get['q_rev'])) {
            $options['q_rev'] = '1';
            $sort = 'asc';
        }
        $orders = tplSaba::getSabaFormOrders();
        if (!empty($get['q_order']) && in_array($get['q_order'], $orders)) {

            $options['q_order'] = $get['q_order'];
            $params['order'] = $core->tpl->getSortByStr(
                ['sortby' => $get['q_order'], 'order' => $sort],
                'post'
            ); //?! post_type
        }
        return $options;
    }

    # Ajouter la condition "ou" ? la recherche
    public static function coreBlogBeforeGetPosts($p)
    {
        global $core;

        if (empty($p['search'])) {
            return;
        }

        self::getPostsParams($p);

        $OR = [];
        foreach(explode(',', $p['search']) as $sentence) {
            $AND = [];
            $words = text::splitWords($sentence);
            foreach($words as $word) {
                $AND[] = "post_words LIKE '%" . $core->con->escape($word) . "%'";
            }
            if (!empty($AND)) {
                $OR[] = " (" . implode (' AND ', $AND) . ") ";
            }
        }
        if (!empty($OR)) {
            $p['search'] = '';
            $p['sql'] .= "AND (" . implode (' OR ', $OR) . ") ";
        }
    }
}

class urlSaba extends dcUrlHandlers
{
    public static function error($args, $type, $e)
    {
        global $core, $_ctx;

        if ($e->getCode() == 404) {
            $q = explode('/', $args);
            if (empty($q)) {
                return false;
            }

            # Clean URI
            $_GET['q'] = implode('%20', $q);
            $_SERVER['QUERY_STRING'] = '';

            # Claim comes from 404
            $GLOBALS['_from_error'] = true;

            # Serve saba
            self::serveDocument('saba_404_default.html');

            return true;
        }
    }
}

class tplSaba
{
    public static function getSabaDefaultPostsOptions()
    {
        return [
            'q'=> '',
            'q_opt' => [],
            'q_cat' => [],
            'q_age' => '0,0',
            'q_user'=> [],
            'q_order'=> 'date',
            'q_rev' => '0',
            'q_type'=> []
        ];
    }

    public static function getSabaFormOptions()
    {
        return [
            __('Selected entry') => 'selected',
            __('With comments') => 'comment',
            __('With trackbacks') => 'trackback'
        ];
    }

    public static function getSabaFormOrders()
    {
        return [
            __('Title') => 'title',
            __('Selected entry') => 'selected',
            __('Author') => 'author',
            __('Date') => 'date',
            __('Update') => 'update',
            __('Comments count') => 'comment',
            __('Trackbacks count') => 'trackback'
        ];
    }

    public static function getSabaFormAges()
    {
        return [
            __('All') => '0,0',
            __('Less than a month') => '0,2592000',
            __('From 1 to 6 month') => '2592000,15552000',
            __('From 6 to 12 month') => '15552000,31536000',
            __('More than a year') => '31536000,0'
        ];
    }

    public static function getSabaFormTypes()
    {
        $know = [
            'post' => __('Entry'),
            'page' => __('Page'),
            'pollsfactory' => __('Poll'),
            'eventhandler' => __('Event')
        ];
        // todo: add behavior for unknow types

        $rs = [];
        $types = $GLOBALS['core']->getPostTypes();

        foreach($types as $k => $v) {
            if (!$v['public_url']) {
                continue;
            }
            $rs[isset($know[$k]) ? $know[$k] : __($k)] = $k;
        }

        return $rs;
    }
}