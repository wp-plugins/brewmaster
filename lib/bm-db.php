<?php
if ( !class_exists('brewmasterdb') ) :
 
include_once (BM_ABSPATH . '/lib/batch.php');
include_once (BM_ABSPATH . '/admin/options.php');
 
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
 * BrewMaster Database Class
 *
 * @author Nate Eklund
 *
 * @since 1.0.0
 */
class brewmasterdb
{
    /**
     * Init the Database Abstraction layer for BrewMaster
     *
     */
    function __construct()
    {
        global $wpdb;

        $this->beers    = array();
        $this->batches  = array();
        $this->paged    = array();

        register_shutdown_function(array(&$this, '__destruct'));

    }

    /**
     * PHP5 style destructor and will run when database object is destroyed.
     *
     * @return bool Always true
     */
    function __destruct()
    {
        return true;
    }
    
    /**
     * Return the number of beers currently in the database
     */
    function count_beers()
    {
        global $wpdb;
        $beer_count = $wpdb->get_var("SELECT COUNT(beer_id) FROM $wpdb->bmbeers");
        return $beer_count;
    }
    
    /**
     * Get a beer given its ID
     *
     * @param int|string $id or $slug
     * @return A bmBeer object (null if not found)
     */
    function find_beer( $id )
    {
        global $wpdb;

        if( is_numeric($id) )
        {
            if ( $beer = wp_cache_get($id, 'bm_beers') )
                return $beer;

            $beer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bmbeers WHERE 
            	beer_id = %d", $id ) );

        } 
        else
        {
          $beer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bmbeers WHERE 
              slug = %s", $id ) );
        }

        if ($beer)
        {
            wp_cache_add($id, $beer, 'bm_beers');

            return $beer;
        } 
        else
        {
          return false;
        }
    }
    
