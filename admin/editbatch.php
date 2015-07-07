<?php
if ( !class_exists('bmEditBatch') ) :
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
 * Code for adding/editing a batch
 * 
 * @author Nate Eklund 
 *  
 */

include_once (BM_ABSPATH . '/lib/batch.php');

class bmEditBatch
{
    var $batch;
    var $action;
    
    function __construct()
    {
        // same as $_SERVER['REQUEST_URI'], but should work under IIS 6.0
        $this->filepath    = admin_url() . 'admin.php?page=' . $_GET['page'];

        //Look for POST updates
        if ( !empty($_POST) )
        {
            $this->action = $_POST['action'];
            $this->processor();
        }
        elseif(isset($_GET['batch_id']))
        {
            $this->load_batch($_GET['batch_id']);
        }
        else
        {
            $this->batch = new bmBatch();
            $this->action = "add";
            ?>
                <H2>Add Batch</H2>
            <?php
        }
    }
    
    function load_batch($batchID)
    {
        global $brewmasterdb;
        
        if(!empty($batchID))
        {
            $this->batch = $brewmasterdb->find_batch($batchID);
            $this->action = "edit";
            ?>
                <H2>Editing <?php echo $this->batch->batch_name ?></H2>
            <?php
        }
    }
    
    function processor()
    {
      global $brewmasterdb;
      $batchID = "";

      if(isset($_POST['batch_id']))
      {
          $batchID = $_POST['batch_id'];
      }

      if(!isset($this->action))
      {
          $this->action = "load";
      }

      if($this->action == "edit" && !empty($batchID))
      {
        $this->batch = $brewmasterdb->find_batch($batchID);

        $this->batch->beer_id = $_POST['beer_id'];
        $this->batch->batch_name = stripslashes($_POST['batch_name']);
        $this->batch->batch_number = $_POST['batch_number'];
        $this->batch->start_date = $_POST['start_date'];
        $this->batch->bottle_date = $_POST['bottle_date'];
        $this->batch->finish_date = $_POST['finish_date'];
        $this->batch->abv = $_POST['abv'];
        $this->batch->ibus = $_POST['ibus'];
        $this->batch->available = isset($_POST['available']) ? True : 0;
        $this->batch->original_gravity = $_POST['original_gravity'];
        $this->batch->final_gravity = $_POST['final_gravity'];
        $this->batch->batch_volume = $_POST['batch_volume'];
        $this->batch->batch_volume_units = $_POST['batch_volume_units'];
        $this->batch->mash_temp = $_POST['mash_temp'];
        $this->batch->mash_volume = $_POST['mash_volume'];
        $this->batch->mash_volume_units = $_POST['mash_volume_units'];
        $this->batch->sparge_temp = $_POST['sparge_temp'];
        $this->batch->sparge_volume = $_POST['sparge_volume'];
        $this->batch->sparge_volume_units = $_POST['sparge_volume_units'];
        $this->batch->preboil_gravity = $_POST['preboil_gravity'];
        $this->batch->preboil_volume = $_POST['preboil_volume'];
        $this->batch->preboil_volume_units = $_POST['preboil_volume_units'];
        $this->batch->secondary_date = $_POST['secondary_date'];
        $this->batch->tertiary_date = $_POST['tertiary_date'];
        $this->batch->notes = stripslashes($_POST['notes']);

        $brewmasterdb->update_batch($this->batch);

        ?>
            <H2>Editing <?php echo $this->batch->batch_name ?></H2>
        <?php
      }
      elseif($this->action == "add")
      {
        if(!isset($_POST['beer_id']))
          $_POST['beer_id'] = -1;

        //Add this batch to the database!
        $batchID = $brewmasterdb->add_batch($_POST['beer_id'], stripslashes($_POST['batch_name']), $_POST['batch_number'], 
                                            $_POST['start_date'], $_POST['bottle_date'], $_POST['finish_date'], 
                                            $_POST['abv'], $_POST['ibus'], 
                                            $_POST['available'] == "on" ? True : 0,
                                            $_POST['original_gravity'], $_POST['final_gravity'],
                                            $_POST['batch_volume'], $_POST['batch_volume_units'],
                                            $_POST['mash_temp'], $_POST['mash_volume'], $_POST['mash_volume_units'],
                                            $_POST['sparge_temp'], $_POST['sparge_volume'], $_POST['sparge_volume_units'],
                                            $_POST['preboil_gravity'], $_POST['preboil_volume'], $_POST['preboil_volume_units'],
                                            $_POST['secondary_date'], $_POST['tertiary_date'],
                                            stripslashes($_POST['notes']));
        
        $this->batch = $brewmasterdb->find_batch($batchID);
        $this->create_batch_page($this->batch);
        $this->action = "edit"; 
        ?>
          <H2>Added <?php echo $_POST['batch_name'] ?></H2>
        <?php
      }
      else
      {
        $this->action = "add";
        $this->batch = new bmBatch();
      }
    }
    
