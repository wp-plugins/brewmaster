<?php
/*
 * Copyright (c) 2015, Nathaniel Eklund 

This program is free software; you can redistribute it and/or 
modify it under the terms of the GNU General Public License 
as published by the Free Software Foundation; either version 2 
of the License, or (at your option) any later version. 

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA. 
*/


if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
 * creates all tables for BrewMaster
 * called during register_activation hook
 *
 * @access internal
 * @return void
 */
function brewmaster_install ()
{
    global $wpdb , $wp_roles, $wp_version;

    // Check for capability
    if ( !current_user_can('activate_plugins') )
        return;

    // Set the capabilities for the administrator
    $role = get_role('administrator');
    // We need this role, no other chance
    if ( empty($role) )
    {
        update_option( "bm_init_check", __('Sorry, BrewMaster works only with a role called administrator',"brewmaster") );
        return;
    }

    $role->add_cap('BrewMaster Manager');

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // add charset & collate like wp core
    $charset_collate = '';

    if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') )
    {
        if ( ! empty($wpdb->charset) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
            $charset_collate .= " COLLATE $wpdb->collate";
    }

    $bmbeers     = $wpdb->prefix . 'bm_beers';
    $bmbatches   = $wpdb->prefix . 'bm_batches';

    // Create beers table
    $sql = "CREATE TABLE " . $bmbeers . " (
    beer_id BIGINT(20) NOT NULL AUTO_INCREMENT ,
    beer_name VARCHAR(128) NOT NULL ,
    slug VARCHAR(255) NOT NULL ,
    description MEDIUMTEXT NULL ,
    abv DECIMAL(4,2) NOT NULL ,
    ibus INT NOT NULL ,
    recipe_url MEDIUMTEXT NULL ,
    notes LONGTEXT NULL,
    available BOOLEAN NOT NULL DEFAULT '0' ,
    page_id INT NULL ,
    PRIMARY KEY  (beer_id)
    ) $charset_collate;";
    dbDelta($sql);

    // Create batches table
    $sql = "CREATE TABLE " . $bmbatches . " (
    batch_id BIGINT(20) NOT NULL AUTO_INCREMENT ,
    beer_id BIGINT(20) NOT NULL ,
    batch_name VARCHAR(255) NULL ,
    slug VARCHAR(255) NOT NULL ,
    batch_number BIGINT(20) DEFAULT '1' NOT NULL ,
    start_date DATE NOT NULL ,
    bottle_date DATE NULL ,
    finish_date DATE NULL ,
    original_gravity DECIMAL(6,4) NULL ,
    final_gravity DECIMAL(6,4) NULL ,
    abv DECIMAL(4,2) NULL ,
    ibus INT NOT NULL ,
    batch_volume DECIMAL (6,2) NULL ,
    batch_volume_units VARCHAR(7) NULL ,
    available BOOLEAN NOT NULL DEFAULT '0' ,
    mash_temp DECIMAL (6,2) NULL ,  
    mash_volume DECIMAL (6,2) NULL ,
    mash_volume_units VARCHAR(7) NULL ,
    sparge_temp DECIMAL (6,2) NULL ,
    sparge_volume DECIMAL (6,2) NULL ,
    sparge_volume_units VARCHAR(7) NULL ,
    preboil_gravity DECIMAL(6,4) NULL ,
    preboil_volume DECIMAL (6,2) NULL ,
    preboil_volume_units VARCHAR(7) NULL ,
    secondary_date DATE NULL ,
    tertiary_date DATE NULL ,
    notes LONGTEXT NULL ,
    page_id INT NULL ,
    PRIMARY KEY  (batch_id) ,
    KEY (beer_id)
    ) $charset_collate;";
    dbDelta($sql);

    // check one table again, to be sure
    if( !$wpdb->get_var( "SHOW TABLES LIKE '$bmbeers'" ) )
    {
        update_option( "bm_init_check", __('BrewMaster : Tables could not created, please check your database settings',"bmbatches") );
        return;
    }

    $options = get_option('bm_options');
    // set the default settings, if we didn't upgrade
    if ( empty( $options ) )
         bm_default_options();

    // if all is passed , save the DBVERSION
    add_option("bm_db_version", BM_DBVERSION);
}

/**
 * Setup the default option array for brewmaster
 *
 * @access internal
 * @since version 0.1
 * @return void
 */
function brewmaster_register_posttypes() {
  $labels = array(
                'name' => __( 'Brew Master Beers', 'bm-beers' ),
                'singular_name' => __( 'Brew Master Beer', 'bm-beer' )
              );
  
    register_post_type( 'bm_internal',
        array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'rewrite'            => array( 'slug' => 'brewmaster' ),
            'supports'           => false,
            'capability_type'    => 'page',
            'show_in_menu'       => false
        )
    );
}

/**
 * Setup the default option array for brewmaster
 *
 * @access internal
 * @since version 0.1
 * @return void
 */
function BM_default_options()
{
    global $blog_id, $bm;

    $bm_options['separateAvailableBeers'] = 'true';          // Wether to display available beers at the top

    update_option('bm_options', $bm_options);
}

/**
 * Deregister a capability from all classic roles
 *
 * @access internal
 * @param string $capability name of the capability which should be deregister
 * @return void
 */
function bm_remove_capability($capability)
{
    // this function remove the $capability only from the classic roles
    $check_order = array("subscriber", "contributor", "author", "editor", "administrator");

    foreach ($check_order as $role)
    {
        $role = get_role($role);
        $role->remove_cap($capability);
    }
}

/**
 * Uninstall all settings and tables
 * Called via Setup and register_uninstall hook
 *
 * @access internal
 * @return void
 */
function brewmaster_uninstall()
{
  global $wpdb;

  // first remove all tables
  $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bm_beers");
  $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bm_batches");

  // then remove all options
  delete_option( 'bm_options' );
  delete_option( 'bm_db_version' );
  delete_option( 'bm_update_exists' );
  delete_option( 'bm_next_update' );
  delete_option('bm_displayUnavailable');
  delete_option('bm_separateUnavailable');
  delete_option('bm_wordForAvailable');
  delete_option('bm_wordforUnavailable');
  delete_option('bm_beerStatType');
  delete_option('bm_averageBatchStats');
  delete_option('bm_listBatches');
  
  //Remove all our beer and batch pages
  $custompages = get_pages(array('post_type' => 'bm_internal'));
  foreach($custompage as $page)
  {
    wp_delete_post($page->ID, true);
  }
  
  //Remove our custom post type
  global $wp_post_types;
  if ( isset( $wp_post_types[ 'bm-internal' ] ) ) {
      unset( $wp_post_types[ 'bm-internal' ] );
  }

  //Remove the management capability
  bm_remove_capability("BrewMaster Manager");
}

?>