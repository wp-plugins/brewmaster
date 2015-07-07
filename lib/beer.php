<?php
if ( !class_exists('bmBeer') ) :
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
* Beer PHP class for the WordPress plugin BrewMaster
* 
* @author Nate Eklund 
*  
*/
class bmBeer
{
    
    /**** Public variables ****/    
    var $errmsg          =    '';            // Error message to display, if any
    var $error           =    FALSE;         // Error state
    var $permalink       =    '';            // Permalink
    
    /**** Beer Data ****/
    var $beerID      =    0;    //Beer ID
    var $beerName    =    '';   //Beer Name   
    var $slug        =    '';   //Slug
    var $description =    '';   //Beer Description  
    var $abv         =    0.0;  //Alcohol By Volume   
    var $IBUs        =    0;    //IBUs (bitterness)  
    var $recipeURL   =    '';   //Recipe URL
    var $notes       =    '';   //Notes
    var $pageID      =    '';

    /**** Batch Data ****/
    var $batches = array();
        
    /**
     * Constructor
     * 
     * @param object $beer The bmBeer object representing the beer in question
     * @return void
     */
    function bmBeer($beer = NULL)
    {
        if (is_null($beer))
            return;
        
        //This must be an object
        $beer = (object) $beer;

        // Build up the object
        foreach ($beer as $key => $value)
            $this->$key = $value ;
        
        // Finish initialization
        $this->$beerID      =    $beer->$beerID;
        $this->$beerName    =    $beer->$beerName;  
        $this->$slug        =    $beer->$slug;
        $this->$description =    $beer->$description;
        $this->$abv         =    $beer->$abv;
        $this->$IBUs        =    $beer->$IBUs;
        $this->$recipeURL   =    $beer->$recipeURL;
        $this->$notes       =    $beer->$notes;
        $this->$pageID      =    $beer->$pageID;
        
        //Get Link
        $this->permalink    = get_permalink($this->pageID);
        
        // Note wp_cache_add will increase memory needs (4-8 kb)
        // wp_cache_add($this->beer_id, $this, 'bm_beers');
        // Get tags only if necessary
        unset($this->tags);
    }
    
    /**
     * Get the tags associated to this beer
     */
    function get_tags()
    {
        if ( !isset($this->tags) )
            $this->tags = wp_get_object_terms($this->pid, 'bm_tag', 'fields=all');

        return $this->tags;
    }
    
    /**
     * Get the permalink to the beer
     */
    function get_permalink()
    {
        return $this->permalink; 
    }
    
    function __destruct()
    {
    }
}
endif;
?>