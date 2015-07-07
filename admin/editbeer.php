<?php
if ( !class_exists('bmEditBeer') ) :
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
* Code for adding/editing a beer
* 
* @author Nate Eklund 
*  
*/

include_once (BM_ABSPATH . '/lib/beer.php');

class bmEditBeer
{
    var $beer;
    var $beer_id;
    var $action;
    
    /**
     * bmEditBeer::__construct()
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
        elseif(isset($_GET['beer_id']))
        {
            $this->load_beer($_GET['beer_id']);
        }
        else
        {
            $this->beer = new bmBeer();
            $this->action = "add";
            ?>
                <H2>Add Beer</H2>
            <?php
        }
    }
    
    function load_beer($beerID)
    {
        global $brewmasterdb;
        
        if(!empty($beerID))
        {
            $this->beer = $brewmasterdb->find_beer($beerID);
            $this->action = "edit";
            ?>
                <H2>Editing <?php echo $this->beer->beer_name ?></H2>
            <?php
        }
    }
    
    function create_beer_page($beer)
    {
      if($beer->page_id < 1)
      {
        global $brewmasterdb;

        // create a page to display this beer
        $post = array(
            'comment_status' => 'open', 
            'ping_status' => 'open',
            'post_author' => wp_get_current_user()->ID,
            'post_content' => '<!-- Automatically created by the Brew Master Plugin; DO NOT TOUCH -->[BrewMaster:beer id=' . $beer->beer_id . ']',
            'post_name' => $beer->slug,
            'post_status' => 'publish',
            'post_title' => $beer->beer_name,
            'post_type' => 'bm_internal'
        );

        $post_id = wp_insert_post( $post );
        $brewmasterdb->set_beer_pageid($beer->beer_id, $post_id);
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
        
        if($this->action == "edit" && !empty($beerID))
        {
          $this->beer = $brewmasterdb->find_beer($beerID);

          $this->beer->beer_name   = stripslashes($_POST['name']);
          $this->beer->description = stripslashes($_POST['description']);
          $this->beer->abv         = $_POST['abv'];
          $this->beer->ibus        = $_POST['ibus'];
          $this->beer->recipe_url  = $_POST['recipeurl'];
          $this->beer->notes       = stripslashes($_POST['notes']);
          $this->beer->available   = isset($_POST['available']) ? True : 0;

          $brewmasterdb->update_beer($this->beer);

          ?>
              <H2>Editing <?php echo $this->beer->beer_name ?></H2>
          <?php
        }
        elseif($this->action == "add")
        {
            //Add this beer to the database!
            $beerID = $brewmasterdb->add_beer(stripslashes($_POST['name']), stripslashes($_POST['description']), 
                                              $_POST['abv'], $_POST['ibus'], 
                                              $_POST['recipeurl'], stripslashes($_POST['notes']),
                                              $_POST['available'] == "on" ? True : 0);
          
            $this->beer = $brewmasterdb->find_beer($beerID);  
            $this->create_beer_page($this->beer);
            
            $this->action = "edit"; 
            ?>
                <H2>Added <?php echo $_POST['name'] ?></H2>
            <?php
        }
        else
        {
            $this->action = "add";
            $this->beer = new bmBeer();
        }
    }
    
    function controller()
    {
      if(!isset($this->beer->beer_id))
          $this->beer->beer_id = '';
      if(!isset($this->beer->beer_name))
          $this->beer->beer_name = '';
      if(!isset($this->beer->abv))
          $this->beer->abv = '';
      if(!isset($this->beer->ibus))
          $this->beer->ibus = '';
      if(!isset($this->beer->description))
          $this->beer->description = '';
      if(!isset($this->beer->recipe_url))
          $this->beer->recipe_url = '';
      if(!isset($this->beer->notes))
          $this->beer->notes = '';
      if(!isset($this->beer->available))
          $this->beer->available = False;

      ?>
        <form method="post" action="<?php echo $this->filepath?>">
          <input type="hidden" name="beer_id" value="<?php echo $this->beer->beer_id ?>">
          Beer Name: <input type="text" name="name" value="<?php echo $this->beer->beer_name ?>" required></br>
          ABV: <input type="number" name="abv" size="4" min="0" max="99.99" step="any" value="<?php echo $this->beer->abv ?>"></br>
          IBUs: <input type="number" name="ibus" size="4" min="0" step="any" value="<?php echo $this->beer->ibus ?>"></br>
          Description:</br>
          <textarea name="description" rows="5" cols="100"><?php echo $this->beer->description ?></textarea></br>
          Recipe URL: <input type="url" name="recipeurl" value="<?php echo $this->beer->recipe_url ?>"></br>
          Notes:</br>
          <textarea name="notes" rows="5" cols="100"><?php echo $this->beer->notes ?></textarea></br>
          Available: <input type="checkbox" name="available" <?php echo $this->beer->available == True ? "checked" : ""?>></br>

          <input type="hidden" name="action" value="<?php echo $this->action ?>">

          <input type="submit" value="<?php echo ($this->beer->beer_id == "") ? 'Add Beer' : 'Edit Beer' ?>">
        </form>
      <?php
    }
}

endif;
?>