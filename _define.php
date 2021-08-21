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

$this->registerModule(
	/* Name */
	"saba",
	/* Description*/
	"Search across blog's archive",
	/* Author */
	"Jean-Christian Denis",
	/* Version */
	'2021.08.20.2',
	/* Properies */
	array(
		'permissions' => 'admin',
		'type' => 'plugin',
		'dc_min' => '2.6',
		'support' => 'http://jcd.lv/q=saba',
		'details' => 'http://plugins.dotaddict.org/dc2/details/saba'
	)
);
