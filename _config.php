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

if (!defined('DC_CONTEXT_MODULE')) {

	return null;
}

# -- Get settings --
$core->blog->settings->addNamespace('saba');
$s = $core->blog->settings->saba;

$filters_list = array(
	'options',
	'orders',
	'ages',
	'categories',
	'authors',
	'types'
);

$saba_active = (boolean) $s->active;
$saba_filters = (string) $s->filters;

$saba_filters = @unserialize($saba_filters);
if (!is_array($saba_filters)) {
	$saba_filters = array();
}

# -- Set settings --
if (!empty($_POST['save'])) {

	try {
		$saba_active = !empty($_POST['saba_active']);
		$saba_filters = array();

		foreach($filters_list as $filter) {
			if (!empty($_POST['saba_filter_'.$filter])) {
				$saba_filters[] = $filter;
			}
		}

		$s->put(
			'active',
			$saba_active,
			'boolean',
			'Enable extension'
		);
		$s->put(
			'filters',
			serialize($saba_filters),
			'string',
			'Diabled filters'
		);

		$core->blog->triggerBlog();

		dcPage::addSuccessNotice(
			__('Configuration has been successfully updated.')
		);
		http::redirect(
			$list->getURL('module=saba&conf=1&redir='.
			$list->getRedir())
		);
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# -- Display form --
echo '

<div class="fieldset">
<h4>'.__('Activate').'</h4>

<p><label class="classic" for="saba_active">'.
form::checkbox(
	'saba_active',
	1,
	$saba_active
).__('Enable extension on this blog').'</label></p>

</div>

<div class="fieldset">
<h4>'.__('Advanced search').'</h4>

<p><label class="classic" for="saba_filter_options">'.
form::checkbox(
	'saba_filter_options',
	1,
	in_array('options', $saba_filters)
).__('Disable filter on post options').'</label></p>

<p><label class="classic" for="saba_filter_orders">'.
form::checkbox(
	'saba_filter_orders',
	1,
	in_array('orders', $saba_filters)
).__('Disable filter on order').'</label></p>

<p><label class="classic" for="saba_filter_ages">'.
form::checkbox(
	'saba_filter_ages',
	1,
	in_array('ages', $saba_filters)
).__('Disable filter on age').'</label></p>

<p><label class="classic" for="saba_filter_categories">'.
form::checkbox(
	'saba_filter_categories',
	1,
	in_array('categories', $saba_filters)
).__('Disable filter on categories').'</label></p>

<p><label class="classic" for="saba_filter_authors">'.
form::checkbox(
	'saba_filter_authors',
	1,
	in_array('authors', $saba_filters)
).__('Disable filter on authors').'</label></p>

<p><label class="classic" for="saba_filter_types">'.
form::checkbox(
	'saba_filter_types',
	1,
	in_array('types', $saba_filters)
).__('Disable filter on post types').'</label></p>

</div>';
