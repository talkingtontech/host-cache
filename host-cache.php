<?php
/**
 * Plugin Name: Host Cache
 * Description: Downloads WordPress plugins, themes, and updates from the official repo through a proxy cache.
 * Version: 0.1.0
 * Author: Talkington Tech
 * Author URI: http://talkingtontech.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

class Host_Cache {

  public $options;

  public function __construct( $args = array() ) {
    $this->options = wp_parse_args( $args, array(
      'host' => 'wpcache.host',
      'ssl' => false,
      'catchall' => false,
      'arg_nocache' => 'nocache'
    ) );

    add_action( 'init', array( $this, 'init' ), 10 );
  }

  public function init() {
    add_filter( 'pre_set_site_transient_update_core', array( $this, 'core_updates' ), 10 );
    add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'plugin_updates' ), 10 );
    add_filter( 'pre_set_site_transient_update_themes', array( $this, 'theme_updates' ), 10 );

    add_filter( 'plugins_api_result', array( $this, 'plugins_api_result' ), 10, 2 );
    add_filter( 'themes_api_result', array( $this, 'themes_api_result' ), 10, 2 );

    add_filter( 'pre_http_request', array( $this, 'http_request_catchall' ), 10, 3 );
    add_filter( 'http_request_host_is_external', array( $this, 'http_request_always_allow' ), 10, 3 );
  }

  private function cached_download_url( $url ) {
    $scheme = $this->options['ssl'] ? 'https' : 'http';
    $url = str_replace( array( 'downloads.wordpress.org', 'wordpress.org' ), $this->options['host'], $url );
    $url = preg_replace( '#^\w+://#', $scheme . '://', $url );

    if ( !preg_match( '/\d+(\.\d+)+/m', $url ) ) {
      $url = add_query_arg( $this->options['arg_nocache'], 1, $url );
    }

    return $url;
  }

  public function core_updates( $transient ) {
    if ( !isset( $transient->updates ) ) {
      return $transient;
    }

    foreach ( $transient->updates as $item => $update_details ) {
      if ( $this->is_download_url( $update_details->download ) ) {
        $update_details->download = $this->cached_download_url( $update_details->download );
      }

      if ( isset( $update_details->packages ) ) {
        $packages = get_object_vars( $update_details->packages );

        foreach ( $packages as $package => $value ) {
          if ( !$this->is_download_url( $value ) ) {
            continue;
          }

          $update_details->packages->$package = $this->cached_download_url( $value );
        }
      }

      $transient->updates[$item] = $update_details;
    }

    return $transient;
  }

  public function http_request_always_allow( $result, $host, $url ) {
    if ( $this->options['host'] === $host ) {
      return true;
    }

    return $result;
  }

  public function http_request_catchall( $result, $request_args, $url ) {
    if ( !$this->options['catchall'] || !$this->is_download_url( $url ) ) {
      return $result;
    }

    $url = $this->cached_download_url( $url );

    $http = _wp_http_get_object();
    return $http->request( $url, $request_args );
  }

  private function is_download_url( $url ) {
    if ( false !== strpos( $url, 'downloads.wordpress.org' ) ) {
      return true;
    } else if ( false !== strpos( $url, 'wordpress.org/themes/download/' ) ) {
      return true;
    }

    return false;
  }

  public function plugin_updates( $transient ) {
    if ( !isset( $transient->response ) ) {
      return $transient;
    }

    foreach ( $transient->response as $item => $update_details ) {
      if ( !$this->is_download_url( $update_details->package ) ) {
        continue;
      }

      $update_details->package = $this->cached_download_url( $update_details->package );
      $transient->response[$item] = $update_details;
    }

    return $transient;
  }

  public function plugins_api_result( $result, $action ) {
    if ( is_wp_error( $result ) ) {
      return $result;
    }

    if ( 'plugin_information' === $action ) {
      if ( $this->is_download_url( $result->download_link ) ) {
        $result->download_link = $this->cached_download_url( $result->download_link );
      }
    }

    return $result;
  }

  public function theme_updates( $transient ) {
    if ( !isset( $transient->response ) ) {
      return $transient;
    }

    foreach ( $transient->response as $item => $update_details ) {
      if ( !$this->is_download_url( $update_details['package'] ) ) {
        continue;
      }

      $update_details['package'] = $this->cached_download_url( $update_details['package'] );
      $transient->response[$item] = $update_details;
    }

    return $transient;
  }

  public function themes_api_result( $result, $action ) {
    if ( is_wp_error( $result ) ) {
      return $result;
    }

    if ( 'theme_information' === $action ) {
      if ( $this->is_download_url( $result->download_link ) ) {
        $result->download_link = $this->cached_download_url( $result->download_link );
      }
    }

    return $result;
  }

}

global $host_cache;
$host_cache = new Host_Cache();