<?php
if ( !class_exists('bmManageBeers') ) :
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
* Code for listing and editing beers
* 
* @author Nate Eklund 
*  
*/

class bmManageBeers
{
  var $beer;
  var $action;
  
  /**
   * bmManageBeers::__construct()
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
    else //No special action set, just list the beers to manage!
    {
      $this->action = "list";
    }
  }
  
  function load_beer($beerID)
  {
    global $brewmasterdb;

    if(!empty($beerID))
    {
      $this->beer = $brewmasterdb->find_beer($beerID);
    }
  }

  function processor()
  {
    global $brewmasterdb;
    $beerID = "";

    if(isset($_POST['beer_id']))
    {
      $beerID = $_POST['beer_id'];
    }
    else if(isset($_GET['beer_id']))
    {
      $beerID = $_GET['beer_id'];
    }
    else  //Can't manage a beer if we don't have a beer ID!
    {
      exit();
    }

    if($this->action == "deleteBeer")
    {
      $brewmasterdb->delete_beer($beerID);
      $this->action = "list";
    }
    else if($this->action == "confirmDeleteBeer")
    {
      $this->load_beer($beerID);
      ?>
        <form method="post" action="<?php echo $this->filepath?>">
          <input type="hidden" name="beer_id" value="<?php echo $beerID ?>">
          <input type="hidden" name="action" value="deleteBeer">
          <p>Are you sure you want to delete <?php echo $this->beer->beer_name ?></p>
          <input type="submit" value="Delete">
          <a href="admin.php?page=bm-manage-beers">cancel</a>
        </form>
      <?php
    }
  }
  
  function controller()
  {
    if($this->action == "list")
    {
      global $wpdb;
      $beer_list = $wpdb->get_results('SELECT beer_id,beer_name,abv,ibus,available FROM ' . $wpdb->bmbeers . 
                                      ' ORDER BY available DESC,beer_name ASC');

      ?>
        <H2>Manage Beers</H2>
      <?php

      if($beer_list)
      {
        ?>
            <table>
                <tr>
                    <th>Beer ID</th>
                    <th>Beer Name</th>
                    <th>ABV</th>
                    <th>IBUs</th>
                    <th>Available</th>
                </tr>
        <?php

        foreach($beer_list as $beer)
        {
          if(!isset($beer->beer_id))
            $beer->beer_id = '';
          if(!isset($beer->beer_name))
            $beer->beer_name = '';
          if(!isset($beer->abv))
            $beer->abv = '';
          if(!isset($beer->ibus))
            $beer->ibus = '';
          if(!isset($beer->available))
            $beer->available = '';

          $this->beer = $beer;

          ?>
            <tr>
              <td><?php echo $this->beer->beer_id ?></td>
              <td><?php echo $this->beer->beer_name ?></td>
              <td><?php echo $this->beer->abv ?></td>
              <td><?php echo $this->beer->ibus ?></td>
              <td><?php echo $this->beer->available?'Yes':'No'; ?></td>
              <td>
                <a href="<?php echo admin_url() . 'admin.php?page=bm-add-beer&amp;beer_id=' 
                                                . $this->beer->beer_id ?>">
                  Edit
                </a>
              </td>
              <td>
                <a id="DeleteBeer".<?php echo $this->beer->beer_id ?> 
                   href="<?php echo $this->filepath . 
                                    '&amp;beer_id=' . $this->beer->beer_id .
                                    '&amp;action=confirmDeleteBeer' ?>">
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
          No beers found.  <a href="<?php echo admin_url() . 'admin.php?page=bm-add-beer' ?>">Add One!</a>
        <?php
      }
    }
  }
}

endif;
?>