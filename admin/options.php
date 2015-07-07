<?php
if ( !class_exists('bmOptions') ) :
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
* Options PHP class for the WordPress plugin BrewMaster
* 
* @author Nate Eklund 
*  
*/
class bmOptions
{
  /**** Public variables ****/    
  var $errmsg          =    '';            // Error message to display, if any
  var $error           =    FALSE;         // Error state

  /**** Options ****/
  var $displayUnavailable     =    0;    // Display unavailable beers
  var $separateUnavailable    =    0;    // Separate beers into available and unavailable  
  var $wordForAvailable       =    '';   // Word for available beers, default "Now Serving"
  var $wordforUnavailable     =    '';   // Word for unavailable beers, default "Sold Out"
  var $beerStatType           =    '';   // Display stats as "Real", "Target" or "Both"  
  var $averageBatchStats      =    0;    // Whether or not to us batch avg as "real" stats
  var $listBatches            =    0;    // Whether or not to list batches in beer display


  /**
   * Constructor
   * 
   * @return void
   */
  function bmOptions()
  {
    $this->displayUnavailable     =    get_option('bm_displayUnavailable');
    $this->separateUnavailable    =    get_option('bm_separateUnavailable');
    $this->wordForAvailable       =    get_option('bm_wordForAvailable');
    $this->wordforUnavailable     =    get_option('bm_wordforUnavailable');
    $this->beerStatType           =    get_option('bm_beerStatType');
    $this->averageBatchStats      =    get_option('bm_averageBatchStats');
    $this->listBatches            =    get_option('bm_listBatches');
  }

  function __destruct()
  {
  }
}
endif;
?>