    /**
     * This function returns all information about a beer and the batches of it
     *
     * @param int|string $id or $name
     * @param string $order_by
     * @param string $order_dir (ASC |DESC)
     * @param bool $exclude
     * @param int $limit number of paged beers, 0 shows all beers
     * @param int $start the start index for paged beers
     * @param bool $json remove the key for associative array in json request
     * @return An array containing the bmBatch objects representing the batches of the beer.
     */
    function get_beer($id, $order_by = 'beer_name', $order_dir = 'ASC', $exclude = true, 
    	$limit = 0, $start = 0, $json = false)
    {
        global $wpdb;

        // init the beer as empty array
        $beer = array();
        $i = 0;

        // Check for the exclude setting
        $exclude_clause = '';

        // Say no to any other value
        $order_dir       = ( $order_dir == 'DESC') ? 'DESC' : 'ASC';
        $order_by        = ( empty($order_by) ) ? 'beer_name' : $order_by;
        $order_clause    = "ABS(tt.{$order_by}) {$order_dir}, tt.{$order_by} {$order_dir}";

        // Should we limit this query ?
        $limit_by  = ( $limit > 0 ) ? 'LIMIT ' . intval($start) . ',' . intval($limit) : '';

        // Query database
        if( is_numeric($id) )
            $result = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS tt.*, 
            	t.beer_id FROM $wpdb->bmbeers AS t INNER JOIN $wpdb->bmbatches AS tt ON 
            	t.beer_id = tt.beer_id WHERE t.beer_id = %d {$exclude_clause} 
            	ORDER BY {$order_clause} {$limit_by}", $id ), OBJECT_K );
        else
            $result = $wpdb->get_results( $wpdb->prepare( "SELECT SQL_CALC_FOUND_ROWS tt.*, 
            	t.beer_id FROM $wpdb->bmbeers AS t INNER JOIN $wpdb->bmbatches AS tt ON 
            	t.beer_id = tt.beer_id WHERE t.slug = %s {$exclude_clause} 
            	ORDER BY {$order_clause} {$limit_by}", $id ), OBJECT_K );

        // Count the number of batches and calculate the pagination
        if ($limit > 0)
        {
            $this->paged['total_objects'] = intval ( $wpdb->get_var( "SELECT FOUND_ROWS()" ) );
            $this->paged['objects_per_page'] = max ( count( $result ), $limit );
            $this->paged['max_objects_per_page'] = ( $limit > 0 ) ? ceil( $this->paged[
            				'total_objects'] / intval($limit)) : 1;
        }

        // Build the object
        if ($result)
        {
            // Now added all batch data
            foreach ($result as $key => $value)
            {
                // due to a browser bug we need to remove the key for associative array for 
                // json request
                // (see http://code.google.com/p/chromium/issues/detail?id=883)
                if ($json) $key = $i++;
                $beer[$key] = new bmBatch( $value ); // keep in mind each request 
                									 //require 8-16 kb memory usage
            }
        }

        // Could not add to cache, the structure is different to find_beer() cache_add, 
        // need rework
        //wp_cache_add($id, $beer, 'bm_beers');

        return $beer;
    }
    
    /**
     * This function returns all the IDs of batches of the specified beer
     *
     * @param int $id
     * @param string $orderby
     * @param string $order (ASC |DESC)
     * @return An array containing the bmBatch IDs representing the batches of the beer.
     */
    function get_ids_from_beer($id, $order_by = 'batch_number', $order_dir = 'ASC')
    {
      global $wpdb;

      $order_dir = ( $order_dir == 'DESC') ? 'DESC' : 'ASC';
      $order_by  = ( empty($order_by) ) ? 'batch_number' : $order_by;

      if( is_numeric($id) )
      {
        $query = $wpdb->prepare( "SELECT batch_id FROM $wpdb->bmbatches ".
                                 "WHERE beer_id = %d ORDER BY {$order_by} $order_dir", $id );
      }

      return $result;
    }
    
    /**
     * Delete a beer AND all the batches associated with it!
     *
     * @id The beer ID
     */
    function delete_beer( $id )
    {
      global $wpdb;
      
      //Delete its display page.
      $beer = $this->find_beer($id);
      wp_delete_post($beer->page_id, true);
      
      //Delete its batches
      $batch_ids = $this->get_ids_from_beer($id);
      foreach($batch_ids as $batch_id)
      {
        $this->delete_batch($batch_id);
      }

      //Delete the beer
      $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->bmbeers WHERE beer_id = %d", $id) );

      wp_cache_delete($id, 'bm_beers');

      return true;
    }
    
    /**
     * brewmasterdb::update_beer() - Update a beer in the database
     *
     * @since V1.7.0
     * @param int $beer The beer to update
     * @return bool result of update query
     */
    function update_beer($beer)
    {
      global $wpdb;

      $sql = array();
      $values = array();

      $id = (int) $beer->beer_id;

      // create the sql parameter "name = value"
      foreach ($beer as $key => $value)
      {
        $sql[] = $key . " = %s";
        $values[] = $value;
      }

      // create the final string
      $sql = implode(', ', $sql);
      
      if ( !empty($sql) && $id != 0)
      {
        $result = $wpdb->query($wpdb->prepare("UPDATE $wpdb->bmbeers SET $sql WHERE beer_id = $id", $values));
      }

      if($result != false)
      {
        //Update the title of the beer's display page
        $post = array(
          'ID' => $beer->page_id,
          'post_title' => $beer->beer_name
        );
        wp_update_post($post);

        wp_cache_delete($id, 'bm_beers');
      }

      return $result;
    }
    
    function set_beer_pageid($beer_id, $page_id)
    {
    	global $wpdb;

        $sql = array();
        $beer_id = (int) $beer_id;
        $page_id = (int) $page_id;

        if ($beer_id > 0)
        {
            $result = $wpdb->query( "UPDATE $wpdb->bmbeers SET page_id='$page_id' WHERE beer_id='$beer_id'" );
        }

        wp_cache_delete($beer_id, 'bm_beers');

        return $result;
    }
    
    /**
    * Add a beer to the database
    *
    * @return bool result or the ID of the inserted beer
    */
    function add_beer($name = '', $description = '', $abv = 0.0, $ibus = 0, $recipe_url = '', 
    	$notes = '', $available = False)
    {
        global $wpdb;

        // slug must be unique, we use the name for that
        $slug = brewmasterdb::get_unique_slug( sanitize_title( $name ), 'beer' );

        if ( false === $wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->bmbeers (beer_name, 
        											 slug, description, abv, ibus, recipe_url, 
        											 notes, available)
                                                     VALUES (%s, %s, %s, %f, %d, %s, %s, %s)", 
                                                     $name, $slug, $description, $abv, 
                                                     $ibus, $recipe_url, $notes, $available)))
        {
            return false;
        }

        $beerID = (int) $wpdb->insert_id;

        //and give me the new id
        return $beerID;
    }
    
