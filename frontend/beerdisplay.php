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

function bm_beer_html($beer_id)
{    
  global $wpdb;

  $breweryName            =    get_option('bm_breweryName');
  $displayUnavailable     =    get_option('bm_displayUnavailable');
  $separateUnavailable    =    get_option('bm_separateUnavailable');
  $wordForAvailable       =    htmlspecialchars(get_option('bm_wordForAvailable'));
  $wordforUnavailable     =    htmlspecialchars(get_option('bm_wordforUnavailable'));
  $beerStatType           =    get_option('bm_beerStatType');
  $averageBatchStats      =    get_option('bm_averageBatchStats');
  $listBatches            =    get_option('bm_listBatches');

  $html = "<div id=\"BM_beerDisplay\">";

  global $brewmasterdb;

  $beer = null;
  if(empty($beer_id))
  {
    return $html;
  }

  $beer = $brewmasterdb->find_beer($beer_id);

  $html .= "<H2 id=\"BM_beerHeader\">" . htmlspecialchars($beer->beer_name) . " by " . htmlspecialchars($breweryName) . "</H2>";
  $available = $beerStatType == 'Real' ? GetRealAvailability($beer_id) : $beer->available;
  $html .= "<span id=\"BM_beerAvailability\">" . $available == '1' ? $wordForAvailable : $wordforUnavailable . "</span>";
  
  $html .= "<div id=\"BM_beerDescription\">" . htmlspecialchars($beer->description) . "</div>";
  
  //Display Stats based on stat type setting
  $html .= "<div id=\"BM_beerStatsDisplay\">";
  if($beerStatType == 'Target')
  {
    $html .= "<div id=\"BM_beerABV\"><b>ABV: </b>". $beer->abv . "</div>";
    $html .= "<div id=\"BM_beerIBUs\"><b>IBUs: </b>". $beer->ibus . "</div>";
  }
  else
  {
    $realABV = GetRealABV($beer_id, $averageBatchStats);
    $realABV = $realABV > -1 ? $realABV : $beer->abv;
    
    $realIBUs = GetRealIBUs($beer_id, $averageBatchStats);;
    $realIBUs = $realIBUs > -1 ? $realIBUs : $beer->ibus;
    
    if($beerStatType == 'Real')
    {
      $html .= "<div id=\"BM_beerABV\"><b>ABV:</b> " . $realABV . "</div>";
      $html .= "<div id=\"BM_beerIBUs\"><b>IBUs:</b> " . $realIBUs . "</div>";
    }
    else // Both
    {
      $html .= "<div id=\"BM_beerABV\"><b>Target ABV:</b> $beer->abv";
      $html .= "</br><b>Real ABV:</b> " . $realABV . "</div>";
      $html .= "<div id=\"BM_beerIBUs\"><b>Target IBUs:</b> $beer->ibus";
      $html .= "</br><b>Real IBUs:</b> " . $realIBUs . "</div>";
    }
  }
  
  $html .= "</div>";

  $html .= !empty($beer->recipe_url) ? "Recipe Link: <a id=\"BM_beerRecipeLink\" 
                                                href='".$beer->recipe_url."'>$beer->recipe_url</a>" : "";
  
  $html .= "<div id=\"BM_beerNotes\">
              <b>Notes:</b><p>" . htmlspecialchars($beer->notes) . "</p>
            </div>";

  if($listBatches == '1')
  {
    require_once(dirname(__FILE__) . "/batchdisplay.php");

    $html .= "</br><H3 id=\"BM_beerBatchListHeader\">Batches</H3>";
    if($displayUnavailable == '1')
    {
      if($separateUnavailable == '1')
      {
        $html .= "<b>$wordForAvailable</b>";
        $batch_list = $wpdb->get_results($wpdb->prepare('SELECT batch_id,beer_id,batch_name,start_date,finish_date,original_gravity,final_gravity,batch_volume,batch_volume_units,abv,ibus,available,page_id FROM ' . 
                                 $wpdb->bmbatches . ' WHERE beer_id=%s AND available="1" ORDER BY start_date DESC',
                                                        $beer_id));
        $html .= fetch_batchlist_list_html($batch_list);

        $html .= "</br><b>$wordforUnavailable</b>";
        $batch_list = $wpdb->get_results($wpdb->prepare('SELECT batch_id,beer_id,batch_name,start_date,finish_date,original_gravity,final_gravity,batch_volume,batch_volume_units,abv,ibus,available,page_id FROM ' . 
                                 $wpdb->bmbatches . ' WHERE beer_id=%s AND available="0" ORDER BY start_date DESC',
                                                       $beer_id));
        $html .= fetch_batchlist_list_html($batch_list);
      }
      else
      {
        $batch_list = $wpdb->get_results($wpdb->prepare('SELECT batch_id,beer_id,batch_name,start_date,finish_date,original_gravity,final_gravity,batch_volume,batch_volume_units,abv,ibus,available,page_id FROM ' . 
                                 $wpdb->bmbatches . ' WHERE beer_id=%s ORDER BY available DESC,start_date DESC',
                                                       $beer_id));
        $html .= fetch_batchlist_list_html($batch_list);
      }
    }
    else
    {
      $batch_list = $wpdb->get_results($wpdb->prepare('SELECT batch_id,beer_id,batch_name,start_date,finish_date,original_gravity,final_gravity,batch_volume,batch_volume_units,abv,ibus,available,page_id FROM ' . 
                               $wpdb->bmbatches . ' WHERE beer_id=%s AND available="1" ORDER BY start_date DESC',
                                                     $beer_id));
      $html .= fetch_batchlist_list_html($batch_list);
    }
  }
  
  $html .= "</div>";

  return $html;
}

