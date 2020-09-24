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

  static private $post_type = '';

  private static function get_acf_all_fields( $post_type = false  ) {

    $post_type = strip_tags( $post_type );

    if( empty( $post_type ) ) {

      return false;

    }

    $acf_all_fields = array();

    $acf_get_field_groups = array( 'post_type'	=> $post_type );

    $acf_field_groups = acf_get_field_groups( $acf_get_field_groups );

    if( ! empty( $acf_field_groups ) && is_array( $acf_field_groups ) ) {

      foreach( $acf_field_groups as $acf_field_group ) {

        $acf_fields = acf_get_fields( $acf_field_group );

        if( empty( $acf_fields ) ) {

          continue;

        }

        foreach( $acf_fields as $acf_field ) {

          $acf_all_fields[] = $acf_field;

        }

      }

    }

    return $acf_all_fields;

  }

  public static function mywp_wp_loaded() {

    if( ! self::is_do_controller() ) {

      return false;

    }

    if( ! class_exists( 'ACF' ) ) {

      return false;

    }

    add_filter( 'mywp_setting_post_types' , array( __CLASS__ , 'mywp_setting_post_types' ) );

    add_filter( 'mywp_setting_admin_posts_get_available_list_columns' , array( __CLASS__ , 'mywp_setting_admin_posts_get_available_list_columns' ) , 10 , 2 );

    add_filter( 'mywp_controller_admin_sidebar_get_sidebar_item_added_classes_found_current_item_ids' , array( __CLASS__ , 'mywp_controller_admin_sidebar_get_sidebar_item_added_classes_found_current_item_ids' ) , 10 , 5 );

    add_action( 'mywp_ajax' , array( __CLASS__ , 'mywp_ajax' ) , 1000 );

    add_action( 'load-edit.php' , array( __CLASS__ , 'load_edit' ) , 1000 );

  }

  public static function mywp_setting_post_types( $post_types ) {

    if( isset( $post_types['acf-field-group'] ) ) {

      unset( $post_types['acf-field-group'] );

    }

    return $post_types;

  }

  public static function mywp_setting_admin_posts_get_available_list_columns( $available_list_columns , $list_column_id ) {

    $available_list_columns['acf'] = array(
      'title' => 'Advanced Custom Fields',
      'columns' => array(),
    );

    $columns = array();

    $acf_all_fields = self::get_acf_all_fields( $list_column_id );

    if( ! empty( $acf_all_fields ) && is_array( $acf_all_fields ) ) {

      foreach( $acf_all_fields as $acf_field ) {

        $id = $acf_field['key'];

        $columns[ $id ] = array(
          'id' => $id,
          'type' => 'acf',
          'sort' => '',
          'orderby' => '',
          'default_title' => $acf_field['label'],
          'title' => $acf_field['label'],
          'width' => '',
        );


      }

    }

    $available_list_columns['acf']['columns'] = $columns;

    if( isset( $available_list_columns['other'] ) ) {

      $other_columns = $available_list_columns['other'];

      unset( $available_list_columns['other'] );

      $available_list_columns['other'] = $other_columns;

    }

    if( isset( $available_list_columns['custom_fields'] ) ) {

      $custom_fields_columns = $available_list_columns['custom_fields'];

      unset( $available_list_columns['custom_fields'] );

      $available_list_columns['custom_fields'] = $custom_fields_columns;

    }

    return $available_list_columns;

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

  public static function mywp_ajax() {

    if( empty( $_POST['action'] ) or $_POST['action'] !== 'inline-save' ) {

      return false;

    }

    if( empty( $_POST['screen'] ) ) {

      return false;

    }

    if( empty( $_POST['post_type'] ) ) {

      return false;

    }

    self::$post_type = strip_tags( $_POST['post_type'] );

    add_action( 'manage_' . self::$post_type . '_posts_custom_column' , array( __CLASS__ , 'manage_column_body' ) , 10 , 2 );

  }

  public static function load_edit() {

    global $typenow;

    if( empty( $typenow ) ) {

      return false;

    }

    self::$post_type = $typenow;

    add_action( "manage_{$typenow}_posts_custom_column" , array( __CLASS__ , 'manage_column_body' ) , 10 , 2 );

  }

  public static function manage_column_body( $column_id , $post_id ) {

    if( ! self::is_do_function( __FUNCTION__ ) ) {

      return false;

    }

    $acf_all_fields = self::get_acf_all_fields( self::$post_type );

    if( ! empty( $acf_all_fields ) ) {

      $column_acf_field = false;

      foreach( $acf_all_fields as $acf_field ) {

        if( (string) $acf_field['key'] === (string) $column_id ) {

          $column_acf_field = $acf_field;

          break;

        }

      }

      if( ! empty( $column_acf_field ) ) {

        MywpACFApi::print_field_column( $column_id , $post_id , $column_acf_field );

      }

    }

    self::after_do_function( __FUNCTION__ );

  }

}

MywpControllerModuleACF::init();

endif;
