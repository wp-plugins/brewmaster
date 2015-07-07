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
* Brewery Display code for beer individual beers
* 
* @author Nate Eklund 
*  
*/

function bm_batch_html($batch_id)
{    
  global $wpdb;

  $wordForAvailable       =    htmlspecialchars(get_option('bm_wordForAvailable'));
  $wordforUnavailable     =    htmlspecialchars(get_option('bm_wordforUnavailable'));

  $html = "<div id=\"BM_batchDisplay\">";

  global $brewmasterdb;

  $batch = null;
  if(empty($batch_id))
  {
    return $html;
  }

  $batch = $brewmasterdb->find_batch($batch_id);

  $beer = $wpdb->get_row($wpdb->prepare("SELECT beer_name FROM $wpdb->bmbeers WHERE beer_id=%d", $batch->beer_id));
  $beer_name = '';
  if($beer)
  {
    $beer_name = $beer->beer_name;
  }

  $html .= "<H2 id=\"BM_batchBatchHeader\">$batch->batch_name</H2>";
  $html .= "<b>Batch " . $batch->batch_number . empty($beer_name) ? "" : " of " . htmlspecialchars($beer_name);
  $available = $batch->available == '1' ? $wordForAvailable : $wordforUnavailable;
  $html .= "<span id=\"BM_batchAvailability\">" . $available . "</span>";

  $html .= "<div id=\"BM_batchBaseStats\">";
    $html .= "<div id=\"BM_batchABV\"><b>ABV:</b>" . $batch->abv . "</div>";
    $html .= "<div id=\"BM_batchIBUs\"><b>IBUs:</b>" . $batch->ibus . "</div>";
  
    if($batch->original_gravity > 0)
    {
      $html .= "<div id=\"BM_batchOG\"><b>Original Gravity: </b>" . $batch->original_gravity . "</div>";
    }
    if($batch->final_gravity > 0)
    {
      $html .= "<div id=\"BM_batchFG\"><b>Final Gravity: </b>" . $batch->final_gravity . "</div>";
    }
    if($batch->batch_volume > 0)
    {
      $html .= "<div id=\"BM_batchVolume\"><b>Batch Volume: </b>" . $batch->batch_volume . 
                  " " . $batch->batch_volume_units . 
               "</div>";
    }
  $html .= "</div>"; //BM_batchBaseStats

  $html .= "<div id=\"BM_batchDates\">";
    $html .= "</br><b>Start Date: </b>" . $batch->start_date;
    if($batch->secondary_date != '0000-00-00')
    {
      $html .= "</br><b>Secondary Date: </b>" .  $batch->secondary_date;
    }
    if($batch->tertiary_date != '0000-00-00')
    {
      $html .= "</br><b>Tertiary Date: </b>" . $batch->tertiary_date;
    }
  
    $html .= "</br><b>Bottle/Keg Date: </b>" . $batch->bottle_date;
  
    if($batch->finish_date != '0000-00-00')
    {
      $html .= "</br><b>Finish Date: </b>" . $batch->finish_date;
    }
  $html .= "</div>"; //BM_batchDates
  
  $html .= "<div id=\"BM_batchProcessInfo\">";
    $html .= "</br><b>Mash Temp: </b>" . $batch->mash_temp;
    $html .= "</br><b>Mash Volume: </b>" . $batch->mash_volume . " " . $batch->mash_volume_units;
    $html .= "</br><b>Sparge Temp: </b>" . $batch->sparge_temp;
    $html .= "</br><b>Sparge Volume: </b>" . $batch->sparge_volume . " " . $batch->sparge_volume_units;
    $html .= "</br><b>Pre-Boil Gravity: </b>" . $batch->preboil_gravity;
    $html .= "</br><b>Pre-Boil Volume: </b>" . $batch->preboil_volume . " " . $batch->preboil_volume_units;
  $html .= "</div>"; //BM_batchProcessInfo
  
  $html .= "<div id=\"BM_beerNotes\">
              <b>Notes: </b><p>" . htmlspecialchars($batch->notes) . 
           "</div>";

  $html .= "</div>";

  return $html;
}

function fetch_batchlist_table_html($batch_list)
{
  $html = "";

  if($batch_list)
  {
    $html = "<table id=\"BM_batchListDisplay\">
              <tr>
                <th>Batch Name</th>
                <th>ABV</th>
                <th>IBUs</th>
                <th>Available</th>
                <th>Start Date</th>
              </tr>";

    foreach($batch_list as $batch)
    {
      $html .= bm_batchlist_item_html($batch);
    }

    $html .= "</table>";
  }
  else
  {
    $html = "</br>No batches found.";
  }
  
  return $html;
}

function bm_batchlist_item_html($batch)
{
  $wordForAvailable   = htmlspecialchars(get_option('bm_wordForAvailable'));
  $wordforUnavailable = htmlspecialchars(get_option('bm_wordforUnavailable'));

  $html = "";

  if(!isset($batch->batch_name))
    $batch->batch_name = '';
  if(!isset($batch->abv))
    $batch->abv = '';
  if(!isset($batch->ibus))
    $batch->ibus = '';
  if(!isset($batch->available))
    $batch->available = '';

  $available = $batch->available == '1' ? $wordForAvailable : $wordforUnavailable;
  $batch_url = get_permalink($batch->page_id);

  $html .= "<tr id=\"BM_batchListItem\">
              <td><a href='{$batch_url}'>".htmlspecialchars($batch->batch_name)."</a></td>
              <td>$batch->abv</td>
              <td>$batch->ibus</td>
              <td>$available</td>
              <td>$batch->start_date</td>
            </tr>";

  return $html;
}

function fetch_batchlist_list_html($batch_list)
{
  $html = "";

  if($batch_list)
  {
    $html = "<ul id=\"BM_batchListDisplay\">";

    foreach($batch_list as $batch)
    {
      $html .= bm_batchlist_list_item_html($batch);
    }

    $html .= "</ul>";
  }
  else
  {
    $html = "<div>No batches found.</div>";
  }
  
  return $html;
}

function bm_batchlist_list_item_html($batch)
{
  $wordForAvailable   = htmlspecialchars(get_option('bm_wordForAvailable'));
  $wordforUnavailable = htmlspecialchars(get_option('bm_wordforUnavailable'));

  $html = "";

  if(!isset($batch->batch_name))
    $batch->batch_name = '';
  if(!isset($batch->abv))
    $batch->abv = '';
  if(!isset($batch->ibus))
    $batch->ibus = '';
  if(!isset($batch->available))
    $batch->available = '';

  $available = $batch->available == '1' ? $wordForAvailable : $wordforUnavailable;
  $batch_url = get_permalink($batch->page_id);

  $html .= "<li id=\"BM_batchListItem\">
              <h4 id=\"BM_batchListItemTitle\"><a href='{$batch_url}'>".htmlspecialchars($batch->batch_name)."</a></h4>
              <span id=\"BM_batchListItemAvailable\">$available</span>
              <span id=\"BM_batchListItemStartDate\">$batch->start_date</span>
              <div id=\"BM_batchListItemStats\">
                <b>ABV:</b>$batch->abv
                <b>IBUs:</b>$batch->ibus
              </div>
            </li>";

  return $html;
}

?>