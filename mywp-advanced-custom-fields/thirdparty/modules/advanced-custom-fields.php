<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpThirdpartyAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpACFThirdpartyModuleACF' ) ) :

final class MywpACFThirdpartyModuleACF extends MywpThirdpartyAbstractModule {

  protected static $id = 'advanced_custom_fields';

  protected static $base_name = 'advanced-custom-fields/acf.php';

  protected static $name = 'ACF Custom Fields';

  public static function mywp_init() {

    add_filter( 'mywp_setting_post_types' , array( __CLASS__ , 'mywp_setting_post_types' ) );

    add_filter( 'mywp_controller_admin_sidebar_get_sidebar_item_added_classes_found_current_item_ids' , array( __CLASS__ , 'mywp_controller_admin_sidebar_get_sidebar_item_added_classes_found_current_item_ids' ) , 10 , 5 );

  }

  public static function current_pre_plugin_activate( $is_plugin_activate ) {

    if( class_exists( 'ACF' ) ) {

      return true;

    }

    return $is_plugin_activate;

  }

  public static function mywp_setting_post_types( $post_types ) {

    if( isset( $post_types['acf-field-group'] ) ) {

      unset( $post_types['acf-field-group'] );

    }

    return $post_types;

  }

  public static function mywp_controller_admin_sidebar_get_sidebar_item_added_classes_found_current_item_ids( $found_current_item_ids , $sidebar_items , $current_url , $current_url_parse , $current_url_query ) {

    if( ! empty( $found_current_item_ids ) ) {

      return $found_current_item_ids;

    }

    if( empty( $current_url_query['post_type'] ) or $current_url_query['post_type'] !== 'acf-field-group' ) {

      return $found_current_item_ids;

    }

    if( strpos( $current_url_parse['path'] , 'post-new.php' ) === false ) {

      return $found_current_item_ids;

    }

    foreach( $sidebar_items as $key => $sidebar_item ) {

      if( ! is_object( $sidebar_item ) ) {

        continue;

      }

      if( empty( $sidebar_item->item_link_url_parse['host'] ) or empty( $sidebar_item->item_link_url_parse['path'] ) ) {

        continue;

      }

      if(
        $current_url_parse['scheme'] !== $sidebar_item->item_link_url_parse['scheme'] or
        $current_url_parse['host'] !== $sidebar_item->item_link_url_parse['host']
      ) {

        continue;

      }

      if( $sidebar_item->item_link_url_parse_query !== array( 'post_type' => 'acf-field-group' ) ) {

        continue;

      }

      $found_current_item_ids[] = $sidebar_item->ID;

    }

    return $found_current_item_ids;

  }

}

MywpACFThirdpartyModuleACF::init();

endif;
