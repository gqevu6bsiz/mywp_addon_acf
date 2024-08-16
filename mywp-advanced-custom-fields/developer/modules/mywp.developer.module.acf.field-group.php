<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpDeveloperAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpDeveloperModuleAcfFieldgroup' ) ) :

final class MywpDeveloperModuleAcfFieldgroup extends MywpDeveloperAbstractModule {

  static protected $id = 'acf_field_group';

  public static function mywp_debug_renders( $debug_renders ) {

    $debug_renders[ self::$id ] = array(
      'debug_type' => 'acf',
      'title' => __( 'Field Groups'  , 'acf' ),
    );

    return $debug_renders;

  }

  protected static function mywp_debug_render() {

    global $pagenow;
    global $post;

    $is_post_edit_page = false;

    if( is_admin() && ! empty( $pagenow ) && in_array( $pagenow , array( 'post.php' , 'post-new.php' ) , true ) ) {

      $is_post_edit_page = true;

    }

    if( ! $is_post_edit_page ) {

      printf( '<p>%s</p>' , __( 'Field groups debug on only the post edit screen.' , 'mywp-acf' ) );

      return false;

    }

    if( empty( $post ) or empty( $post->ID ) ) {

      printf( '<p>%s</p>' , __( 'Post not found.' , 'my-wp' ) );

      return false;

    }

    if( ! MywpACFApi::is_enable_acf() ) {

      printf( '<p>%s</p>' , __( 'ACF is not activated.' , 'mywp-acf' ) );

      return false;

    }

    $current_acf_field_groups = array();

    $acf_field_groups = MywpACFApi::get_acf_field_groups();

    foreach( $acf_field_groups as $acf_field_group ) {

      $acf_get_field_group_visibility_args = array(
        'post_id'	=> $post->ID,
        'post_type'	=> $post->post_type,
      );

      $visibility = acf_get_field_group_visibility( $acf_field_group , array( 'post_id'	=> $post->ID , 'post_type'	=> $post->post_type ) );

      if( $visibility ) {

        $current_acf_field_groups[] = $acf_field_group;

      }

    }

    if( empty( $current_acf_field_groups ) ) {

      printf( '<p>%s</p>' , __( 'Empty current ACF Field group.' , 'mywp-acf' ) );

      return false;

    }

    echo '<table class="debug-table debug-table-acf">';

    echo '<thead>';

    echo '<tr>';

    printf( '<th>%s</th>' , '&nbsp;' );

    printf( '<th>%s</th>' , __( 'Field Groups'  , 'acf' ) );

    printf( '<th>%s</th>' , __( 'Fields'  , 'acf' ) );

    echo '</tr>';

    echo '</thead>';

    echo '<tbody>';

    foreach( $current_acf_field_groups as $acf_field_group ) {

      echo '<tr>';

      printf( '<th>[%s] %s<br />%s</th>' , esc_html( $acf_field_group['ID'] ) , esc_html( $acf_field_group['key'] ) , esc_html( $acf_field_group['title'] ) );

      echo '<td>';

      echo '<textarea readonly="readonly">';

      print_r( $acf_field_group );

      echo '</textarea>';

      echo '</td>';

      echo '<td>';

      $fields = acf_get_fields( $acf_field_group );

      if( ! empty( $fields ) ) {

        foreach( $fields as $field ) {

          echo '<textarea readonly="readonly">';

          print_r($field);

          echo '</textarea>';

        }

      }

      echo '</td>';

      echo '</tr>';

    }

    echo '</tbody>';

    echo '</table>';

  }

  public static function mywp_debug_render_footer() {

    ?>
    <style>
    .debug-table-acf tbody td {
      vertical-align: top;
    }
    </style>
    <?php

  }

}

MywpDeveloperModuleAcfFieldgroup::init();

endif;
