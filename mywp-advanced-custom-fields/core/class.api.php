<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'MywpACFApi' ) ) :

final class MywpACFApi {

  public static function plugin_info() {

    $plugin_info = array(
      'document_url' => 'https://mywpcustomize.com/add_ons/my-wp-add-on-acf/',
      'website_url' => 'https://mywpcustomize.com/',
      'github' => 'https://github.com/gqevu6bsiz/mywp_addon_acf',
      'github_raw' => 'https://raw.githubusercontent.com/gqevu6bsiz/mywp_addon_acf/',
      'github_tags' => 'https://api.github.com/repos/gqevu6bsiz/mywp_addon_acf/tags',
    );

    $plugin_info = apply_filters( 'mywp_acf_plugin_info' , $plugin_info );

    return $plugin_info;

  }

  public static function print_field_column( $column_id , $post_id , $acf_field ) {

    if( empty( $column_id ) ) {

      return false;

    }

    $column_id = strip_tags( $column_id );

    if( empty( $post_id ) ) {

      return false;

    }

    $post_id = intval( $post_id );

    if( empty( $acf_field['type'] ) ) {

      return false;

    }

    $acf_field_type = $acf_field['type'];

    $acf_field_name = '';

    if( ! empty( $acf_field['name'] ) ) {

      $acf_field_name = $acf_field['name'];

    }

    $filter_content = apply_filters( 'mywp_acf_print_field_column' , '' , $column_id , $post_id , $acf_field );

    if( ! empty( $filter_content ) ) {

      print_r( $filter_content );

    } else {

      if( in_array( $acf_field_type , array( 'group' ) ) ) {

        $acf_sub_fields = $acf_field['sub_fields'];

        if( have_rows( $acf_field_name , $post_id ) ) {

          echo '<ul>';

          while( have_rows( $acf_field_name , $post_id ) ) {

            the_row();

            echo '<ul>';

            foreach( $acf_sub_fields as $acf_subfield ) {

              $sub_field = get_sub_field( $acf_subfield['name'] );

              echo '<li>';

              if( is_object( $sub_field ) or  is_array( $sub_field ) ) {

                print_r( $sub_field );

              } else {

                echo $sub_field;

              }

              echo '</li>';

            }

            echo '</ul>';

          }

          echo '</ul>';

        }

      } else {

        $field = get_field( $acf_field_name , $post_id );

        if( is_object( $field ) or  is_array( $field ) ) {

          echo '<span>';

          print_r( $field );

          echo '</span>';

        } else {

          echo $field;

        }

      }

    }

  }

}

endif;
