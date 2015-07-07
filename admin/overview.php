<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

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
 * bm_admin_overview()
 *
 * Add the admin overview the dashboard style
 * @return mixed content
 */
function bm_admin_overview()
{
    ?>
    <div class="wrap bm-wrap">
        <h2>BrewMaster Overview</h2>
        <p>
            <?php global $brewmasterdb; echo intval($brewmasterdb->count_beers())?> beers.</br>
            <?php global $brewmasterdb; echo intval($brewmasterdb->count_batches())?> batches.
        </p>
    </div>

    <?php
}
?>