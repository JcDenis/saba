<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of saba, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2013 Jean-Christian Denis and contributors
# contact@jcdenis.fr
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {

	return null;
}

# Admin behaviors
$core->blog->settings->addNamespace('saba');

if ($core->blog->settings->saba->active) {

	# Register saba handler
	$core->url->register(
		'search',
		'search',
		'^search(/.+)?$',
		array('urlSaba', 'saba')
	);

	# Add saba templates path
	$core->tpl->setPath(
		$core->tpl->getPath(),
		dirname(__FILE__).'/default-templates/'
	);

	# templates
	$core->tpl->addBlock(
		'SabaIf',
		array('tplSaba', 'SabaIf')
	);
	$core->tpl->addBlock(
		'SabaEntries',
		array('tplSaba', 'SabaEntries')
	);
	$core->tpl->addBlock(
		'SabaFormIf',
		array('tplSaba', 'SabaFormIf')
	);
	$core->tpl->addValue(
		'SabaFormSearch',
		array('tplSaba', 'SabaFormSearch')
	);
	$core->tpl->addValue(
		'SabaFormOptions',
		array('tplSaba', 'SabaFormOptions')
	);
	$core->tpl->addValue(
		'SabaFormCategories',
		array('tplSaba', 'SabaFormCategories')
	);
	$core->tpl->addValue(
		'SabaFormTypes',
		array('tplSaba', 'SabaFormTypes')
	);
	$core->tpl->addValue(
		'SabaFormAges',
		array('tplSaba', 'SabaFormAges')
	);
	$core->tpl->addValue(
		'SabaFormOrders',
		array('tplSaba', 'SabaFormOrders')
	);
	$core->tpl->addValue(
		'SabaFormAuthors',
		array('tplSaba', 'SabaFormAuthors')
	);
	$core->tpl->addValue(
		'SabaPaginationURL',
		array('tplSaba', 'SabaPaginationURL')
	);
	$core->tpl->addValue(
		'SabaURL',
		array('tplSaba','SabaURL')
	);

	# behavior
	$core->addBehavior(
		'templateCustomSortByAlias',
		array('pubSaba', 'templateCustomSortByAlias')
	);
	$core->addBehavior(
		'urlHandlerBeforeGetData',
		array('pubSaba', 'urlHandlerBeforeGetData')
	);
	$core->addBehavior(
		'corePostSearch',
		array('pubSaba', 'corePostSearch')
	);
	$core->url->registerError(array('urlSaba','error'));
}

class pubSaba
{
	public static function templateCustomSortByAlias($alias)
	{
		$alias['post'] = array(
			'title'		=> 'post_title',
			'selected'	=> 'post_selected',
			'author'		=> 'user_id',
			'date'		=> 'post_dt',
			'update'		=> 'post_upddt',
			'id'			=> 'post_id',
			'comment'		=> 'nb_comment',
			'trackback'	=> 'nb_trackback'
		);
	}

