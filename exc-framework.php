<?php if( ! defined('ABSPATH')) exit('restricted access');

/*
Plugin Name:  Extracoding Framework
Plugin URI:   http://www.extracoding.com/framework
License: http://www.extracoding.com/framework/license.txt
Description:  Extracoding framework is a powerful tool to develop wordpress themes and plugins faster.
Version:      1.1
Author:       Hassan r. Bhutta
Author URI:   http://extracoding.com
*/

//Include base class
$GLOBALS['_eXc'] = array();

require_once( 'core/base_class.php' );

do_action( 'exc-framework-load', '1.1' );