    function create_batch_page($batch)
    {
      if($batch->page_id < 1)
      {
        global $brewmasterdb;
        
        // create a page to display this batch
        $post = array(
          'comment_status' => 'open', 
          'ping_status' => 'open',
          'post_author' => wp_get_current_user()->ID,
          'post_content' => '<!-- Automatically created by the Brew Master Plugin; DO NOT TOUCH -->[BrewMaster:batch id=' . $batch->batch_id . ']',
          'post_name' => $batch->slug,
          'post_status' => 'publish',
          'post_title' => $batch->batch_name,
          'post_type' => 'bm_internal'
        );
        
        $post_id = wp_insert_post( $post );
        $brewmasterdb->set_batch_pageid($batch->batch_id, $post_id);
      }
    }
    
    function controller()
    {
        if(!isset($this->batch->batch_id))
            $this->batch->batch_id = '';
        if(!isset($this->batch->beer_id))
            $this->batch->beer_id = '';
        if(!isset($this->batch->batch_name))
            $this->batch->batch_name = '';
        if(!isset($this->batch->batch_number))
            $this->batch->batch_number = '';
        if(!isset($this->batch->start_date))
            $this->batch->start_date = '';
        if(!isset($this->batch->bottle_date))
            $this->batch->bottle_date = '';
        if(!isset($this->batch->finish_date))
            $this->batch->finish_date = '';
        if(!isset($this->batch->abv))
            $this->batch->abv = '';
        if(!isset($this->batch->ibus))
            $this->batch->ibus = '';
        if(!isset($this->batch->available))
            $this->beer->available = 0;
        if(!isset($this->batch->original_gravity))
            $this->batch->original_gravity = '';
        if(!isset($this->batch->final_gravity))
            $this->batch->final_gravity = '';
        if(!isset($this->batch->batch_volume))
            $this->batch->batch_volume = '';
        if(!isset($this->batch->batch_volume_units))
            $this->batch->batch_volume_units = '';
        if(!isset($this->batch->mash_temp))
            $this->batch->mash_temp = '';
        if(!isset($this->batch->mash_volume))
            $this->batch->mash_volume = '';
        if(!isset($this->batch->mash_volume_units))
            $this->batch->mash_volume_units = '';
        if(!isset($this->batch->sparge_temp))
            $this->batch->sparge_temp = '';
        if(!isset($this->batch->sparge_volume))
            $this->batch->sparge_volume = '';
        if(!isset($this->batch->sparge_volume_units))
            $this->batch->sparge_volume_units = '';
        if(!isset($this->batch->preboil_gravity))
            $this->batch->preboil_gravity = '';
        if(!isset($this->batch->preboil_volume))
            $this->batch->preboil_volume = '';
        if(!isset($this->batch->preboil_volume_units))
            $this->batch->preboil_volume_units = '';
        if(!isset($this->batch->secondary_date))
            $this->batch->secondary_date = '';
        if(!isset($this->batch->tertiary_date))
            $this->batch->tertiary_date = '';
        if(!isset($this->batch->notes))
            $this->batch->notes = '';
        
        ?>
            <form method="post" action="<?php echo $this->filepath?>">
                <input type="hidden" name="batch_id" value="<?php echo $this->batch->batch_id ?>">
                Beer Name:  <select name="beer_id">
                                <option value="-1" <?php echo $this->batch->beer_id < 1 ? "Selected" : "" ?>>NoneSelected</option>
                                <?php
                                    global $wpdb;
                                    $beers = $wpdb->get_results("SELECT beer_id, beer_name FROM $wpdb->bmbeers");
                                    foreach ($beers as $beer)
                                    {
                                        $selected = $this->batch->beer_id == $beer->beer_id ? " Selected" : "";
                                        echo "<option value='".$beer->beer_id."'" . $selected . ">".$beer->beer_name."</option>";
                                    }
                                ?>
                            </select></br>
                Batch Name: <input type="text" name="batch_name" value="<?php echo $this->batch->batch_name ?>" required></br>
                Batch Number: <input type="number" name="batch_number" size="5" min="0" value="<?php echo $this->batch->batch_number ?>"></br>
                Start Date: <input type="date" name="start_date" value="<?php echo $this->batch->start_date ?>"></br>
                Bottling/Kegging Date: <input type="date" name="bottle_date" value="<?php echo $this->batch->bottle_date ?>"></br>
                Finish Date: <input type="date" name="finish_date" value="<?php echo $this->batch->finish_date ?>"></br>
                ABV: <input type="number" name="abv" size="4" min="0" max="99.99" step="any" value="<?php echo $this->batch->abv ?>"></br>
                IBUs: <input type="number" name="ibus" size="4" min="0" step="any" value="<?php echo $this->batch->ibus ?>"></br>
                Available: <input type="checkbox" name="available" <?php echo $this->batch->available == True ? "checked" : "" ?>></br>
                Original Gravity: <input type="number" name="original_gravity" size="6" min="0" step="any" value="<?php echo $this->batch->original_gravity ?>"></br>
                Final Gravity: <input type="number" name="final_gravity" size="6" min="0" step="any" value="<?php echo $this->batch->final_gravity ?>"></br>
                Batch Volume: <input type="number" name="batch_volume" size="6" min="0" step="any" value="<?php echo $this->batch->batch_volume ?>"> 
                              <select name="batch_volume_units"> 
                                  <option value="Gallons" <?php echo $this->batch->batch_volume_units == 'Gallons' ? "Selected" : ""; ?>>Gallons</option>
                                  <option value="Liters" <?php echo $this->batch->batch_volume_units == 'Liters' ? "Selected" : ""; ?>>Liters</option>
                                  <option value="Barrels" <?php echo $this->batch->batch_volume_units == 'Barrels' ? "Selected" : ""; ?>>Barrels</option>
                              </select></br>
                Mash Temp: <input type="number" name="mash_temp" size="6" min="0" step="any" value="<?php echo $this->batch->mash_temp ?>"></br>
                Mash Volume: <input type="number" name="mash_volume" size="6" min="0" step="any" value="<?php echo $this->batch->mash_volume ?>"> 
                              <select name="mash_volume_units"> 
                                  <option value="Gallons" <?php echo $this->batch->mash_volume_units == "Gallons" ? "Selected" : ""; ?>>Gallons</option>
                                  <option value="Liters" <?php echo $this->batch->mash_volume_units == "Liters" ? "Selected" : ""; ?>>Liters</option>
                                  <option value="Barrels" <?php echo $this->batch->mash_volume_units == "Barrels" ? "Selected" : ""; ?>>Barrels</option>
                              </select></br>
                Sparge Temp: <input type="number" name="sparge_temp" size="6" min="0" step="any" value="<?php echo $this->batch->sparge_temp ?>"></br>
                Sparge Volume: <input type="number" name="sparge_volume" size="6" min="0" step="any" value="<?php echo $this->batch->sparge_volume ?>"> 
                              <select name="sparge_volume_units"> 
                                  <option value="Gallons" <?php echo $this->batch->sparge_volume_units == "Gallons" ? "Selected" : ""; ?>>Gallons</option>
                                  <option value="Liters" <?php echo $this->batch->sparge_volume_units == "Liters" ? "Selected" : ""; ?>>Liters</option>
                                  <option value="Barrels" <?php echo $this->batch->sparge_volume_units == "Barrels" ? "Selected" : ""; ?>>Barrels</option>
                              </select></br>
                Pre-Boil Gravity: <input type="number" name="preboil_gravity" size="6" min="0" step="any" value="<?php echo $this->batch->preboil_gravity ?>"></br>
                Pre-Boil Volume: <input type="number" name="preboil_volume" size="6" min="0" step="any" value="<?php echo $this->batch->preboil_volume ?>"> 
                              <select name="preboil_volume_units"> 
                                  <option value="Gallons" <?php echo $this->batch->preboil_volume_units == "Gallons" ? "Selected" : ""; ?>>Gallons</option>
                                  <option value="Liters" <?php echo $this->batch->preboil_volume_units == "Liters" ? "Selected" : ""; ?>>Liters</option>
                                  <option value="Barrels" <?php echo $this->batch->preboil_volume_units == "Barrels" ? "Selected" : ""; ?>>Barrels</option>
                              </select></br>
                Secondary Date: <input type="date" name="secondary_date" value="<?php echo $this->batch->secondary_date ?>"></br>
                Tertiary Date: <input type="date" name="tertiary_date" value="<?php echo $this->batch->tertiary_date ?>"></br>
                Notes:</br>
                <textarea name="notes" rows="5" cols="100"><?php echo $this->batch->notes ?></textarea></br>
                
                <input type="hidden" name="action" value="<?php echo $this->action ?>">
                
                <input type="submit" value="<?php echo ($this->batch->batch_id == "") ? 'Add Batch' : 'Edit Batch' ?>">
            </form>
        <?php
    }
}

endif;
?>