function GetRealABV($beer_id, $averageBatchStats)
{
  $abv = 0.0;

  global $wpdb;

  if($averageBatchStats == '1')
  {
    $results = $wpdb->get_results($wpdb->prepare("SELECT AVG(abv) AS ABV FROM " 
                                                 . $wpdb->bmbatches 
                                                 . " WHERE beer_id=%s",
                                                 $beer_id));

    if($results[0]->ABV >0)
    {
      $abv = round($results[0]->ABV, 2);
    }
    else //Average should be above 0, if not set it to an invalid ABV
    {
      $abv = -1;
    }
  }
  else
  {
    $results = $wpdb->get_results($wpdb->prepare("SELECT abv FROM " 
                                                 . $wpdb->bmbatches 
                                                 . " WHERE beer_id=%s ORDER BY start_date DESC LIMIT 1",
                                                 $beer_id));
    if($results)
    {
      $abv = $results[0]->abv;
    }
    else //No batches found, return invalid ABV
    {
      $abv = -1;
    }
  }

  return $abv;
}

function GetRealIBUs($beer_id, $averageBatchStats)
{
    $ibus = 0.0;
    
    global $wpdb;
    
    if($averageBatchStats == '1')
    {
      $results = $wpdb->get_results($wpdb->prepare("SELECT AVG(ibus) AS IBUs FROM " 
                                                   . $wpdb->bmbatches 
                                                   . " WHERE beer_id=%s",
                                                   $beer_id));
      if($results[0]->IBUs > 0)
      {
        $ibus = round($results[0]->IBUs, 2);
      }
      else //Average should be above 0, if not set it to an invalid IBU value
      {
        $ibus = -1;
      }
    }
    else
    {
      $results = $wpdb->get_results($wpdb->prepare("SELECT ibus FROM " 
                                                   . $wpdb->bmbatches 
                                                   . " WHERE beer_id=%s ORDER BY start_date DESC LIMIT 1",
                                                   $beer_id));
      if($results)
      {
        $ibus = $results[0]->ibus;
      }
      else //No batches found, return invalid IBUs
      {
        $ibus = -1;
      }
    }
    
    return $ibus;
}

function GetRealAvailability($beer_id)
{
  $available = False;

  global $wpdb;
  $results = $wpdb->get_results($wpdb->prepare("SELECT available FROM " 
                                               . $wpdb->bmbatches 
                                               . " WHERE beer_id=%s ORDER BY available DESC LIMIT 1",
                                               $beer_id));

  if($results)
  {
    $available = $results[0]->available;
  }
  else //No batches found, this beer is not available!
  {
    $available = 0;
  }

  return $available;
}


?>