    /**
     * Return the number of batches currently in the database
     */
    function count_batches()
    {
        global $wpdb;
        $batch_count = $wpdb->get_var("SELECT COUNT(batch_id) FROM $wpdb->bmbatches");
        return $batch_count;
    }
    
    /**
     * Get a batch given its ID
     *
     * @param int|string $id or $slug
     * @return A bmBatch object (null if not found)
     */
    function find_batch( $id )
    {
        global $wpdb;

        if( is_numeric($id) )
        {
            if ( $batch = wp_cache_get($id, 'bm_batches') )
                return $batch;

            $batch = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bmbatches 
            										  WHERE batch_id = %d", $id ) );

        } else
            $batch = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bmbatches 
            									      WHERE slug = %s", $id ) );

        // Build the object from the query result
        if ($batch)
        {
            // it was a bad idea to use a object, stripslashes_deep() could not used here, 
            // learn from it
            $batch->batch_name = stripslashes($batch->batch_name);
            $batch->notes  = stripslashes($batch->notes);

            //TODO:Possible failure , $id could be a number or name
            wp_cache_add($id, $batch, 'bm_batches');

            return $batch;
        } else
            return false;
    }
    
    /**
     * Delete a batch
     *
     * @id The batch ID
     */
    function delete_batch( $id )
    {
      global $wpdb;

      //Delete its display page.
      $batch = $this->find_batch($id);
      wp_delete_post($batch->page_id, true);

      //Delete the batch
      $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->bmbatches WHERE batch_id = %d", $id) );

      wp_cache_delete($id, 'bm_batches');

      return true;
    }
    
    /**
     * brewmasterdb::update_batch() - Update a batch in the database
     *
     * @since V1.7.0
     * @param int $id   id of the batch
     * @param (optional) string $title or name of the batch
     * @param (optional) string $path
     * @param (optional) string $description
     * @param (optional) int $pageid
     * @param (optional) int $previewpic
     * @param (optional) int $author
     * @return bool result of update query
     */
    function update_batch($batch)
    {
      global $wpdb;

      $sql = array();
      $id = (int) $batch->batch_id;

      // create the sql parameter "name = value"
      foreach ($batch as $key => $value)
      {
        if ($value !== false)
        {
          $sql[] = $key . " = '" . $value . "'";
        }
      }

      // create the final string
      $sql = implode(', ', $sql);

      if ( !empty($sql) && $id != 0)
      {
        $result = $wpdb->query( "UPDATE $wpdb->bmbatches SET $sql WHERE batch_id = $id" );
      }

      if($result != false)
      {
        //Update the title of the batch's display page
        $post = array(
          'ID' => $batch->page_id,
          'post_title' => $batch->batch_name
        );
        wp_update_post($post);

        wp_cache_delete($id, 'bm_batches');
      }
      

      return $result;
    }
    
    function set_batch_pageid($batch_id, $page_id)
    {
      global $wpdb;

      $sql = array();
      $batch_id = (int) $batch_id;
      $page_id = (int) $page_id;

      if ($batch_id > 0)
      {
        $result = $wpdb->query( "UPDATE $wpdb->bmbatches SET page_id='$page_id' WHERE batch_id='$batch_id'" );
      }

      wp_cache_delete($batch_id, 'bm_batches');

      return $result;
    }
    
