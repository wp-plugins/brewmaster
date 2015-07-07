<?php
if ( !class_exists('bmManageBatches') ) :
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
* Code for managing batches
* 
* @author Nate Eklund 
*  
*/

include_once (BM_ABSPATH . '/lib/batch.php');

class bmManageBatches
{
  var $batch;
  var $beerName;

  /**
   * bmManageBatches::__construct()
   *
   * @return void
   */
  function __construct()
  {
    $this->filepath    = admin_url() . 'admin.php?page=' . $_GET['page'];
    
    //Look for POST updates
    if ( !empty($_POST) )
    {
      $this->action = $_POST['action'];
      $this->processor();
    }
    else if(isset($_GET['action']))
    {
      $this->action = $_GET['action'];
      $this->processor();
    }
    else //No special action set, just list the batches to manage!
    {
      $this->action = "list";
    }
  }

  function load_batch($batchID)
  {
    global $brewmasterdb;

    if(!empty($batchID))
    {
      $this->batch = $brewmasterdb->find_batch($batchID);
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
    else if(isset($_GET['batch_id']))
    {
      $batchID = $_GET['batch_id'];
    }
    else  //Can't manage a batch if we don't have a batch ID!
    {
      exit();
    }

    if($this->action == "deleteBatch")
    {
      $brewmasterdb->delete_batch($batchID);
      $this->action = "list";
    }
    else if($this->action == "confirmDeleteBatch")
    {
      $batchName;
      if(isset($_GET['batch_name']))
      {
        $batchName = $_GET['batch_name'];
      }
      else
      {
        //Batch Name wasn't passed in, grab it from the DB
        $this->load_batch($batchID);
        $batchName = $this->batch->batch_name;
      }
      
      ?>
        <form method="post" action="<?php echo $this->filepath?>">
          <input type="hidden" name="batch_id" value="<?php echo $batchID ?>">
          <input type="hidden" name="action" value="deleteBatch">
          <p>Are you sure you want to delete <?php echo $batchName ?></p>
          <input type="submit" value="Delete">
          <a href="admin.php?page=bm-manage-batches">cancel</a>
        </form>
      <?php
    }
  }

  function controller()
  {
    if($this->action == "list")
    {
      global $wpdb;
      $batch_list = $wpdb->get_results('SELECT batch_id,beer_id,batch_name,batch_number,start_date,bottle_date,original_gravity,final_gravity,batch_volume,batch_volume_units,abv,ibus,available FROM ' . 
                                       $wpdb->bmbatches . ' ORDER BY beer_id ASC,start_date DESC');

      ?>
        <H2>Manage Batches</H2>
      <?php

      if($batch_list)
      {
        ?>
          <table>
            <tr>
              <th>Batch ID</th>
              <th>Beer</th>
              <th>Batch Name</th>
              <th>Batch Number</th>
              <th>Start Date</th>
              <th>Bottling Date</th>
              <th>Original Gravity</th>
              <th>Final Gravity</th>
              <th>Batch Size</th>
              <th>ABV</th>
              <th>IBUs</th>
              <th>Available</th>
            </tr>
        <?php

        foreach($batch_list as $batch)
        {
          if(!isset($batch->batch_id))
            $batch->batch_id = '';
          if(!isset($batch->beer_id))
            $batch->beer_id = '';
          if(!isset($batch->batch_name))
            $batch->batch_name = '';
          if(!isset($batch->batch_number))
            $batch->batch_number = '';
          if(!isset($batch->start_date))
            $batch->start_date = '';
          if(!isset($batch->bottle_date))
            $batch->bottle_date = '';
          if(!isset($batch->abv))
            $batch->abv = '';
          if(!isset($batch->ibus))
            $batch->ibus = '';
          if(!isset($batch->available))
            $this->beer->available = '';
          if(!isset($batch->original_gravity))
            $batch->original_gravity = '';
          if(!isset($batch->final_gravity))
            $batch->final_gravity = '';
          if(!isset($batch->batch_volume))
            $batch->batch_volume = '';
          if(!isset($batch->batch_volume_units))
            $batch->batch_volume_units = '';

          $this->batch = $batch;

          $beer = $wpdb->get_row($wpdb->prepare("SELECT beer_name FROM $wpdb->bmbeers WHERE beer_id=%d", $batch->beer_id));
          if($beer)
          {
            $this->beerName = $beer->beer_name;
          }
          else
          {
            $this->beerName = '';
          }

          ?>
            <tr>
              <td><?php echo $this->batch->batch_id ?></td>
              <td><?php echo $this->beerName ?></td>
              <td><?php echo $this->batch->batch_name ?></td>
              <td><?php echo $this->batch->batch_number ?></td>
              <td><?php echo $this->batch->start_date ?></td>
              <td><?php echo $this->batch->bottle_date ?></td>
              <td><?php echo $this->batch->original_gravity ?></td>
              <td><?php echo $this->batch->final_gravity ?></td>
              <td>
                <?php 
                  echo $this->batch->batch_volume; 
                  echo ' ';
                  echo $this->batch->batch_volume_units; 
                ?>
              </td>
              <td><?php echo $this->batch->abv ?></td>
              <td><?php echo $this->batch->ibus ?></td>
              <td><?php echo $this->batch->available?'Yes':'No'; ?></td>
              <td>
                <a href="<?php echo admin_url() . 'admin.php?page=bm-add-batch&amp;batch_id=' . $this->batch->batch_id ?>">
                  Edit
                </a>
              </td>
              <td>
                <a id="DeleteBatch".<?php echo $this->batch->batch_id ?> 
                   href="<?php echo $this->filepath . 
                                    '&amp;batch_id=' . $this->batch->batch_id .
                                    '&amp;batch_name=' . $this->batch->batch_name .
                                    '&amp;action=confirmDeleteBatch' ?>">
                  Delete
                </a>
              </td>
            </tr>
          <?php
        }

        ?>
          </table>
        <?php
      }
      else
      {
        ?>
          No batches found.  <a href="<?php echo admin_url() . 'admin.php?page=bm-add-batch' ?>">Add One!</a>
        <?php
      }
    }
  }
}

endif;
?>