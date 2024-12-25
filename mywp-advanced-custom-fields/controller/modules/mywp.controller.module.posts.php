<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpControllerAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpControllerModuleAcfPosts' ) ) :

final class MywpControllerModuleAcfPosts extends MywpControllerAbstractModule {

  static protected $id = 'acf_posts';

  static private $post_type = '';

  public static function mywp_wp_loaded() {

    if( ! self::is_do_controller() ) {

      return false;

    }

    add_filter( 'mywp_setting_post_types' , array( __CLASS__ , 'mywp_setting_post_types' ) );

    add_filter( 'mywp_setting_admin_posts_get_available_list_columns' , array( __CLASS__ , 'mywp_setting_admin_posts_get_available_list_columns' ) , 10 , 2 );

    add_action( 'mywp_ajax' , array( __CLASS__ , 'mywp_ajax' ) , 1001 );

    add_action( 'load-edit.php' , array( __CLASS__ , 'load_edit' ) , 1001 );

  }

  public static function mywp_setting_post_types( $post_types ) {

    if( isset( $post_types['acf-field-group'] ) ) {

      unset( $post_types['acf-field-group'] );

    }

    return $post_types;

  }

  public static function mywp_setting_admin_posts_get_available_list_columns( $available_list_columns , $list_column_id ) {

    if( empty( $available_list_columns ) ) {

      return $available_list_columns;

    }

    if( ! is_array( $available_list_columns ) ) {

      return $available_list_columns;

    }

    $new_available_list_columns = array();

    foreach( $available_list_columns as $available_list_column_key => $available_list_column ) {

      $new_available_list_columns[ $available_list_column_key ] = $available_list_column;

      if( $available_list_column_key === 'other' ) {

        $new_available_list_columns['acf'] = array(
          'title' => 'Advanced Custom Fields',
          'columns' => array(),
        );

      }

    }

    $old_available_list_columns = $available_list_columns;

    $available_list_columns = $new_available_list_columns;

    $acf_fields = MywpACFApi::get_acf_fields_by_post_type( $list_column_id );

    if( empty( $acf_fields ) ) {

      return $available_list_columns;

    }

    $columns = array();

    foreach( $acf_fields as $acf_field ) {

      $field_name = $acf_field['name'];

      if( isset( $available_list_columns['deprecated']['columns'][ $field_name ] ) ) {

        unset( $available_list_columns['deprecated']['columns'][ $field_name ] );

      }

      if( isset( $available_list_columns['deprecated']['columns'][ '_' . $field_name ] ) ) {

        unset( $available_list_columns['deprecated']['columns'][ '_' . $field_name ] );

      }

      /*
      if( isset( $available_list_columns['custom_fields']['columns']['mywp_custom_field_column_' . $field_name] ) ) {

        unset( $available_list_columns['custom_fields']['columns']['mywp_custom_field_column_' . $field_name] );

      }

      if( isset( $available_list_columns['custom_fields']['columns']['mywp_custom_field_column_' . '_' . $field_name] ) ) {

        unset( $available_list_columns['custom_fields']['columns']['mywp_custom_field_column_' . '_' . $field_name] );

      }
      */

      if( in_array( $acf_field['type'] , array( 'message' , 'accordion' , 'tab' ) , true ) ) {

        continue;

      }

      $column_id = $acf_field['key'];

      $columns[ 'mywp_acf_column_' . $column_id ] = array(
        'id' => 'mywp_acf_column_' . $column_id,
        'type' => 'acf',
        'sort' => '',
        'orderby' => '',
        'default_title' => $acf_field['label'],
        'title' => $acf_field['label'],
        'width' => '',
      );

    }

    $available_list_columns['acf']['columns'] = $columns;

    return $available_list_columns;

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

    add_filter( 'mywp_controller_admin_posts_get_post_statuses' , array( __CLASS__ , 'mywp_controller_admin_posts_get_post_statuses' ) , 9 , 2 );

    add_filter( 'mywp_controller_admin_posts_custom_search_filter_fields-' . self::$post_type , array( __CLASS__ , 'mywp_controller_admin_posts_custom_search_filter_fields' ) , 9 );

    add_action( 'mywp_controller_admin_posts_custom_search_filter-' . self::$post_type , array( __CLASS__ , 'mywp_controller_admin_posts_custom_search_filter' ) , 9 , 2 );

    add_action( 'mywp_controller_admin_posts_custom_search_filter_form_field_content' , array( __CLASS__ , 'mywp_controller_admin_posts_custom_search_filter_form_field_content' ) , 9 );

    add_filter( 'mywp_controller_admin_posts_custom_search_filter_fields_after-' . self::$post_type , array( __CLASS__ , 'mywp_controller_admin_posts_custom_search_filter_fields_after' ) , 9 , 2 );

  }