    /**
    * Add a batch to the database
    *
    * @since V1.7.0
    * @param (optional) string $title or name of the batch
    * @param (optional) string $path
    * @param (optional) string $description
    * @param (optional) int $pageid
    * @param (optional) int $previewpic
    * @param (optional) int $author
    * @return bool result of the ID of the inserted batch
    */
    function add_batch($beerID, $batchName = '', $batchNumber = '', $startDate = '', 
    				   $bottleDate = '', $finishDate = '', $abv = 0.0, $IBUs = 0, 
    				   $available = False, $originalGravity = 0.0, $finalGravity = 0.0, 
    				   $batchVolume = 0.0, $batchVolumeUnits = '', $mash_temp = 0.0, 
    				   $mash_volume = 0.0, $mash_volume_units = '', $sparge_temp = 0.0, 
    				   $sparge_volume = 0.0, $sparge_volume_units = '',
                       $preboil_gravity = 0.0, $preboil_volume = 0.0, 
                       $preboil_volume_units = '',
                       $secondary_date = '', $tertiary_date = '', $notes = '')
    {
        global $wpdb;

        // slug must be unique, we use the name for that
        $slug = brewmasterdb::get_unique_slug( sanitize_title( $batchName ), 'batch' );

        if ( false === $wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->bmbatches (beer_id, batch_name, slug, batch_number, 
                                                                                   start_date, bottle_date, finish_date, abv, ibus, available,
                                                                                   original_gravity, final_gravity, batch_volume, batch_volume_units,
                                                                                   mash_temp, mash_volume, mash_volume_units,
                                                                                   sparge_temp, sparge_volume, sparge_volume_units,
                                                                                   preboil_gravity, preboil_volume, preboil_volume_units,
                                                                                   secondary_date, tertiary_date, notes)
                                                     VALUES (%s, %s, %s, %d, 
                                                             %s, %s, %s, %f, %d, %s, 
                                                             %f, %f, %f, %s,
                                                             %f, %f, %s,
                                                             %f, %f, %s,
                                                             %f, %f, %s,
                                                             %s, %s, %s)", 
                                                             $beerID, $batchName, $slug, $batchNumber, 
                                                             $startDate, $bottleDate, $finishDate, $abv, $IBUs, $available,
                                                             $originalGravity, $finalGravity, $batchVolume, $batchVolumeUnits,
                                                             $mash_temp, $mash_volume, $mash_volume_units,
                                                             $sparge_temp, $sparge_volume, $sparge_volume_units,
                                                             $preboil_gravity, $preboil_volume, $preboil_volume_units,
                                                             $secondary_date, $tertiary_date, $notes)))
        {
            return false;
        }

        $batchID = (int) $wpdb->insert_id;

        //and give me the new id
        return $batchID;
    }
    
    /**
     * Computes a unique slug for the beer or batch when given the desired slug.
     *
     * @since 1.7.0
     * @author taken from WP Core includes/post.php
     * @param string $slug the desired slug (post_name)
     * @param string $type ('beer' or 'batch')
     * @param int (optional) $id of the object, so that it's not checked against itself
     * @return string unique slug for the object, based on $slug (with a -1, -2, etc. suffix)
     */
    function get_unique_slug( $slug, $type, $id = 0 )
    {
        global $wpdb;

        switch ($type)
        {
            case 'beer':
                $check_sql = "SELECT slug FROM $wpdb->bmbeers WHERE slug = %s 
                								AND NOT beer_id = %d LIMIT 1";
            break;
            case 'batch':
                $check_sql = "SELECT slug FROM $wpdb->bmbatches WHERE slug = %s 
                								AND NOT batch_id = %d LIMIT 1";
            break;
            default:
                return false;
        }

        //if you didn't give us a name we take the type
        $slug = empty($slug) ? $type: $slug;

        // Slugs must be unique across all objects.
        $slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $id ) );

        if ( $slug_check )
        {
            $suffix = 2;
            do {
                $alt_name = substr ($slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
                $slug_check = $wpdb->get_var( $wpdb->prepare($check_sql, $alt_name, $id ) );
                $suffix++;
            } while ( $slug_check );
            $slug = $alt_name;
        }

        return $slug;
    }
}

if ( ! isset($GLOBALS['brewmasterdb']) ) {
    /**
     * Initate the Brewmaster Database Object
     * @global object $brewmasterdb Creates a new brewmasterdb object
     */
    unset($GLOBALS['brewmasterdb']);
    $GLOBALS['brewmasterdb'] = new brewmasterdb() ;
}

endif;
?>