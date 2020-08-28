<?php
/*
Plugin Name: My WP Add-on Advanced Custom Fields
Plugin URI: https://mywpcustomize.com/add_ons/add-on-advanced-custom-fields/
Description: My WP Add-on Advanced Custom Fields customize for My WP.
Version: 1.0
Author: gqevu6bsiz
Author URI: http://gqevu6bsiz.chicappa.jp/
Text Domain: mywp-advanced-custom-fields
Domain Path: /languages
My WP Test working: 1.14
*/

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'MywpACF' ) ) :

final class MywpACF {

  private static $instance;

  private function __construct() {}

  public static function get_instance() {

    if ( !isset( self::$instance ) ) {

      self::$instance = new self();

    }

    return self::$instance;

  }

  private function __clone() {}

  private function __wakeup() {}

  public static function init() {

    self::define_constants();
    self::include_core();

    add_action( 'mywp_start' , array( __CLASS__ , 'mywp_start' ) );

  }

  private static function define_constants() {

    define( 'MYWP_ACF_NAME' , 'My WP Add-On Advanced Custom Fields' );
    define( 'MYWP_ACF_VERSION' , '1.0' );
    define( 'MYWP_ACF_PLUGIN_FILE' , __FILE__ );
    define( 'MYWP_ACF_PLUGIN_BASENAME' , plugin_basename( MYWP_ACF_PLUGIN_FILE ) );
    define( 'MYWP_ACF_PLUGIN_DIRNAME' , dirname( MYWP_ACF_PLUGIN_BASENAME ) );
    define( 'MYWP_ACF_PLUGIN_PATH' , plugin_dir_path( MYWP_ACF_PLUGIN_FILE ) );
    define( 'MYWP_ACF_PLUGIN_URL' , plugin_dir_url( MYWP_ACF_PLUGIN_FILE ) );

  }

  private static function include_core() {

    $dir = MYWP_ACF_PLUGIN_PATH . 'core/';

    require_once( $dir . 'class.api.php' );

  }

  public static function mywp_start() {

    add_action( 'mywp_plugins_loaded', array( __CLASS__ , 'mywp_plugins_loaded' ) );

    add_action( 'init' , array( __CLASS__ , 'wp_init' ) );

  }

  public static function mywp_plugins_loaded() {

    add_filter( 'mywp_controller_plugins_loaded_include_modules' , array( __CLASS__ , 'mywp_controller_plugins_loaded_include_modules' ) );

    add_filter( 'mywp_thirdparty_plugins_loaded_include_modules' , array( __CLASS__ , 'mywp_thirdparty_plugins_loaded_include_modules' ) );

  }

  public static function wp_init() {

    load_plugin_textdomain( 'mywp-acf' , false , MYWP_ACF_PLUGIN_DIRNAME . '/languages' );

  }

  public static function mywp_controller_plugins_loaded_include_modules( $includes ) {

    $dir = MYWP_ACF_PLUGIN_PATH . 'controller/modules/';

    $includes['acf_main_general'] = $dir . 'mywp.controller.module.main.general.php';
    $includes['acf_updater']      = $dir . 'mywp.controller.module.updater.php';

    return $includes;

  }

  public static function mywp_thirdparty_plugins_loaded_include_modules( $includes ) {

    $dir = MYWP_ACF_PLUGIN_PATH . 'thirdparty/modules/';

    $includes['acf'] = $dir . 'advanced-custom-fields.php';

    return $includes;

  }

}

MywpACF::init();

endif;
