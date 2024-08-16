<?php
/*
Plugin Name: My WP Add-on Advanced Custom Fields
Plugin URI: https://mywpcustomize.com/add_ons/my-wp-add-on-acf/
Description: My WP Add-on Advanced Custom Fields is customize for Posts and setting Posts on My WP.
Version: 1.3.0
Author: gqevu6bsiz
Author URI: https://mywpcustomize.com/
Text Domain: mywp-acf
Domain Path: /languages
My WP Test working: 1.24
ACF Test working: 6.3
*/

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'MywpACF' ) ) :

final class MywpACF {

  public static function init() {

    self::define_constants();
    self::include_core();

    add_action( 'mywp_start' , array( __CLASS__ , 'mywp_start' ) );

  }

  private static function define_constants() {

    define( 'MYWP_ACF_NAME' , 'My WP Add-On Advanced Custom Fields' );
    define( 'MYWP_ACF_VERSION' , '1.3.0' );
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

    add_filter( 'mywp_debug_types' , array( __CLASS__ , 'mywp_debug_types' ) );

  }

  public static function mywp_plugins_loaded() {

    add_filter( 'mywp_controller_plugins_loaded_include_modules' , array( __CLASS__ , 'mywp_controller_plugins_loaded_include_modules' ) );

    add_filter( 'mywp_developer_plugins_loaded_include_modules' , array( __CLASS__ , 'mywp_developer_plugins_loaded_include_modules' ) );

  }

  public static function wp_init() {

    load_plugin_textdomain( 'mywp-acf' , false , MYWP_ACF_PLUGIN_DIRNAME . '/languages' );

  }

  public static function mywp_controller_plugins_loaded_include_modules( $includes ) {

    $dir = MYWP_ACF_PLUGIN_PATH . 'controller/modules/';

    $includes['acf_main_general']     = $dir . 'mywp.controller.module.main.general.php';
    $includes['acf_updater']          = $dir . 'mywp.controller.module.updater.php';
    $includes['acf_controller']       = $dir . 'mywp.controller.module.acf.php';
    $includes['acf_posts_controller'] = $dir . 'mywp.controller.module.posts.php';

    return $includes;

  }

  public static function mywp_developer_plugins_loaded_include_modules( $includes ) {

    $dir = MYWP_ACF_PLUGIN_PATH . 'developer/modules/';

    $includes['acf_field_group'] = $dir . 'mywp.developer.module.acf.field-group.php';

    return $includes;

  }

  public static function mywp_debug_types( $debug_types ) {

    $debug_types['acf'] = __( 'ACF' , 'my-wp' );

    return $debug_types;

  }

}

MywpACF::init();

endif;