	public static function urlHandlerBeforeGetData($_ctx)
	{
		global $core;

		$options = array(
			'q'		=> '',
			'q_opt'	=> array(),
			'q_cat'	=> array(),
			'q_age'	=> '0,0',
			'q_user'	=> array(),
			'q_order'	=> 'date',
			'q_rev'	=> '0',
			'q_type'	=> array()
		);

		if (!empty($_GET['q']) && 1 < strlen($_GET['q'])) {
			# move to saba
			$_ctx->current_tpl = null;
			$_ctx->current_tpl = 'saba_search.html';

			# retreive _GET
			$qs = $_SERVER['QUERY_STRING'];
			$qs = preg_replace('#(^|/)page/([0-9]+)#', '', $qs);
			parse_str($qs, $get);

			$params = array(
				'sql'=>'',
				'post_type' => ''
			);

			# search string
			$params['search'] = rawurldecode($_GET['q']);
			$options['q'] = rawurldecode($_GET['q']);

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
					$cats[] = "C.cat_id = '".$v."'";
					$options['q_cat'][] = $v;
				}
				if (!empty($cats)) {
					$params['sql'] .= 'AND ('.implode(' OR ', $cats).') ';
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
			}

			# age
			$ages = tplSaba::getSabaFormAges();
			if (!empty($get['q_age']) && in_array($get['q_age'], $ages)) {
				$age = explode(',', $get['q_age']);
				$ts = time();
				$options['q_age'] = $get['q_age'];
				
				if ($age[0]) {
					$params['sql'] .= "AND P.post_dt < '".dt::str('%Y-%m-%d %H:%m:%S', $ts-$age[0])."' ";
				}
				if ($age[1]) {
					$params['sql'] .= "AND P.post_dt > '".dt::str('%Y-%m-%d %H:%m:%S', $ts-$age[1])."' ";
				}
			}

			# user
			if (!empty($get['q_user'])) {
				$users = array();
				foreach($get['q_user'] as $v) {
					$users[] = "U.user_id = '".$core->con->escape($v)."'";
					$options['q_user'][] = $v;
				}
				if (!empty($users)) {
					$params['sql'] .= 'AND ('.implode(' OR ', $users).') ';
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
					array('sortby'=>$get['q_order'], 'order'=>$sort),'post'); //?! post_type
			}

			# count
			$GLOBALS['_search'] = rawurldecode($_GET['q']);
			if ($GLOBALS['_search']) {
				$GLOBALS['_search_count'] = $core->blog->getPosts($params, true)->f(0);
			}

			# pagintaion
			$_page_number = !isset($GLOBALS['_page_number']) ? 1 : $GLOBALS['_page_number'];
			$params['limit'] = $_ctx->nb_entry_per_page;
			$params['limit'] = array((($_page_number-1)*$params['limit']), $params['limit']);

			# get posts
			$_ctx->post_params = $params;
			$_ctx->posts = $core->blog->getPosts($params);
			unset($params);
		}
		$_ctx->saba_options = $options;
	}

	# Ajouter la condition "ou" à la recherche
	public static function corePostSearch($core, $p)
	{
		$sentences = explode(',', $p[2]['search']);

		$OR = array();
		foreach($sentences as $sentence)
		{
			$AND = array();
			$words = text::splitWords($sentence);
			foreach($words as $word) {
				$AND[] = "post_words LIKE '%".$core->con->escape($word)."%'";
			}
			if (!empty($AND)) {
				$OR[] = " (".implode (' AND ',$AND).") ";
			}
		}
		if (!empty($OR)) {
			$req = "AND (".implode (' OR ',$OR).") ";
		}

		# Return
		if (!empty($req)) {
			$p[0] = '';
			$p[2]['sql'] = (isset($p[2]['sql']) ? $p[2]['sql'] : '').$req;
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
			self::serveDocument('saba_search.html');

			return true;
		}
	}
	
	public static function saba($args)
	{
		$_ctx =& $GLOBALS['_ctx'];
		$core =& $GLOBALS['core'];

		self::serveDocument('saba_search.html');
	}
}

class tplSaba
{
	public static function SabaEntries($a, $c)
	{
		return
		'<?php if ($_ctx->exists("posts")) : while ($_ctx->posts->fetch()) : ?>'.$c.'<?php endwhile; endif; ?>';
	}

	public static function SabaFormSearch($a)
	{
		return '<?php echo html::escapeHTML($_ctx->saba_options["q"]); ?>';
	}

	public static function SabaIf($a, $c)
	{
		$if = array();

		$operator = isset($a['operator']) ? $GLOBALS['core']->tpl->getOperator($a['operator']) : '&&';
		
		if (isset($a['has_search'])) {
			$sign = (boolean) $a['has_search'] ? '' : '!';
			$if[] = $sign.'isset($_search_count)';
		}

		if (isset($a['from_error'])) {
			$sign = (boolean) $a['from_error'] ? '' : '!';
			$if[] = $sign.'isset($_from_error)';
		}

		return !empty($if) ?
			'<?php if('.implode(' '.$operator.' ', $if).') : ?>'.$c.'<?php endif; ?>'
			: $c;
	}

	public static function SabaURL($a)
	{
		$f = $GLOBALS['core']->tpl->getFilters($a);

		return '<?php echo '.sprintf($f,'$core->blog->url.$core->url->getBase("search")').'; ?>';
	}

