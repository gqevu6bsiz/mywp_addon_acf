<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpControllerAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpControllerModuleACFMainGeneral' ) ) :

final class MywpControllerModuleACFMainGeneral extends MywpControllerAbstractModule {

  static protected $id = 'acf_main_general';

  static protected $is_do_controller = true;

  protected static function after_init() {

    add_filter( 'mywp_controller_pre_get_model_' . self::$id , array( __CLASS__ , 'mywp_controller_pre_get_model' ) );

  }

  public static function mywp_controller_pre_get_model( $pre_model ) {

    $pre_model = true;

    return $pre_model;

  }

  public static function mywp_wp_loaded() {

    if( ! is_admin() ) {

      return false;

    }

    add_action( 'load-plugins.php' , array( __CLASS__ , 'load_plugins' ) );

  }

  private static function is_current_plugin( $plugin_file_name = false ) {

    if( empty( $plugin_file_name ) ) {

      return false;

    }

    $plugin_file_name = strip_tags( $plugin_file_name );

    if ( strpos( MYWP_ACF_PLUGIN_BASENAME , $plugin_file_name ) === false ) {

      return false;

    }

    return true;

  }

  public static function load_plugins() {

    add_filter( 'plugin_row_meta' , array( __CLASS__ , 'plugin_row_meta' ) , 10 , 4 );

    //add_action( 'in_plugin_update_message-' . MYWP_ACF_PLUGIN_BASENAME , array( __CLASS__ , 'in_plugin_update_message' ) , 10 , 2 );

    //add_action( 'admin_print_footer_scripts' , array( __CLASS__ , 'admin_print_footer_scripts' ) );

  }

  public static function plugin_row_meta( $plugin_meta , $plugin_file , $plugin_data , $status ) {

    if ( ! self::is_current_plugin( $plugin_file ) ) {

      return $plugin_meta;

    }

    $plugin_info = MywpACFApi::plugin_info();

    if( ! empty( $plugin_info['document_url'] ) ) {

      $plugin_meta[] =  sprintf( '<a href="%1$s" target="_blank">%2$s</a>' , esc_url( $plugin_info['document_url'] ) , __( 'Documents' , 'my-wp' ) );

    }

    $plugin_meta = apply_filters( 'mywp_acf_plugin_row_meta' , $plugin_meta , $plugin_file , $plugin_data , $status );

    return $plugin_meta;

  }

  /*
  public static function in_plugin_update_message( $plugin_data , $response ) {

    if( empty( $response->new_version ) ) {

      return false;

    }

    echo '</p>';

    echo '<p class="show">';

    $plugin_info = MywpACFApi::plugin_info();

    printf( __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>.' ) , $response->new_version , $plugin_info['github'] , 'target="_blank"' ,  MYWP_ACF_NAME );

  }

  public static function admin_print_footer_scripts() {

    echo '<style>';

    printf( 'tr#%s-update .update-message p { display: none; }' , MYWP_ACF_PLUGIN_DIRNAME );
    printf( 'tr#%s-update .update-message p.show { display: block; }' , MYWP_ACF_PLUGIN_DIRNAME );

    echo '</style>';

  }
  */

}

MywpControllerModuleACFMainGeneral::init();

endif;