  public static function manage_column_body( $column_id , $post_id ) {

    if( ! self::is_do_function( __FUNCTION__ ) ) {

      return false;

    }

    if( strpos( $column_id , 'mywp_acf_column_' ) !== false ) {

      $acf_field_key = str_replace( 'mywp_acf_column_' , '' , $column_id );

      $acf_field = MywpACFApi::get_acf_field( $acf_field_key );

      if( empty( $acf_field['type'] ) ) {

        return false;

      }

      if( in_array( $acf_field['type'] , array( 'message' , 'accordion' , 'tab' ) , true ) ) {

        return false;

      }

      if( in_array( $acf_field['type'] , array( 'group' ) , true ) ) {

        $acf_sub_fields = $acf_field['sub_fields'];

        if( have_rows( $acf_field['name'] , $post_id ) ) {

          echo '<ul>';

          while( have_rows( $acf_field['name'] , $post_id ) ) {

            the_row();

            foreach( $acf_sub_fields as $acf_subfield ) {

              echo '<li>';

              //$field_value = get_sub_field( $acf_subfield['name'] );

              MywpACFApi::print_field_value( $acf_subfield , $post_id );

              echo '</li>';

            }

          }

          echo '</ul>';

        }

      } else {

        //$field_value = get_field( $acf_field['name'] , $post_id );

        MywpACFApi::print_field_value( $acf_field , $post_id );

      }

    }

    self::after_do_function( __FUNCTION__ );

  }

  public static function mywp_controller_admin_posts_get_post_statuses( $post_statuses , $post_type ) {

    if( isset( $post_statuses['acf-disabled'] ) ) {

      unset( $post_statuses['acf-disabled'] );

    }

    return $post_statuses;

  }

  public static function mywp_controller_admin_posts_custom_search_filter_fields( $custom_search_filter_fields ) {

    $acf_fields = MywpACFApi::get_acf_fields_by_post_type( self::$post_type );

    if( empty( $acf_fields ) ) {

      return $custom_search_filter_fields;

    }

    foreach( $acf_fields as $acf_field ) {

      $custom_search_filter_id = sprintf( 'mywp_custom_search_acf_%s' , $acf_field['key'] );

      $custom_search_filter_field = array(
        'id' => $custom_search_filter_id,
        'title' => $acf_field['label'],
      );

      if( in_array( $acf_field['type'] , array( 'text' , 'textarea' , 'email' , 'url' , 'wysiwyg' , 'link' ) , true ) ) {

        $custom_search_filter_field['type'] = 'text';

      } elseif( in_array( $acf_field['type'] , array( 'number' , 'range' ) , true ) ) {

        $custom_search_filter_field['type'] = 'number';

      } elseif( in_array( $acf_field['type'] , array( 'select' , 'checkbox' , 'radio' , 'button_group' ) , true ) ) {

        $custom_search_filter_field['type'] = 'select';

        $custom_search_filter_field['choices'] = $acf_field['choices'];

      } elseif( in_array( $acf_field['type'] , array( 'true_false' ) , true ) ) {

        $custom_search_filter_field['type'] = 'select';

        $custom_search_filter_field['choices'] = array(
          'unchecked' => __( 'Unchecked' ),
          'checked' => __( 'Checked' , 'acf' ),
        );

      } elseif( in_array( $acf_field['type'] , array( 'post_object' , 'page_link' , 'relationship' ) , true ) ) {

        $custom_search_filter_field['type'] = 'number';

      } elseif( in_array( $acf_field['type'] , array( 'taxonomy' ) , true ) ) {

        $custom_search_filter_field['type'] = 'select';

        $taxonomy = get_taxonomy( $acf_field['taxonomy'] );

        if( ! empty( $taxonomy ) ) {

          $terms = get_terms( $taxonomy->name , array( 'hide_empty' => false ) );

          if( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

            foreach( $terms as $term ) {

              $custom_search_filter_field['choices'][ $term->term_id ] = $term->name;

            }

          }

        }

      } elseif( in_array( $acf_field['type'] , array( 'user' ) , true ) ) {

        $custom_search_filter_field['type'] = 'number';

      } elseif( in_array( $acf_field['type'] , array( 'date_picker' ) , true ) ) {

        $custom_search_filter_field['type'] = 'date';

      } elseif( in_array( $acf_field['type'] , array( 'date_time_picker' ) , true ) ) {

        $custom_search_filter_field['type'] = 'custom';

        $custom_search_filter_field['placeholder'] = '0000-00-00 00:00:00';

        $custom_search_filter_field['acf_field'] = $acf_field;

      } elseif( in_array( $acf_field['type'] , array( 'time_picker' ) , true ) ) {

        $custom_search_filter_field['type'] = 'custom';

        $custom_search_filter_field['placeholder'] = '00:00:00';

        $custom_search_filter_field['acf_field'] = $acf_field;

      }

      $custom_search_filter_fields[ $custom_search_filter_id ] = $custom_search_filter_field;

    }

    return $custom_search_filter_fields;

  }

