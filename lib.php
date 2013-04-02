<?php

/**
*
*
*/
function openmeetings_get_all_meetings($id){
	global $CFG, $USER;

	return get_records('block_om_session', 'blockid', $id);
}

/**
*
*
*/
function openmeetings_close_meeting($openmeetings){
	global $CFG;
	
	$om = get_record('block_om_session', 'id', $openmeetings->id);
	$om->started = 0;		
	$om->finish = 1;
	update_record('block_om_session', addslashes_recursive($om));		
}

/**
*
*
*/	
function openmeetings_is_valid($openmeetings){	
	$start = $openmeetings->timestarted;
	$end = $openmeetings->timestarted + (60 * $openmeetings->duration);
	
	$isvalid = true;
	if ($sessions = get_records('block_om_session', 'blockid', $openmeetings->blockid)){
		foreach($sessions as $session){
			if($session->finish != 1){
				$sessionstart = $session->timestarted;
				$sessionend = $session->timestarted + (60 * $session->duration);		
				if( ($end <= $sessionstart) || ($sessionend <= $start) ){
				} else {
					$isvalid = false;
				}
			}
		}
		return $isvalid;
	}
	return $isvalid;
}
	
?>