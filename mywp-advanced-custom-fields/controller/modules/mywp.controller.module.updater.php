<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpControllerAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpControllerModuleACFUpdater' ) ) :

final class MywpControllerModuleACFUpdater extends MywpControllerAbstractModule {

  static protected $id = 'acf_updater';

  static private $schedule_hook = 'mywp_acf_version_check';

  protected static function after_init() {

    add_filter( 'mywp_controller_pre_get_model_' . self::$id , array( __CLASS__ , 'mywp_controller_pre_get_model' ) );

    add_filter( 'site_transient_update_plugins' , array( __CLASS__ , 'site_transient_update_plugins' ) );

    add_filter( 'plugins_api' , array( __CLASS__ , 'plugins_api' ) , 10 , 3 );

  }

  public static function mywp_controller_pre_get_model( $pre_model ) {

    $pre_model = true;

    return $pre_model;

  }

  public static function site_transient_update_plugins( $site_transient ) {

    if( empty( $site_transient ) or ! isset( $site_transient->response ) ) {

      return $site_transient;

    }

    $is_latest = self::is_latest();

    if( is_wp_error( $is_latest ) ) {

      return $site_transient;

    }

    if( $is_latest ) {

      return $site_transient;

    }

    $latest = self::get_latest();

    if( is_wp_error( $latest ) ) {

      return $site_transient;

    }

    $plugin_info = MywpACFApi::plugin_info();

    $remote = self::get_remote();

    if( empty( $remote ) or is_wp_error( $remote ) ) {

      return $site_transient;

    }

    $update_plugin = array(
      'id' => MYWP_ACF_PLUGIN_BASENAME,
      'slug' => MYWP_ACF_PLUGIN_DIRNAME,
      'plugin' => MYWP_ACF_PLUGIN_BASENAME,
      'new_version' => $latest,
      'url' => $plugin_info['github'],
      'package' => self::get_remote_download_link(),
      'icons' => array(),
      'banners' => array(),
      'banners_rtl' => array(),
      'requires' => false,
      'tested' => false,
      'compatibility' => false,
    );

    $site_transient->response[ MYWP_ACF_PLUGIN_BASENAME ] = (object) $update_plugin;

    return $site_transient;

  }

  public static function get_remote() {

    $transient_key = 'mywp_' . self::$id . '_remote';

    $transient = get_site_transient( $transient_key );

    if( ! empty( $transient ) ) {

      return $transient;

    }

    $plugin_info = MywpACFApi::plugin_info();

    $remote_args = array();

    $error = new WP_Error();

    $remote_result = wp_remote_get( $plugin_info['github_release_latest'] , $remote_args );

    if( empty( $remote_result ) ) {

      $error->add( 'not_results' , __( 'Connection lost or the server is busy. Please try again later.' , 'mywp-acf' ) );

      return $error;

    }

    if( is_wp_error( $remote_result ) ) {

      $error->add( 'invalid_remote' , $remote_result->get_error_message() );

      return $error;

    }

    $remote_code = wp_remote_retrieve_response_code( $remote_result );
    $remote_body = wp_remote_retrieve_body( $remote_result );

    set_site_transient( $transient_key , $remote_body , DAY_IN_SECONDS );

    if( $remote_code !== 200 ) {

      if( ! empty( $remote_body ) ) {

        $maybe_json = json_decode( $remote_body );

        if( ! empty( $maybe_json ) && ! empty( $maybe_json->message ) ) {

          $error->add( 'invalid_connection' , sprintf( '[%d] %s' , $remote_code , $maybe_json->message ) );

        } else {

          $error->add( 'invalid_json' , sprintf( '[%d] %s' , $remote_code , __( 'An error has occurred. Please reload the page and try again.' , 'mywp-acf' ) ) );

        }

      } else {

        $error->add( 'invalid_connection' , sprintf( '[%d] %s' , $remote_code , __( 'Connection lost or the server is busy. Please try again later.' , 'mywp-acf' ) ) );

      }

      return $error;

    }

    if( empty( $remote_body ) ) {

      $error->add( 'invalid_remote_body' , __( 'An error has occurred. Please reload the page and try again.' , 'mywp-acf' ) );

      return $error;

    }

    return $remote_body;

  }

  private static function get_remote_download_link() {

    $remote = self::get_remote();

    if( empty( $remote ) or is_wp_error( $remote ) ) {

      return false;

    }

    $maybe_remote_json = json_decode( $remote );

    if( empty( $maybe_remote_json->assets ) or ! is_array( $maybe_remote_json->assets ) ) {

      return false;

    }

    $download_link = '';

    $download_file_path = sprintf( '%s.%s.zip' , MYWP_ACF_PLUGIN_DIRNAME , self::get_latest() );

    foreach( $maybe_remote_json->assets as $asset ) {

      if( ! isset( $asset->name ) ) {

        continue;

      }

      if( $asset->name !== $download_file_path ) {

        continue;

      }

      if( empty( $asset->browser_download_url ) ) {

        continue;

      }

      $download_link = $asset->browser_download_url;

      break;

    }

    return $download_link;

  }