	public static function SabaFormIf($a,$c)
	{
		$if = array();

		$operator = isset($a['operator']) ? $GLOBALS['core']->tpl->getOperator($a['operator']) : '&&';

		$fl = self::getSabaFormFilters();
		foreach($fl as $filter) {
			if (isset($a['filter_'.$filter])) {
				$sign = (boolean) $a['filter_'.$filter] ? '' : '!';
				$if[] = $sign.'tplSaba::isSabaFormFilter(\''.$filter.'\')';
			}
		}

		return !empty($if) ?
			'<?php if('.implode(' '.$operator.' ',$if).') : ?>'.$c.'<?php endif; ?>'
			: $c;
	}

	public static function SabaFormOptions($a)
	{
		$dis = !empty($a['remove']) ? explode(',', $a['remove']) : array();

		$res = '';
		$li = '<li><label><input name="q_opt[]" type="checkbox" value="%s" %s/> %s</label></li>';

		$rs = self::getSabaFormOptions();
		foreach($rs as $k => $v) {
			if (in_array($v,$dis)) {
				continue;
			}
			$chk = '<?php echo in_array("'.$v.'",$_ctx->saba_options["q_opt"]) ? \'checked="checked" \' : ""; ?>';
			$res .= sprintf($li, $v, $chk, html::escapeHTML($k));
		}

		if (!empty($res)) {

			return '<div class="saba_opt_otp"><h2>'.__('Filter options').'</h2><ul>'.$res.'</ul></div>';
		}
	}

	public static function SabaFormOrders($a)
	{
		$dis = !empty($a['remove']) ? explode(',',$a['remove']) : array();

		$res = '';
		$li = '<li><label><input name="q_order" type="radio" value="%s" %s/> %s</label></li>';

		$rs = self::getSabaFormOrders($dis);
		foreach($rs as $k => $v) {
			if (in_array($v,$dis)) {
				continue;
			}
			$chk = '<?php echo "'.$v.'" == $_ctx->saba_options["q_order"] ? \'checked="checked" \' : ""; ?>';
			$res .= sprintf($li, $v, $chk, html::escapeHTML($k));
		}

		if (!empty($res)) {
			$chk = '<?php echo !empty($_ctx->saba_options["q_rev"]) ? \'checked="checked" \' : ""; ?>';
			$res .= '<li><label><input name="q_rev" type="checkbox" value="1" '.$chk.'/> '.__('Reverse order').'</label></li>';

			return '<div class="saba_opt_order"><h2>'.__('Filter order').'</h2><ul>'.$res.'</ul></div>';
		}
	}

	public static function SabaFormCategories($a)
	{
		global $core;

		$dis = !empty($a['remove']) ? explode(',', $a['remove']) : array();

		$res = '';
		$li = '<li><label><input name="q_cat[]" type="checkbox" value="%s" %s/> %s</label></li>';

		$rs = $core->blog->getCategories();
		while ($rs->fetch()) {
			if (in_array($rs->cat_id,$dis) || in_array($rs->cat_url,$dis)) {
				continue;
			}
			$chk = '<?php echo in_array("'.$rs->cat_id.'",$_ctx->saba_options["q_cat"]) ? \'checked="checked" \' : ""; ?>';
			$res .= sprintf($li,$rs->cat_id,$chk,html::escapeHTML($rs->cat_title));
		}

		if (!empty($res)) {

			return '<div class="saba_opt_cat"><h2>'.__('Filter by category').'</h2><ul>'.$res.'</ul></div>';
		}
	}

	public static function SabaFormTypes($a)
	{
		global $core;

		$dis = !empty($a['remove']) ? explode(',',$a['remove']) : array();

		$res = '';
		$li = '<li><label><input name="q_type[]" type="checkbox" value="%s" %s/> %s</label></li>';

		$rs = self::getSabaFormTypes();
		foreach($rs as $k => $v) {
			if (in_array($v,$dis)) {
				continue;
			}
			$chk = '<?php echo in_array("'.$v.'",$_ctx->saba_options["q_type"]) ? \'checked="checked" \' : ""; ?>';
			$res .= sprintf($li,$v,$chk,html::escapeHTML($k));
		}

		if (!empty($res)) {

			return '<div class="saba_opt_type"><h2>'.__('Filter by type').'</h2><ul>'.$res.'</ul></div>';
		}
	}

