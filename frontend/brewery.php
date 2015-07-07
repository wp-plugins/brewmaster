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
* Brewery Display code for beer list / brewery overview
* 
* @author Nate Eklund 
*  
*/

function bm_beer_list_html()
{    
  global $wpdb;

  $breweryName            =    get_option('bm_breweryName');
  $displayUnavailable     =    get_option('bm_displayUnavailable');
  $separateUnavailable    =    get_option('bm_separateUnavailable');
  $wordForAvailable       =    get_option('bm_wordForAvailable');
  $wordforUnavailable     =    get_option('bm_wordforUnavailable');
  $beerStatType           =    get_option('bm_beerStatType');
  $averageBatchStats      =    get_option('bm_averageBatchStats');

  $html = "<div id=\"BM_breweryDisplay\">
            <H2>Welcome to $breweryName</H2>";

  $beer_list = $beer_list = $wpdb->get_results('SELECT beer_id,beer_name,abv,ibus,available,page_id FROM '
                                               . $wpdb->bmbeers
                                               . " ORDER BY beer_name DESC, available DESC");
  
  //If we are calculating beer stats from batches we need get the "real" values
  if($beerStatType == 'Real')
  {
    require_once(dirname(__FILE__) . "/beerdisplay.php");  //GetRealAvailability function
    foreach($beer_list as $beer)
    {
      $realABV = GetRealABV($beer->beer_id, $averageBatchStats);
      $beer->abv =  $realABV> -1 ? $realABV : $beer->abv;
      
      $realIBUs = GetRealIBUs($beer->beer_id, $averageBatchStats);
      $beer->ibus = $realIBUs > -1 ? $realIBUs : $beer->ibus;
      
      $beer->available = GetRealAvailability($beer->beer_id);
    }
  }

  if($displayUnavailable == '1')
  {
      if($separateUnavailable == '1')
      {
        $availableList = array();
        $unavailableList = array();
        foreach($beer_list as $beer)
        {
          if($beer->available == '1')
          {
            $availableList[] = $beer;
          }
          else
          {
            $unavailableList[] = $beer;
          }
        }
        
        $html .= "<H3 id=\"BM_ListHeader\">$wordForAvailable</H3>";
        $html .= fetch_beerlist_list_html($availableList);

        $html .= "</br><H3 id=\"BM_ListHeader\">$wordforUnavailable</H3>";
        $html .= fetch_beerlist_list_html($unavailableList);
      }
      else
      {
        $html .= "<H3 id=\"BM_ListHeader\">Our Brews</H3>";
        $html .= fetch_beerlist_list_html($beer_list);
      }
  }
  else
  {
    $availableList = array();
    foreach($beer_list as $beer)
    {
      if($beer->available == '1')
      {
        $availableList[] = $beer;
      }
    }

    $html .= "<H3 id=\"BM_ListHeader\">Our Brews</H3>";

    $html .= fetch_beerlist_list_html($availableList);
    
    $html .= "</div>";
  }

  return $html;
}

function fetch_beerlist_table_html($beer_list)
{
    $html = "";
    
    if($beer_list)
    {
        $html = "<table>
                    <tr>
                        <th>Beer Name</th>
                        <th>ABV</th>
                        <th>IBUs</th>
                        <th>Available</th>
                    </tr>";
        
        foreach($beer_list as $beer)
        {
            $html .= bm_beerlist_item_html($beer);
        }
        
        $html .= "</table>";
    }
    else
    {
        $html = "No beers found.";
    }
    
    return $html;
}

function bm_beerlist_item_html($beer)
{
    $wordForAvailable       =    get_option('bm_wordForAvailable');
    $wordforUnavailable     =    get_option('bm_wordforUnavailable');
    
    $html = "";
    
    if(!isset($beer->beer_name))
        $beer->beer_name = '';
    if(!isset($beer->abv))
        $beer->abv = '';
    if(!isset($beer->ibus))
        $beer->ibus = '';
    if(!isset($beer->available))
        $beer->available = '';
        
    $beer_url = get_permalink($beer->page_id);
    $available = $beer->available == '1' ? $wordForAvailable : $wordforUnavailable;

    $html .= "<tr>
                <td><a href='{$beer_url}'>$beer->beer_name</a></td>
                <td>$beer->abv</td>
                <td>$beer->ibus</td>
                <td>$available</td>
              </tr>";
              
    return $html;
}

function fetch_beerlist_list_html($beer_list)
{
    $html = "";
    
    if($beer_list)
    {
        $html = "<ul id=\"BM_beerList\">";
        
        foreach($beer_list as $beer)
        {
            $html .= bm_beerlist_list_item_html($beer);
        }
        
        $html .= "</ul>";
    }
    else
    {
        $html = "No beers found.";
    }
    
    return $html;
}

function bm_beerlist_list_item_html($beer)
{
    $wordForAvailable       =    get_option('bm_wordForAvailable');
    $wordforUnavailable     =    get_option('bm_wordforUnavailable');
    
    $html = "";
    
    if(!isset($beer->beer_name))
        $beer->beer_name = '';
    if(!isset($beer->abv))
        $beer->abv = '';
    if(!isset($beer->ibus))
        $beer->ibus = '';
    if(!isset($beer->available))
        $beer->available = '';
        
    $beer_url = get_permalink($beer->page_id);
    $available = $beer->available == '1' ? $wordForAvailable : $wordforUnavailable;

    $html .= "<li id=\"BM_beerListItem\">
                <H4 id=\"BM_beerListItemTitle\"><a href='{$beer_url}'>$beer->beer_name</a></H4>
                $available
                <div id=\"BM_beerListItemStats\">
                  ABV: $beer->abv
                  IBUs: $beer->ibus
                </div>
              </li>";
              
    return $html;
}


?>