  public static function get_latest() {

    $transient_key = 'mywp_' . self::$id;

    $transient = get_site_transient( $transient_key );

    if( ! empty( $transient['latest'] ) ) {

      return $transient['latest'];

    }

    $remote = self::get_remote();

    if( empty( $remote ) or is_wp_error( $remote ) ) {

      return $remote;

    }

    $error = new WP_Error();

    $maybe_remote_json = json_decode( $remote );

    if( ! is_object( $maybe_remote_json ) or ! isset( $maybe_remote_json->tag_name ) ) {

      $error->add( 'invalid_remote_json' , __( 'Invalid remote Json data. Please try again.' , 'mywp-acf' ) );

      return $error;

    }

    $latest = $maybe_remote_json->tag_name;

    $transient = array( 'latest' => $latest );

    set_site_transient( $transient_key , $transient , DAY_IN_SECONDS );

    if( ! function_exists( 'wp_clean_plugins_cache' ) ) {

      require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    }

    wp_clean_plugins_cache();

    return $latest;

  }

  public static function is_latest() {

    $latest = self::get_latest();

    $error = new WP_Error();

    if( is_wp_error( $latest ) ) {

      return $latest;

    }

    $latest_compare = version_compare( $latest , MYWP_ACF_VERSION , '<=' );

    return $latest_compare;

  }

  public static function plugins_api( $false , $action , $args ) {

    if( ! in_array( $action , array( 'query_plugins' , 'plugin_information' ) , true ) ) {

      return $false;

    }

    if( empty( $args->slug ) ) {

      return $false;

    }

    if( $args->slug !== MYWP_ACF_PLUGIN_DIRNAME ) {

      return $false;

    }

    $remote = self::get_remote();

    if( empty( $remote ) or is_wp_error( $remote ) ) {

      return $false;

    }

    $maybe_remote_json = json_decode( $remote );

    if( ! is_object( $maybe_remote_json ) or ! isset( $maybe_remote_json->tag_name ) ) {

      return $false;

    }

    $plugin_info = MywpACFApi::plugin_info();

    $plugin_api = array(
      'name' => MYWP_ACF_NAME,
      'slug' => MYWP_ACF_PLUGIN_BASENAME,
      'version' => self::get_latest(),
      'author' => sprintf( '<a href="%s" target="_blank">%s</a>' , esc_url( $plugin_info['document_url'] ) , esc_html( 'gqevu6bsiz' ) ),
      'author_profile' => $plugin_info['website_url'],
      //'contributors' => array(),
      //'requires' => '',
      //'tested' => '',
      //'requires_php' => '',
      //'requires_plugins' => array(),
      //'rating' => '',
      //'ratings' => array(),
      //'num_ratings' => '',
      //'support_url' => '',
      //'support_threads' => '',
      //'support_threads_resolved' => '',
      //'active_installs' => '',
      'last_updated' => $maybe_remote_json->published_at,
      //'added' => '',
      'homepage' => $plugin_info['website_url'],
      'sections' => array(
        //'description' => '',
        //'faq' => '',
        'changelog' => sprintf( '<a href="%s" target="_blank">%s</a>' , esc_url( $plugin_info['github_releases'] ) , esc_html( 'See Releases.' ) ),
        //'screenshots' => '',
        //'reviews' => '',
      ),
      'download_link' => self::get_remote_download_link(),
      //'upgrade_notice' => array(),
      //'screenshots' => array(),
      //'tags' => array(),
      //'versions' => array(),
      //'business_model' => '',
      //'repository_url' => $plugin_info['github'],
      //'commercial_support_url' => '',
      //'donate_link' => '',
      //'banners' => array(),
      //'preview_link' => '',
    );

    $result = (object) $plugin_api;

    return $result;

  }

  public static function mywp_wp_loaded() {

    if( is_multisite() ) {

      if( ! is_main_site() ) {

        return false;

      }

    }

    self::schedule_hook();

    add_action( self::$schedule_hook , array( __CLASS__ , 'version_check' ) );

  }

  public static function schedule_hook() {

    if( wp_next_scheduled( self::$schedule_hook ) ) {

      return false;

    }

    $next_scheduled_date = time() + DAY_IN_SECONDS;

    wp_schedule_single_event( $next_scheduled_date , self::$schedule_hook );

  }

  public static function version_check() {

    self::get_latest();

  }

}

MywpControllerModuleACFUpdater::init();

endif;
