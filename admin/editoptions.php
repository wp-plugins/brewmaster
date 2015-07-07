<?php
if ( !class_exists('bmEditOptions') ) :
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
* Code for editing options
* 
* @author Nate Eklund 
*  
*/

class bmEditOptions
{
  var $action;

  /**
   * bmEditOptions::__construct()
   *
   * @return void
   */
  function __construct()
  {
    $this->filepath    = admin_url() . 'admin.php?page=' . $_GET['page'];

    //Look for POST updates
    if (!empty($_POST))
    {
      $this->processor();
    }

    ?>
      <H2>Edit Options</H2>
    <?php
  }

  function processor()
  {
    update_option('bm_breweryName', $_POST['breweryName']);
    
    update_option('bm_displayUnavailable', isset($_POST['displayUnavailable']) ? "1" : "0");
    update_option('bm_separateUnavailable', isset($_POST['separateUnavailable']) ? "1" : "0");

    if($_POST['wordForAvailable'] == '')
    {
      update_option('bm_wordForAvailable', 'Now Serving');
    }
    else
    {
      update_option('bm_wordForAvailable', $_POST['wordForAvailable']);
    }

    if($_POST['wordforUnavailable'] == '')
    {
      update_option('bm_wordforUnavailable', 'Sold Out');
    }
    else
    {
      update_option('bm_wordforUnavailable', $_POST['wordforUnavailable']);
    }

    update_option('bm_beerStatType', $_POST['beerStatType']);
    update_option('bm_averageBatchStats', $_POST['averageBatchStats']);
    update_option('bm_listBatches', isset($_POST['listBatches']) ? "1" : "0");
  }

  function controller()
  {   
    $breweryName            =    get_option('bm_breweryName');
    $displayUnavailable     =    get_option('bm_displayUnavailable');
    $separateUnavailable    =    get_option('bm_separateUnavailable');
    $wordForAvailable       =    get_option('bm_wordForAvailable');
    $wordforUnavailable     =    get_option('bm_wordforUnavailable');
    $beerStatType           =    get_option('bm_beerStatType');
    $averageBatchStats      =    get_option('bm_averageBatchStats');
    $listBatches            =    get_option('bm_listBatches');

    ?>
      <form method="post" action="<?php echo $this->filepath; ?>">
        <p>
          Brewery Name: <input type="text" name="breweryName" value="<?php echo $breweryName; ?>">
        </p>
        <p>
          Display Unavailable Beers/Batches: <input type="checkbox" 
                                                    name="displayUnavailable" 
                                                    <?php echo $displayUnavailable == "1" ? "checked" : "" ?> >
        </p>
        <p>
        Separate Unavailable Beers/Batches: <input type="checkbox" 
                                                   name="separateUnavailable" 
                                                   <?php echo $separateUnavailable == "1" ? "checked" : "" ?>>
        </p>
        <p>
        Phrase For "Available": <input type="text" 
                                       name="wordForAvailable" 
                                       value="<?php echo $wordForAvailable; ?>"> Default - "Now Serving"
        </p>
        <p>
          Phrase For "Unavailable": <input type="text" 
                                           name="wordforUnavailable" 
                                           value="<?php echo $wordforUnavailable; ?>"> Default - "Sold Out"
        </p>
        <p>
          Beer Stats Display: <select name="beerStatType"> 
                                <option value="Real" 
                                        <?php echo $beerStatType == "Real" ? "Selected" : ""; ?>>Base beer stats on batches
                                </option>
                                <option value="Target" 
                                        <?php echo $beerStatType == "Target" ? "Selected" : ""; ?>>Display stats as set for beer type
                                </option>
                                <option value="Both" 
                                        <?php echo $beerStatType == "Both" ? "Selected" : ""; ?>>Display both "Real" and "Target" stats
                                </option>
                              </select>
        </p>
        <p>
        "Real" Stats Calculation: <select name="averageBatchStats"> 
                                    <option value="1" 
                                            <?php echo $averageBatchStats == "1" ? "Selected" : ""; ?>>
                                            Use average stats from batches</option>
                                    <option value="0" 
                                            <?php echo $averageBatchStats == "0" ? "Selected" : ""; ?>>
                                            Use stats from most recent batch</option>
                                  </select>
        </p>
        <p>
          List Batches in Beer display: <input type="checkbox" 
                                               name="listBatches" 
                                               <?php echo $listBatches == "1" ? "checked" : "" ?>>
        </p>

        <input type="hidden" name="action" value=<?php echo "$this->action"; ?>>

        <input type="submit" value="Save">
      </form>
    <?php
  }
}

endif;
?>