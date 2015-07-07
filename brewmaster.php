<?php
/*
 * Copyright (c) 2015, Nathaniel Eklund 
 * Plugin Name: BrewMaster
 * Version: 1.0
 * Plugin URI: http://neklund.net
 * Description: BrewMaster lets you manage and display your beers and batches.
 * Author: Nate Eklund
 * Author URI: http://neklund.net
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
 * Indicates that a clean exit occured. Handled by set_exception_handler
 */
if (!class_exists('E_Clean_Exit'))
{
  class E_Clean_Exit extends RuntimeException
  {

  }
}

/**
 * Loads the BrewMaster plugin
 */
if (!class_exists('bmLoader'))
{
  class bmLoader
  {
    var $version     = '0.2';
    var $dbversion   = '0.2';
    var $minimum_WP  = '3.5';
    var $options     = '';

    function bmLoader()
    {
      // Stop the plugin if we missed the requirements
      if (( !$this->required_version() ))
        return;

      // Set error handler
      set_exception_handler(array(&$this, 'exception_handler'));

      $this->load_options();
      $this->define_constant();
      $this->define_tables();
      $this->load_dependencies();

      $this->plugin_name = basename(dirname(__FILE__)).'/'.basename(__FILE__);

      // Init options & tables during activation & deregister init option
      register_activation_hook( $this->plugin_name, array(&$this, 'activate') );
      register_deactivation_hook( $this->plugin_name, array(&$this, 'deactivate') );

      // Register a uninstall hook to remove all tables & option automatic
      register_uninstall_hook( $this->plugin_name, array(__CLASS__, 'uninstall') );

      //Filter hooks
      add_filter('the_content', array('bmLoader', 'filterContent'));
    }

    function required_version()
    {
      global $wp_version;

      // Check for WP version installation
      $wp_ok  =  version_compare($wp_version, $this->minimum_WP, '>=');

      if (($wp_ok == FALSE))
      {
        add_action(
          'admin_notices',
          create_function(
            '',
            'global $bm; printf (\'<div id="message" class="error"><p><strong>\' . __(\'Sorry, BrewMaster works only under WordPress %s or higher\', "brewmaster" ) . \'</strong></p></div>\', $bm->minimum_WP );'
          )
        );
        return false;
      }

      return true;
    }

    function define_tables()
    {
      global $wpdb;

      // add database pointer
      $wpdb->bmbeers     = $wpdb->prefix . 'bm_beers';
      $wpdb->bmbatches   = $wpdb->prefix . 'bm_batches';
    }

    function load_options()
    {
      // Load the options
      $this->options = get_option('bm_options');
    }

    function define_constant()
    {
      global $wp_version;

      define('BMVERSION', $this->version);
      // Minimum required database version
      define('BM_DBVERSION', $this->dbversion);

      // required for Windows & XAMPP
      define('BM_WINABSPATH', str_replace("\\", "/", ABSPATH) );

      // define URL
      define('BMFOLDER', basename( dirname(__FILE__) ) );

      define('BM_ABSPATH', trailingslashit( str_replace("\\","/", WP_PLUGIN_DIR . '/' . BMFOLDER ) ) );
      define('BM_URLPATH', trailingslashit( plugins_url( BMFOLDER ) ) );
    }

    function load_dependencies()
    {
      // Load global libraries
      require_once (dirname (__FILE__) . '/lib/bm-db.php');

      if ( is_admin() )
      {
        require_once (dirname (__FILE__) . '/admin/admin.php');
        $this->bmAdminPanel = new bmAdminPanel();
      }
    }

    function activate()
    {
      global $wpdb;
      //Starting from version 1.8.0 it's works only with PHP5.2
      if (version_compare(PHP_VERSION, '5.2.0', '<'))
      {
          deactivate_plugins($this->plugin_name); // Deactivate ourself
          wp_die("Sorry, but you can't run this plugin, it requires PHP 5.2 or higher.");
          return;
      }

      // Clean up transients
      self::remove_transients();

      include_once (dirname (__FILE__) . '/admin/install.php');

      // check for tables
      brewmaster_install();
      // remove the update message
      delete_option( 'bm_update_exists' );
    }

    /**
     * Removes all transients created by BrewMaster. Called during activation
     * and deactivation routines
     */
    static function remove_transients()
    {
      global $wpdb, $_wp_using_ext_object_cache;

      // Fetch all transients
      $query = "
          SELECT option_name FROM {$wpdb->options}
          WHERE option_name LIKE '%bm_request%'
      ";
      $transient_names = $wpdb->get_col($query);;

      // Delete all transients in the database
      $query = "
        DELETE FROM {$wpdb->options}
        WHERE option_name LIKE '%bm_request%'
      ";
      $wpdb->query($query);

      // If using an external caching mechanism, delete the cached items
      if ($_wp_using_ext_object_cache) {
        foreach ($transient_names as $transient) {
          wp_cache_delete($transient, 'transient');
          wp_cache_delete(substr($transient, 11), 'transient');
        }
      }
    }

    function deactivate()
    {
      // remove & reset the init check option
      delete_option( 'bm_init_check' );
      delete_option( 'bm_update_exists' );

      // Clean up transients
      self::remove_transients();
    }

    function uninstall()
    {
      self::remove_transients();

      include_once (dirname (__FILE__) . '/admin/install.php');
      brewmaster_uninstall();
    }

    function test_head_footer_init()
    {
      // If test-head query var exists hook into wp_head
      if ( isset( $_GET['test-head'] ) )
        add_action( 'wp_head', create_function('', 'echo \'<!--wp_head-->\';'), 99999 );

      // If test-footer query var exists hook into wp_footer
      if ( isset( $_GET['test-footer'] ) )
        add_action( 'wp_footer', create_function('', 'echo \'<!--wp_footer-->\';'), 99999 );
    }

    public static function filterContent($content)
    {
      if (preg_match('/\[BrewMaster:(list|beer|batch)( id=(?P<id>\d+))*\]/', $content, $matches))
      {
        //print_r($matches);
        switch ($matches[1])
        {
          case 'list':
            require_once(dirname(__FILE__) . "/frontend/brewery.php");
            $html = bm_beer_list_html();
            break;

          case 'beer':
            require_once(dirname(__FILE__) . "/frontend/beerdisplay.php");
            $html = bm_beer_html($matches['id']);
            break;

          case 'batch':
            require_once(dirname(__FILE__) . "/frontend/batchdisplay.php");
            $html = bm_batch_html($matches['id']);
            break;

          default:
            $html = "Unknown Brew Master page type: " . $matches[1];
            break;
        }

        $id_str = isset($matches['2'])?$matches['2'] : '';
        $match_str = '\[BrewMaster:' . $matches['1'] . $id_str . '\]';
        $content = preg_replace('/'.$match_str.'/', $html, $content);
      }

      return $content;
    }

    /**
    * Handles clean exits gracefully. Re-raises anything else
    * @param Exception $ex
    */
    function exception_handler($ex)
    {
      if (get_class($ex) != 'E_Clean_Exit') throw $ex;
    }
  }

  // Start it up
  global $bm;
  $bm = new bmLoader();
}

include_once (dirname (__FILE__) . '/admin/install.php');
add_action( 'init', 'brewmaster_register_posttypes' );

function load_brewmaster_styles()
{
  wp_register_style( 'bm-frontend', BM_URLPATH.'assets/css/frontend.css' );
  wp_enqueue_style( 'bm-frontend' );
}
add_action( 'wp_enqueue_scripts', 'load_brewmaster_styles' );

?>