  public static function mywp_controller_admin_posts_custom_search_filter_form_field_content( $custom_search_filter_field ) {

    if( empty( $custom_search_filter_field['acf_field'] ) ) {

      return false;

    }

    $acf_field = $custom_search_filter_field['acf_field'];

    if( empty( $acf_field['type'] ) ) {

      return false;

    }

    if( in_array( $acf_field['type'] , array( 'date_time_picker' , 'time_picker' ) , true ) ) {

      ?>

      <label class="from">
        <?php _e( 'From' ); ?>
        <input type="text" name="<?php echo esc_attr( $custom_search_filter_field['input_name'] ); ?>[from]" value="<?php echo esc_attr( $custom_search_filter_field['input_value']['from'] ); ?>" placeholder="<?php echo esc_attr( $custom_search_filter_field['placeholder'] ); ?>" class="from" />
      </label>

      <label class="to">
        <?php _e( 'To' ); ?>
        <input type="text" name="<?php echo esc_attr( $custom_search_filter_field['input_name'] ); ?>[to]" value="<?php echo esc_attr( $custom_search_filter_field['input_value']['to'] ); ?>" placeholder="<?php echo esc_attr( $custom_search_filter_field['placeholder'] ); ?>" class="to" />
      </label>

      <?php

    }

  }

  public static function mywp_controller_admin_posts_custom_search_filter_fields_after( $custom_search_filter_fields , $custom_search_filter_requests ) {

    foreach( $custom_search_filter_fields as $custom_search_filter_field_id => $custom_search_filter_field ) {

      if( empty( $custom_search_filter_field['acf_field']['type'] ) ) {

        continue;

      }

      $acf_field = $custom_search_filter_field['acf_field'];

      if( empty( $acf_field['type'] ) ) {

        continue;

      }

      if( in_array( $acf_field['type'] , array( 'date_time_picker' , 'time_picker' ) , true ) ) {

        $filteterd = false;

        $from = '';

        if( ! empty( $custom_search_filter_requests[ $custom_search_filter_field_id ]['from'] ) ) {

          $from = MywpHelper::sanitize_text( $custom_search_filter_requests[ $custom_search_filter_field_id ]['from'] );

          $filteterd = true;

        }

        $to = '';

        if( ! empty( $custom_search_filter_requests[ $custom_search_filter_field_id ]['to'] ) ) {

          $to = MywpHelper::sanitize_text( $custom_search_filter_requests[ $custom_search_filter_field_id ]['to'] );

          $filteterd = true;

        }

        $custom_search_filter_fields[ $custom_search_filter_field_id ]['input_value'] = array(
          'from' => $from,
          'to' => $to,
        );

        $custom_search_filter_fields[ $custom_search_filter_field_id ]['filtered'] = $filteterd;

      }

    }

    return $custom_search_filter_fields;

  }