	public static function SabaFormAges($a)
	{
		$res = '';
		$li = '<li><label><input name="q_age" type="radio" value="%s" %s/> %s</label></li>';

		$rs = self::getSabaFormAges();
		foreach($rs as $k => $v) {
			$chk = '<?php echo "'.$v.'" == $_ctx->saba_options["q_age"] ? \'checked="checked" \' : ""; ?>';
			$res .= sprintf($li, $v, $chk, html::escapeHTML($k));
		}

		if (!empty($res)) {

			return '<div class="saba_opt_age"><h2>'.__('Filter by age').'</h2><ul>'.$res.'</ul></div>';
		}
	}

	public static function SabaFormAuthors($a)
	{
		global $core;

		$dis = !empty($a['remove']) ? explode(',',$a['remove']) : array();

		$res = '';
		$li = '<li><label><input name="q_user[]" type="checkbox" value="%s" %s/> %s</label></li>';

		$rs = $core->blog->getPostsUsers();
		while ($rs->fetch()) {
			if (in_array($rs->user_id,$dis)) {
				continue;
			}
			$chk = '<?php echo in_array("'.$rs->user_id.'",$_ctx->saba_options["q_user"]) ? \'checked="checked" \' : ""; ?>';
			$res .= sprintf($li,$rs->user_id,$chk,html::escapeHTML(dcUtils::getUserCN($rs->user_id,$rs->user_name,$rs->user_firstname, $rs->user_displayname)));
		}

		if (!empty($res)) {

			return '<div class="saba_opt_user"><h2>'.__('Filter by author').'</h2><ul>'.$res.'</ul></div>';
		}
	}

	public static function SabaPaginationURL($attr)
	{
		$offset = 0;
		if (isset($attr['offset'])) {
			$offset = (integer) $attr['offset'];
		}
	
		$f = $GLOBALS['core']->tpl->getFilters($attr);

		return '<?php echo '.sprintf($f, "ctxSaba::PaginationURL(".$offset.")").'; ?>';
	}

	public static function getSabaFormFilters()
	{
		return array(
			'options',
			'orders',
			'ages',
			'categories',
			'authors',
			'types'
		);
	}

	public static function getSabaFormOptions()
	{
		return array(
			__('Selected entry') => 'selected',
			__('With comments') => 'comment',
			__('With trackbacks') => 'trackback'
		);
	}

	public static function getSabaFormOrders()
	{
		return array(
			__('Title') => 'title',
			__('Selected entry') => 'selected',
			__('Author') => 'author',
			__('Date') => 'date',
			__('Update') => 'update',
			__('Comments count') => 'comment',
			__('Trackbacks count') => 'trackback'
		);
	}

	public static function getSabaFormAges()
	{
		return array(
			__('All') => '0,0',
			__('Less than a month') => '0,2592000',
			__('From 1 to 6 month') => '2592000,15552000',
			__('From 6 to 12 month') => '15552000,31536000',
			__('More than a year') => '31536000,0'
		);
	}

	public static function getSabaFormTypes()
	{
		$know = array(
			'post' => __('Entry'),
			'page' => __('Page'),
			'pollsfactory' => __('Poll'),
			'eventhandler' => __('Event')
		);

		$rs = array();
		$types = $GLOBALS['core']->getPostTypes();

		foreach($types as $k => $v) {
			if (!$v['public_url']) {
				continue;
			}
			$rs[isset($know[$k]) ? $know[$k] : __($k)] = $k;
		}

		return $rs;
	}

	public static function isSabaFormFilter($f)
	{
		$filters = (string) $GLOBALS['core']->blog->settings->saba->filters;
		$filters = @unserialize($filters);
		if (!is_array($filters)) {
			$filters = array();
		}

		return !in_array($f,$filters);
	}
}

class ctxSaba extends context
{
	public static function PaginationURL($offset=0)
	{
		$args = $_SERVER['URL_REQUEST_PART'];

		$n = self::PaginationPosition($offset);

		$args = preg_replace('#(^|/)page/([0-9]+)$#', '', $args);

		$url = $GLOBALS['core']->blog->url.$args;

		if ($n > 1) {
			$url = preg_replace('#/$#', '', $url);
			$url .= '/page/'.$n;
		}

		$qs = preg_replace('#(^|/)page/([0-9]+)(&?)#', '', $_SERVER['QUERY_STRING']);

		# If search param
		if (!empty($_GET['q'])) {
			$s = strpos($url, '?') !== false ? '&amp;' : '?';
			$url .= $s.$qs;
		}

		return $url;
	}
}
