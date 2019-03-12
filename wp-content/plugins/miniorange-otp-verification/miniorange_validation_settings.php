<?php

/**
 * Plugin Name: Email Verification / SMS verification / Mobile Verification
 * Plugin URI: http://miniorange.com
 * Description: Email & SMS OTP verification for all forms. Passwordless Login. SMS Notifications. Support for External Gateway Providers. Enterprise grade. Active Support
 * Version: 3.2.61
 * Author: miniOrange
 * Author URI: http://miniorange.com
 * Text Domain: miniorange-otp-verification
 * Domain Path: /lang
 * WC requires at least: 2.0.0
 * WC tested up to: 3.5.2
 * License: GPL2
 */

if(! defined( 'ABSPATH' )) exit;
define('MOV_PLUGIN_NAME', plugin_basename(__FILE__));
include '_autoload.php';
include 'main.php';
MoOTP::instance(); //intialize the main class
