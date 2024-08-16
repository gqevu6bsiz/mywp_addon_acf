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
      'github_releases' => 'https://github.com/gqevu6bsiz/mywp_addon_acf/releases',
      'github_release_latest' => 'https://api.github.com/repos/gqevu6bsiz/mywp_addon_acf/releases/latest',
    );

    $plugin_info = apply_filters( 'mywp_acf_plugin_info' , $plugin_info );

    return $plugin_info;

  }

  public static function is_enable_acf() {

    if( class_exists( 'ACF' ) ) {

      return true;

    }

    return false;

  }

  public static function get_acf_field_groups( $args = array() ) {

    if( ! self::is_enable_acf() ) {

      return false;

    }

    $acf_field_groups = acf_get_field_groups( $args );

    if( empty( $acf_field_groups ) ) {

      return false;

    }

    if( ! is_array( $acf_field_groups ) ) {

      return false;

    }

    return $acf_field_groups;

  }

  public static function get_acf_all_fields( $args = array() ) {

    $acf_field_groups = self::get_acf_field_groups( $args );

    if( empty( $acf_field_groups ) ) {

      return false;

    }

    $acf_all_fields = array();

    foreach( $acf_field_groups as $acf_field_group ) {

      $acf_fields = acf_get_fields( $acf_field_group );

      if( empty( $acf_fields ) ) {

        continue;

      }

      if( ! is_array( $acf_fields ) ) {

        continue;

      }

      foreach( $acf_fields as $acf_field ) {

        $acf_all_fields[] = $acf_field;

      }

    }

    return $acf_all_fields;

  }

  public static function get_acf_fields_by_post_type( $post_type = false ) {

    if( empty( $post_type ) ) {

      return false;

    }

    if( is_array( $post_type ) ) {

      return false;

    }

    if( is_object( $post_type ) ) {

      return false;

    }

    $post_type = strip_tags( $post_type );

    $mywp_cache = new MywpCache( "MywpACFApi_get_acf_fields_by_post_type_{$post_type}" );

    $cache = $mywp_cache->get_cache();

    if( ! empty( $cache ) ) {

      return $cache;

    }

    $args = array( 'post_type' => $post_type );

    $acf_all_fields = self::get_acf_all_fields( $args );

    $mywp_cache->update_cache( $acf_all_fields );

    return $acf_all_fields;

  }

  public static function get_acf_field( $field_key = false ) {

    if( empty( $field_key ) ) {

      return false;

    }

    if( is_array( $field_key ) ) {

      return false;

    }

    if( is_object( $field_key ) ) {

      return false;

    }

    $field_key = strip_tags( $field_key );

    $mywp_cache = new MywpCache( "MywpACFApi_get_acf_field_{$field_key}" );

    $cache = $mywp_cache->get_cache();

    if( ! empty( $cache ) ) {

      return $cache;

    }

    $acf_all_fields = self::get_acf_all_fields();

    if( empty( $acf_all_fields ) ) {

      return false;

    }

    $found_acf_field = false;

    foreach( $acf_all_fields as $acf_field ) {

      if( (string) $acf_field['key'] === (string) $field_key ) {

        $found_acf_field = $acf_field;

        break;

      }

    }

    $mywp_cache->update_cache( $found_acf_field );

    return $found_acf_field;

  }

  public static function print_field_value( $acf_field , $post_id ) {

    if( empty( $acf_field['type'] ) ) {

      return false;

    }

    if( empty( $post_id ) ) {

      return false;

    }

    $post_id = (int) $post_id;

    $field_value = acf_get_value( $post_id , $acf_field );

    if( empty( $field_value ) ) {

      return false;

    }

    if( ! empty( $acf_field['prepend'] ) ) {

      printf( '<span class="prepend">%s</span>' , esc_html( $acf_field['prepend'] ) );

    }

    echo '<span class="field-value">';

    if( in_array( $acf_field['type'] , array( 'text' , 'textarea' , 'number' , 'range' , 'email' , 'url' ) , true ) ) {

      if( is_array( $field_value ) or is_object( $field_value ) ) {

        printf( '<textarea class="large-text" readonly="readonly">%s</textarea>' , print_r( $field_value , true ) );

      } else {

        echo esc_html( $field_value );

      }

    } elseif( in_array( $acf_field['type'] , array( 'password' ) , true ) ) {

      echo '**********';

    } elseif( in_array( $acf_field['type'] , array( 'image' ) , true ) ) {

      if( is_array( $field_value ) ) {

        return false;

      }

      if( is_object( $field_value ) ) {

        return false;

      }

      $attachment_image_src = wp_get_attachment_image_src( $field_value , $acf_field['preview_size'] );

      if( ! empty( $attachment_image_src[0] ) ) {

        printf( '<a href="%s"><img src="%s" style="%s" /></a>' , esc_url( $attachment_image_src[0] ) , esc_url( $attachment_image_src[0] ) , esc_attr( 'max-width:100%;' ) );

      }

    } elseif( in_array( $acf_field['type'] , array( 'file' ) , true ) ) {

      if( is_array( $field_value ) ) {

        return false;

      }

      if( is_object( $field_value ) ) {

        return false;

      }

      $attachment = acf_get_attachment( $field_value );

      if( ! empty( $attachment['url'] ) ) {

        printf( '<a href="%s" target="_blank">%s</a>' , esc_url( $attachment['url'] ) , esc_url( $attachment['url'] ) );

      }

    } elseif( in_array( $acf_field['type'] , array( 'wysiwyg' ) , true ) ) {

      if( is_array( $field_value ) ) {

        return false;

      }

      if( is_object( $field_value ) ) {

        return false;

      }

      printf( '<textarea class="large-text" readonly="readonly">%s</textarea>' , esc_attr( $field_value ) );

    } elseif( in_array( $acf_field['type'] , array( 'oembed' ) , true ) ) {

      if( is_array( $field_value ) ) {

        return false;

      }

      if( is_object( $field_value ) ) {

        return false;

      }

      printf( '<a href="%s" target="_blank">%s</a>' , esc_url( $field_value ) , esc_url( $field_value ) );

    } elseif( in_array( $acf_field['type'] , array( 'select' , 'checkbox' , 'radio' , 'button_group' ) , true ) ) {

      $is_multiple = false;

      if( ! empty( $acf_field['multiple'] ) ) {

        $is_multiple = true;

      }

      if( $acf_field['type'] === 'checkbox' ) {

        $is_multiple = true;

      }

      if( $is_multiple ) {

        if( ! is_array( $field_value ) ) {

          return false;

        }

        echo '<ul>';

        foreach( $field_value as $value ) {

          if( isset( $acf_field['choices'][ $value ] ) ) {

            printf( '<li>%s</li>' , esc_html( $acf_field['choices'][ $value ] ) );

          }

        }

        echo '</ul>';

      } else {

        if( isset( $acf_field['choices'][ $field_value ] ) ) {

          echo esc_html( $acf_field['choices'][ $field_value ] );

        }

      }

    } elseif( in_array( $acf_field['type'] , array( 'true_false' ) , true ) ) {

      if( empty( $field_value ) ) {

        return false;

      }

      echo esc_html( $acf_field['message'] );

    } elseif( in_array( $acf_field['type'] , array( 'link' ) , true ) ) {

      if( ! is_array( $field_value ) ) {

        return false;

      }

      printf( '<a href="%s">%s</a>' , esc_url( $field_value['url'] ) , esc_html( $field_value['title'] ) );

    } elseif( in_array( $acf_field['type'] , array( 'post_object' , 'page_link' , 'relationship' ) , true ) ) {

      $is_multiple = false;

      if( ! empty( $acf_field['multiple'] ) ) {

        $is_multiple = true;

      }

      if( in_array( $acf_field['type'] , array( 'page_link' , 'relationship' ) , true ) ) {

        $is_multiple = true;

      }

      if( $is_multiple ) {

        if( ! is_array( $field_value ) ) {

          return false;

        }

        echo '<ul>';

        foreach( $field_value as $value ) {

          if( is_array( $value ) ) {

            continue;

          }

          if( is_object( $value ) ) {

            continue;

          }

          printf( '<li>%s</li>' , esc_html( get_the_title( $value ) ) );

        }

        echo '</ul>';

      } else {

        if( is_array( $field_value ) ) {

          return false;

        }

        if( is_object( $field_value ) ) {

          return false;

        }

        printf( '%s' , esc_html( get_the_title( $field_value ) ) );

      }

    } elseif( in_array( $acf_field['type'] , array( 'taxonomy' ) , true ) ) {

      $is_multiple = false;

      if( in_array( $acf_field['field_type'] , array( 'checkbox' , 'multi_select' ) , true ) ) {

        $is_multiple = true;

      }

      if( $is_multiple ) {

        if( ! is_array( $field_value ) ) {

          return false;

        }

        echo '<ul>';

        foreach( $field_value as $value ) {

          if( is_array( $value ) ) {

            continue;

          }

          if( is_object( $value ) ) {

            continue;

          }

          $term = get_term( $value , $acf_field['taxonomy'] );

          if( ! empty( $term->name ) ) {

            printf( '<li>%s</li>' , esc_html( $term->name ) );

          }

        }

        echo '</ul>';

      } else {

        if( is_array( $field_value ) ) {

          return false;

        }

        if( is_object( $field_value ) ) {

          return false;

        }

        $term = get_term( $field_value , $acf_field['taxonomy'] );

        if( ! empty( $term->name ) ) {

          echo esc_html( $term->name );

        }

      }

    } elseif( in_array( $acf_field['type'] , array( 'user' ) , true ) ) {

      $is_multiple = false;

      if( ! empty( $acf_field['multiple'] ) ) {

        $is_multiple = true;

      }

      if( $is_multiple ) {

        if( ! empty( $field_value ) && is_array( $field_value ) ) {

          if( ! is_array( $field_value ) ) {

            return false;

          }

          echo '<ul>';

          foreach( $field_value as $value ) {

            if( is_array( $value ) ) {

              continue;

            }

            if( is_object( $value ) ) {

              continue;

            }

            $user_data = get_userdata( $value );

            if( ! empty( $user_data ) ) {

              printf( '<li>[%s] %s</li>' , esc_html( $user_data->ID ) , esc_html( $user_data->display_name ) );

            }

          }

          echo '</ul>';

        }

      } else {

        if( is_array( $field_value ) ) {

          return false;

        }

        if( is_object( $field_value ) ) {

          return false;

        }

        $user_data = get_userdata( $field_value );

        if( ! empty( $user_data ) ) {

          printf( '[%s] %s' , esc_html( $user_data->ID ) , esc_html( $user_data->display_name ) );

        }

      }

    } elseif( in_array( $acf_field['type'] , array( 'date_picker' , 'date_time_picker' , 'time_picker' ) , true ) ) {

      if( is_array( $field_value ) ) {

        return false;

      }

      if( is_object( $field_value ) ) {

        return false;

      }

      $acf_format_date = acf_format_date( $field_value , $acf_field['display_format'] );

      if( ! empty( $acf_format_date ) ) {

        echo esc_html( $acf_format_date );

      }

    } elseif( in_array( $acf_field['type'] , array( 'color_picker' ) , true ) ) {

      if( is_array( $field_value ) ) {

        return false;

      }

      if( is_object( $field_value ) ) {

        return false;

      }

      $style = 'display: block; width: 100%; height: 100%; background: ' . esc_html( $field_value ) . ';';

      printf( '<span class="color" style="%s"><span style="%s"></span></span>' , esc_attr( 'display:inline-block; width: 20px; height: 20px; border: 1px solid #ccc; background: #fff; padding: 1px;' ) , esc_attr( $style ) );

    } else {

      //var_dump($field_value);
      //print_r($acf_field);

    }

    echo '</span>';

    if( ! empty( $acf_field['append'] ) ) {

      printf( '<span class="append">%s</span>' , esc_html( $acf_field['append'] ) );

    }

  }

}

endif;
