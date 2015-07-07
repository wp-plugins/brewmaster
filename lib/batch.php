<?php
if ( !class_exists('bmBatch') ) :
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
* Batch PHP class for the WordPress plugin BrewMaster
* 
* @author Nate Eklund 
*  
*/
class bmBatch
{
    
    /**** Public variables ****/    
    var $errmsg          =    '';            // Error message to display, if any
    var $error           =    FALSE;         // Error state
    var $permalink       =    '';            // Permalink
    
    /**** Batch Data ****/
    var $batch_id             =    0;       //Batch ID
    var $beer_id              =    0;       //Beer ID
    var $batch_name           =    '';      //Batch Name   
    var $slug                 =    '';      //Slug
    var $batch_number         =    '';      //Batch Number 
    var $start_date           =    '';      //Batch Start Date
    var $bottle_date          =    '';      //Date this batch was bottled/kegged
    var $finish_date          =    '';      //Batch Finish Date
    var $abv                  =    0.0;     //Alcohol By Volume   
    var $ibus                 =    0;       //IBUs (bitterness)  
    var $available            =    False;   //Is this batch available?
    var $original_gravity     =    0.0;
    var $final_gravity        =    0.0;
    var $batch_volume         =    0.0;
    var $batch_volume_units   =    '';
    var $mash_temp            =    0.0;
    var $mash_volume          =    0.0;
    var $mash_volume_units    =    '';
    var $sparge_temp          =    0.0;
    var $sparge_volume        =    0.0;
    var $sparge_volume_units  =    '';
    var $preboil_gravity      =    0.0;
    var $preboil_volume       =    0.0;
    var $preboil_volume_units =    '';
    var $secondary_date       =    '';
    var $tertiary_date        =    '';
    var $notes                =    '';      //Notes
    var $pageID               =    '';

    /**** Batch Data ****/
    var $batches = array();
        
    /**
     * Constructor
     * 
     * @param object $batch The bmBatch object representing the batch in question
     * @return void
     */
    function bmBatch($batch = NULL)
    {
        if (is_null($batch))
            return;
        
        //This must be an object
        $batch = (object) $batch;

        // Build up the object
        foreach ($batch as $key => $value)
            $this->$key = $value ;
        
        // Finish initialization
        $this->batchID              =    $batch->batchID;
        $this->beerID               =    $batch->beerID;
        $this->batchName            =    $batch->batchName;  
        $this->slug                 =    $batch->slug;
        $this->batchNumber          =    $batch->batchNumber;
        $this->startDate            =    $batch->startDate;
        $this->bottleDate           =    $batch->bottleDate;
        $this->finishDate           =    $batch->finishDate;
        $this->originalGravity      =    $batch->originalGravity;
        $this->finalGravity         =    $batch->finalGravity;
        $this->abv                  =    $batch->abv;
        $this->IBUs                 =    $batch->IBUs;
        $this->batchVolume          =    $batch->batchVolume;
        $this->batchVolumeUnits     =    $batch->batchVolumeUnits;
        $this->available            =    $batch->available;
        $this->mash_temp            =    $batch->mash_temp; 
        $this->mash_volume          =    $batch->mash_volume;
        $this->mash_volume_units    =    $batch->mash_volume_units;
        $this->sparge_temp          =    $batch->sparge_temp;
        $this->sparge_volume        =    $batch->sparge_volume;
        $this->sparge_volume_units  =    $batch->sparge_volume_units;
        $this->preboil_gravity      =    $batch->preboil_gravity;
        $this->preboil_volume       =    $batch->preboil_volume;
        $this->preboil_volume_units =    $batch->preboil_volume_units;
        $this->secondary_date       =    $batch->secondary_date;
        $this->tertiary_date        =    $batch->tertiary_date;
        $this->notes                =    $batch->notes;
        $this->pageID               =    $batch->pageID;
        
        //Get Link
        $this->permalink    = get_permalink($this->pageID);
        
        // Note wp_cache_add will increase memory needs (4-8 kb)
        // wp_cache_add($this->batch_id, $this, 'bm_batchs');
        // Get tags only if necessary
        unset($this->tags);
    }
    
    /**
     * Get the tags associated to this batch
     */
    function get_tags()
    {
        if ( !isset($this->tags) )
            $this->tags = wp_get_object_terms($this->pid, 'bm_tag', 'fields=all');

        return $this->tags;
    }
    
    /**
     * Get the permalink to the batch
     * TODO Get a permalink to a page presenting the batch
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