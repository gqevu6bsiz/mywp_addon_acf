<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpControllerAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpControllerModuleACF' ) ) :

final class MywpControllerModuleACF extends MywpControllerAbstractModule {

  static protected $id = 'acf';

  public static function mywp_wp_loaded() {

    if( ! self::is_do_controller() ) {

      return false;

    }

    if( ! MywpACFApi::is_enable_acf() ) {

      return false;

    }

    //add_filter( 'mywp_controller_admin_sidebar_get_sidebar_item_added_classes_found_current_item_ids' , array( __CLASS__ , 'mywp_controller_admin_sidebar_get_sidebar_item_added_classes_found_current_item_ids' ) , 10 , 5 );

  }

  /*
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
  */

}

MywpControllerModuleACF::init();

endif;