  public static function mywp_controller_admin_posts_custom_search_filter( $query , $custom_search_filter_requests ) {

    $meta_query = array();

    foreach( $custom_search_filter_requests as $custom_search_filter_request_key => $custom_search_filter_request ) {

      if( strpos( $custom_search_filter_request_key , 'mywp_custom_search_acf_' ) === false ) {

        continue;

      }

      $acf_field_key = str_replace( 'mywp_custom_search_acf_' , '' , $custom_search_filter_request_key );

      $acf_field = MywpACFApi::get_acf_field( $acf_field_key );

      if( empty( $acf_field ) ) {

        continue;

      }

      if( in_array( $acf_field['type'] , array( 'text' , 'textarea' , 'email' , 'url' , 'wysiwyg' , 'link' ) , true ) ) {

        $value = MywpHelper::sanitize_text( $custom_search_filter_request );

        if( ! empty( $value ) ) {

          $meta_query[] = array(
            'key' => $acf_field['name'],
            'value' => $value,
            'compare' => 'LIKE',
          );

        }

      } elseif( in_array( $acf_field['type'] , array( 'number' , 'range' ) , true ) ) {

        $value = MywpHelper::sanitize_number( $custom_search_filter_request );

        if( ! empty( $value ) ) {

          $meta_query[] = array(
            'key' => $acf_field['name'],
            'value' => $custom_search_filter_request,
            'compare' => '=',
          );

        }

      } elseif( in_array( $acf_field['type'] , array( 'select' ) , true ) ) {

        $value = MywpHelper::sanitize_text( $custom_search_filter_request );

        if( ! empty( $value ) ) {

          if( ! empty( $acf_field['multiple'] ) ) {

            $meta_query[] = array(
              'key' => $acf_field['name'],
              'value' => sprintf( ':"%s"' , $value ),
              'compare' => 'LIKE',
            );

          } else {

            $meta_query[] = array(
              'key' => $acf_field['name'],
              'value' => $value,
              'compare' => '=',
            );

          }

        }

      } elseif( in_array( $acf_field['type'] , array( 'checkbox' ) , true ) ) {

        $value = MywpHelper::sanitize_text( $custom_search_filter_request );

        if( ! empty( $value ) ) {

          $meta_query[] = array(
            'key' => $acf_field['name'],
            'value' => sprintf( ':"%s"' , $value ),
            'compare' => 'LIKE',
          );

        }

      } elseif( in_array( $acf_field['type'] , array( 'radio' , 'button_group' ) , true ) ) {

        $value = MywpHelper::sanitize_text( $custom_search_filter_request );

        if( ! empty( $value ) ) {

          $meta_query[] = array(
            'key' => $acf_field['name'],
            'value' => $value,
            'compare' => '=',
          );

        }

      } elseif( in_array( $acf_field['type'] , array( 'true_false' ) , true ) ) {

        $value = MywpHelper::sanitize_text( $custom_search_filter_request );

        if( ! empty( $value ) ) {

          if( $value === 'unchecked' ) {

            $meta_query[] = array(
              'relation' => 'OR',
              array(
                'key' => $acf_field['name'],
                'value' => 0,
                'compare' => '=',
              ),
              array(
                'key' => $acf_field['name'],
                'compare' => 'NOT EXISTS',
              )
            );

          } elseif( $value === 'checked' ) {

            $meta_query[] = array(
              'key' => $acf_field['name'],
              'value' => 1,
              'compare' => '=',
            );

          }

        }

      } elseif( in_array( $acf_field['type'] , array( 'post_object' , 'page_link' , 'relationship' ) , true ) ) {

        $value = MywpHelper::sanitize_number( $custom_search_filter_request );

        if( ! empty( $value ) ) {

          if( ! empty( $acf_field['multiple'] ) or in_array( $acf_field['type'] , array( 'page_link' , 'relationship' ) , true ) ) {

            $meta_query[] = array(
              'key' => $acf_field['name'],
              'value' => sprintf( ':"%s"' , $value ),
              'compare' => 'LIKE',
            );

          } else {

            $meta_query[] = array(
              'key' => $acf_field['name'],
              'value' => $value,
              'compare' => '=',
            );

          }

        }

      } elseif( in_array( $acf_field['type'] , array( 'taxonomy' ) , true ) ) {

        $value = MywpHelper::sanitize_number( $custom_search_filter_request );

        if( ! empty( $value ) ) {

          if( ! empty( $acf_field['load_terms'] ) or ! empty( $acf_field['save_terms'] ) ) {

            if( ! empty( $acf_field['multiple'] ) or in_array( $acf_field['field_type'] , array( 'checkbox' , 'multi_select' ) , true ) ) {

              $meta_query[] = array(
                'key' => $acf_field['name'],
                'value' => sprintf( ':"%s"' , $value ),
                'compare' => 'LIKE',
              );

            } else {

              $meta_query[] = array(
                'key' => $acf_field['name'],
                'value' => $value,
                'compare' => '=',
              );

            }

          } else {

            if( ! empty( $acf_field['multiple'] ) or in_array( $acf_field['field_type'] , array( 'checkbox' , 'multi_select' ) , true ) ) {

              $meta_query[] = array(
                'key' => $acf_field['name'],
                'value' => sprintf( ':"%s"' , $value ),
                'compare' => 'LIKE',
              );

            } else {

              $meta_query[] = array(
                'key' => $acf_field['name'],
                'value' => $value,
                'compare' => '=',
              );

            }

          }

        }

      } elseif( in_array( $acf_field['type'] , array( 'user' ) , true ) ) {

        $value = MywpHelper::sanitize_number( $custom_search_filter_request );

        if( ! empty( $value ) ) {

          if( ! empty( $acf_field['multiple'] ) ) {

            $meta_query[] = array(
              'key' => $acf_field['name'],
              'value' => sprintf( ':"%s"' , $value ),
              'compare' => 'LIKE',
            );

          } else {

            $meta_query[] = array(
              'key' => $acf_field['name'],
              'value' => $value,
              'compare' => '=',
            );

          }

        }

      } elseif( in_array( $acf_field['type'] , array( 'date_picker' ) , true ) ) {

        foreach( array( 'from' , 'to' ) as $date_key ) {

          if( empty( $custom_search_filter_request[ $date_key ] ) ) {

            continue;

          }

          $date = MywpHelper::sanitize_date( $custom_search_filter_request[ $date_key ] );

          if( empty( $date ) ) {

            continue;

          }

          $acf_format_date = acf_format_date( $date , 'Ymd' );

          $date_q = array(
            'key' => $acf_field['name'],
            'value' => $acf_format_date,
          );

          if( $date_key === 'from' ) {

            $date_q['compare'] = '>=';

          } elseif( $date_key === 'to' ) {

            $date_q['compare'] = '<=';

          }

          $meta_query[] = $date_q;

        }

      } elseif( in_array( $acf_field['type'] , array( 'date_time_picker' ) , true ) ) {

        foreach( array( 'from' , 'to' ) as $date_key ) {

          if( empty( $custom_search_filter_request[ $date_key ] ) ) {

            continue;

          }

          $value = MywpHelper::sanitize_text( $custom_search_filter_request[ $date_key ] );

          if( empty( $value ) ) {

            continue;

          }

          $timestamp = strtotime( $value );

          if( empty( $timestamp ) ) {

            continue;

          }

          $date_q = array(
            'key' => $acf_field['name'],
            'value' => date( 'Y-m-d H:i:s' , $timestamp ),
            'type' => 'DATETIME',
          );

          if( $date_key === 'from' ) {

            $date_q['compare'] = '>=';

          } elseif( $date_key === 'to' ) {

            $date_q['compare'] = '<=';

          }

          $meta_query[] = $date_q;

        }

      } elseif( in_array( $acf_field['type'] , array( 'time_picker' ) , true ) ) {

        foreach( array( 'from' , 'to' ) as $date_key ) {

          if( empty( $custom_search_filter_request[ $date_key ] ) ) {

            continue;

          }

          $value = MywpHelper::sanitize_text( $custom_search_filter_request[ $date_key ] );

          if( empty( $value ) ) {

            continue;

          }

          $time_array = explode( ':' , $value );

          if( empty( $time_array[0] ) ) {

            continue;

          }

          $hour = (int) $time_array[0];

          if( $hour > 23 ) {

            $hour = 0;

          }

          if( empty( $time_array[1] ) ) {

            continue;

          }

          $min = (int) $time_array[1];

          if( $min > 59 ) {

            $min = 0;

          }

          if( empty( $time_array[2] ) ) {

            continue;

          }

          $second = (int) $time_array[2];

          if( $second > 59 ) {

            $second = 0;

          }

          $time_string = sprintf( '%s:%s:%s' , $hour , $min , $second );

          $date_q = array(
            'key' => $acf_field['name'],
            'value' => $time_string,
            'type' => 'TIME',
          );

          if( $date_key === 'from' ) {

            $date_q['compare'] = '>=';

          } elseif( $date_key === 'to' ) {

            $date_q['compare'] = '<=';

          }

          $meta_query[] = $date_q;

        }

      }

    }

    if( ! empty( $meta_query ) ) {

      $query->set( 'meta_query' , wp_parse_args( $meta_query , array( 'relation' => 'AND' ) ) );

    }

  }

}

MywpControllerModuleAcfPosts::init();

endif;
