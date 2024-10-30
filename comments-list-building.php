<?php
/*
 * Plugin Name: Wordpress Comments List Building
 * Plugin URI: http://saleswonder.biz/?p=447&utm_source=listbuilding-plugin&utm_medium=plugin&utm_campaign=c-lb-to-lp
 * Description: Boost list building with freebies for commenting, add them to your email list, start a valuable funnel.
 * Version: 4.1.0
 * Author: Tobias B. Conrad
 * Author URI: http://saleswonder.biz
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: comment-list-builder
 * Domain Path: /languages
 */
/**
 * Copyright 2012 - 2014 Klick-Tipp.com
 * Copyright (c) since 2015, Tobias B. Conrad (email : support@saleswonder.biz)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
  define('COMMENT_LIST_BUILDER_AFFILIATE', 'Tobias-Conrad');
  define('COMMENT_LIST_BUILDER_CAMPAIGNKEY', 'C-L-B_plugin_BE');

  define('CLB_LISTBUILDER_PLUGIN_BASENAME', plugin_basename(__FILE__));

  add_action( 'plugins_loaded', 'comment_listbuilder_load_textdomain' );

  function comment_listbuilder_load_textdomain() {

    load_plugin_textdomain( 'comment-list-builder', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

  }

  include_once('inc/klicktipp.api.inc.php');

  include_once('inc/klick-tipp-listbuilding.class.php');

  if (class_exists("clb_klicktippListbuilding")) {

    $s_klicktippListbuilding = new clb_klicktippListbuilding();

  }

  register_activation_hook( __FILE__, 'clb_plugin_activation' );
  function clb_plugin_activation() {
      $aOptions = get_option('Optionswplistbuilder');
      $aOptions['clb_pt_post'] = 'TRUE';
      update_option('Optionswplistbuilder', $aOptions);
  }

?>