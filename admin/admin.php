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
 *
 * bmAdminPanel - Admin Section for BrewMaster
 *
 * @package BrewMaster
 * @author Nate Eklund
 *
 * @since 1.0.0
 */
 
 class bmAdminPanel
 {
    // constructor
    function __construct()
    {
        // Add the admin menu
        add_action( 'admin_menu', array (&$this, 'add_menu') );

        // Add the script and style files
        add_action('admin_print_scripts', array(&$this, 'load_scripts') );
        add_action('admin_print_styles', array(&$this, 'load_styles') );
    }

    // integrate the menu
    function add_menu()
    {
        add_menu_page('BrewMaster', 
                      'BrewMaster', 
                      'BrewMaster Manager', 
                      BMFOLDER, 
                      array (&$this, 'show_menu'));
        add_submenu_page(BMFOLDER, 
                         'Overview',
                         'Overview', 
                         'BrewMaster Manager', 
                         BMFOLDER, 
                         array (&$this, 'show_menu'));
        add_submenu_page(BMFOLDER, 
                         'Edit Beer', 
                         'Add Beer', 
                         'BrewMaster Manager', 
                         'bm-add-beer', 
                         array (&$this, 'show_menu'));
        add_submenu_page(BMFOLDER,
                         'Edit Batch',
                         'Add Batch', 
                         'BrewMaster Manager', 
                         'bm-add-batch', 
                         array (&$this, 'show_menu'));
        add_submenu_page(BMFOLDER,
                         'Manage Beers',
                         'Manage Beers', 
                         'BrewMaster Manager', 
                         'bm-manage-beers', 
                         array (&$this, 'show_menu'));
        add_submenu_page(BMFOLDER, 
                         'Manage Batches', 
                         'Manage Batches', 
                         'BrewMaster Manager', 
                         'bm-manage-batches', 
                         array (&$this, 'show_menu'));
        add_submenu_page(BMFOLDER,
                         'Options', 
                         'Options', 
                         'BrewMaster Manager', 
                         'bm-options', 
                         array (&$this, 'show_menu'));
    }

    // load the script for the defined page and load only this code
    function show_menu()
    {
      global $bm;

      // Set installation date
      if(empty($bm->options['installDate']))
      {
        $bm->options['installDate'] = time();
        update_option('bm_options', $bm->options);
      }

      switch ($_GET['page'])
      {
        case "bm-add-beer" :
          include_once ( dirname (__FILE__) . '/editbeer.php' );    // bm_add_beer
          $bm->addbeer_page = new bmEditBeer();
          $bm->addbeer_page->controller();
          break;
        case "bm-add-batch" :
          include_once ( dirname (__FILE__) . '/editbatch.php' );    // bm_admin_add_beer
          $bm->addbatch_page = new bmEditBatch();
          $bm->addbatch_page->controller();
          break;
        case "bm-manage-beers" :
          include_once ( dirname (__FILE__) . '/managebeers.php' );    // bm_manage_beers
          $bm->managebeers_page = new bmManageBeers();
          $bm->managebeers_page->controller();
          break;
        case "bm-manage-batches" :
          include_once ( dirname (__FILE__) . '/managebatches.php' );    // bm_manage_batches
          $bm->managebatches_page = new bmManageBatches();
          $bm->managebatches_page->controller();
          break;
        case "bm-options" :
          include_once ( dirname (__FILE__) . '/editOptions.php' );    // bm_admin_options
          $bm->option_page = new bmEditOptions();
          $bm->option_page->controller();
          break;
        case "brewmaster" :
        default :
          include_once ( dirname (__FILE__) . '/overview.php' );     // bm_admin_overview
          bm_admin_overview();
          break;
      }
    }

    function load_scripts()
    {

    }

    function load_styles()
    {

    }
}
 
 ?>