<?php
/*    
	This file is part of STFC.
	Copyright 2006-2007 by Michael Krauss (info@stfc2.de) and Tobias Gafner
		
	STFC is based on STGC,
	Copyright 2003-2007 by Florian Brede (florian_brede@hotmail.com) and Philipp Schmidt
	
    STFC is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    STFC is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


// ########################################################################################
// ########################################################################################
// Startup Konfig

// include game definitions, path url and so on
include_once('config.script.php');

ini_set('memory_limit', '600M');

if(!empty($_SERVER['SERVER_SOFTWARE'])) {
    echo 'The scheduler can only be called by CLI!'; exit;
}

define('TICK_LOG_FILE', $game_path . 'logs/moves_tick_'.date('d-m-Y', time()).'.log');
define('IN_SCHEDULER', true); // wir sind im scheduler...

// include commons classes and functions
include_once('commons.php');


// ########################################################################################
// ########################################################################################
// Init

$starttime = ( microtime(true) + time() );

include_once($game_path . 'include/sql.php');
include_once($game_path . 'include/global.php');
include_once($game_path . 'include/functions.php');
include_once($game_path . 'include/text_races.php');
include_once($game_path . 'include/race_data.php');
include_once($game_path . 'include/ship_data.php');
include_once($game_path . 'include/libs/moves.php');

$sdl = new scheduler("TICK-MOVES");
$db = new sql($config['server'].":".$config['port'], $config['game_database'], $config['user'], $config['password']); // create sql-object for db-connection

$game = new game();

$sdl->info('Starting Scheduler at '.date('d.m.y H:i:s', time()));
if(($cfg_data = $db->queryrow('SELECT * FROM config')) === false) {
    $sdl->fatal('Could not query tick data! ABORTED');
  exit;
}
$ACTUAL_TICK = $cfg_data['tick_id'];
$NEXT_TICK = ($cfg_data['tick_time'] - time());
$LAST_TICK_TIME = ($cfg_data['tick_time']-TICK_DURATION*60);
$STARDATE = $cfg_data['stardate'];

if($cfg_data['tick_stopped']) {
    $sdl->info('Finished Scheduler in '.round((microtime(true))-$starttime, 4).' secs. Tick has been stopped (Unlock in table "config")');
    exit;
}

if(empty($ACTUAL_TICK)) {
    $sdl->fatal('Finished Scheduler in '.round((microtime(true))-$starttime, 4).' secs. empty($ACTUAL_TICK) == true');
    exit;
}

/*
Example Job:

$sdl->start_job('Mine Job');

do something ... during error / message:
  $sdl->log('...');
best also - before, so it's apart from the other messages, also: $sdl->log('- this was not true');

$sdl->finish_job('Mine Job'); // terminates the timer

 */


// ########################################################################################
// ########################################################################################
// Moves Scheduler
$sdl->start_job('Moves Scheduler');
include('moves_main.php');
$sdl->finish_job('Moves Scheduler');


// ########################################################################################
// ########################################################################################
// Quit and close log

$db->close();
$sdl->info('Finished Scheduler in '.round((microtime(true))-$starttime, 4).' secs. Executed Queries: '.$db->i_query);

?>

