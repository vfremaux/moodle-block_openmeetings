<?php

/**
*
*
*/
function om_block_fix_charset($str){
	global $CFG;
	
	if (empty($CFG->openmeetings_cnxcharset) || ($CFG->openmeetings_cnxcharset == 'UTF-8')){
		return $str;
	} else {
		return mb_convert_encoding($str, $CFG->openmeetings_cnxcharset, 'auto');
	}
}

/**
* get the complete list of declared openmeetings servers from central configuration.
* Caches the list for further use.
*
*/
function get_available_servers(){
	global $CFG;
	static $SERVERS;

	if (isset($SERVERS)){
		return $SERVERS;
	} else {
		$servers = (array)json_decode(base64_decode(@$CFG->openmeetings_servers));	
		foreach($servers as $s){
			$SERVERS["$s->id"] = $s;
		}
	}
	
	return $SERVERS;
}

/**
* Retrieves the commplete configuration for a given OM server.
* @param int $serverid
*/
function get_server_info($servid){
	global $CFG;
		
	$servers = & get_available_servers();

	if (!isset($servers[$servid])) return null;
	
	if (empty($servers[$servid]->omhttpport)) $servers[$servid]->omhttpport = '5080';
	if (empty($servers[$servid]->omrtmpport)) $servers[$servid]->omrtmpport = '1935';
	
	return $servers[$servid];
}

/**
* Provides the allowed meeting duration list for meeting form
*/
function openmeetings_get_durations(){
	$enableoptions = array();
    $enableoptions[60]  = '60 '.get_string('minutes');
    $enableoptions[120]  = '120 '.get_string('minutes');
	$enableoptions[180]  = '180 '.get_string('minutes');
	$enableoptions[240]  = '240 '.get_string('minutes');
	return $enableoptions;
}

/**
* Provides the allowed meeting max number of users for meeting form
*/
function openmeetings_get_maxusers(){
	$enableoptions = array();
	$enableoptions[2]  = '2';
	$enableoptions[5]  = '5';
	$enableoptions[10]  = '10';
	$enableoptions[15]  = '15';
	$enableoptions[20]  = '20';
	$enableoptions[35]  = '35';
	$enableoptions[50]  = '50';	
	$enableoptions[100]  = '100';
	$enableoptions[200]  = '200';
	$enableoptions[500]  = '500';	
	$enableoptions[1000]  = '1000';				
	return $enableoptions;
}

/**
* Provides the allowed meeting room modes for meeting form
*/
function openmeetings_get_roommodes(){
	$enableoptions = array();
	$enableoptions[1] = get_string('modeconference', 'block_openmeetings');
	$enableoptions[2] = get_string('modeaudience', 'block_openmeetings');
	$enableoptions[3] = get_string('moderestricted', 'block_openmeetings');
	$enableoptions[0] = get_string('moderecording', 'block_openmeetings');
	return $enableoptions;	
}

/**
* Provides the allowed meeting moderation status for meeting form
*/
function openmeetings_get_moderations(){
	$enableoptions = array();
	$enableoptions[1] = get_string('moderatedroom', 'block_openmeetings');
	$enableoptions[2] = get_string('unmoderatedroom', 'block_openmeetings');						
	return $enableoptions;	
}

/**
* Provides the meeting language choice for meeting form
*/
function openmeetings_get_languages($serverversion){
	if ($serverversion == '1.6'){
		$langchoice = array (
	   		'1' => get_string('english','block_openmeetings'),
			'2' => get_string('deutsch','block_openmeetings'),
			// '3' => 'deutsch (2)','block_openmeetings'),
			'4' => get_string('french','block_openmeetings'),
			'5' => get_string('italian','block_openmeetings'), 
			'6' => get_string('portugues','block_openmeetings'), 
			'7' => get_string('portuguesbrazil','block_openmeetings'),
			'8' => get_string('spanish', 'block_openmeetings'),
			'9' => get_string('russian', 'block_openmeetings'),
			'10' => get_string('swedish','block_openmeetings'), 
			'11' => get_string('chinesesimplified','block_openmeetings'), 
			'12' => get_string('chinesetraditional','block_openmeetings'), 
			'13' => get_string('korean','block_openmeetings'),
			'14' => get_string('arabic','block_openmeetings'), 
			'15' => get_string('japanese','block_openmeetings'), 
			'16' => get_string('indonesian','block_openmeetings'), 
			'17' => get_string('hungarian','block_openmeetings'),
		    '18' => get_string('turkish','block_openmeetings'),
		    '19' => get_string('ukrainian','block_openmeetings'), 
		    '20' => get_string('thai','block_openmeetings'),
		    '21' => get_string('persian','block_openmeetings'), 
		    '22' => get_string('czech','block_openmeetings'),
		    '23' => get_string('galician','block_openmeetings'), 
		    '24' => get_string('finnish','block_openmeetings'),
		    '25' => get_string('polish', 'block_openmeetings'),
		    '26' => get_string('greek','block_openmeetings'),
		    '27' => get_string('dutch','block_openmeetings'),
		    '28' => get_string('hebrew','block_openmeetings'));
    } else {
		$langchoice = array (
	   		'1' => get_string('english','block_openmeetings'),
			'2' => get_string('deutsch','block_openmeetings'),
			'3' => get_string('french','block_openmeetings'), 
			'4' => get_string('italian','block_openmeetings'), 
			'5' => get_string('portugues','block_openmeetings'), 
			'6' => get_string('portuguesbrazil','block_openmeetings'),
			'7' => get_string('spanish','block_openmeetings'), 
			'8' => get_string('russian','block_openmeetings'), 
			'9' => get_string('swedish','block_openmeetings'), 
			'10' => get_string('chinesesimplified','block_openmeetings'), 
			'11' => get_string('chinesetraditional','block_openmeetings'), 
			'12' => get_string('korean','block_openmeetings'), 
			'13' => get_string('arabic','block_openmeetings'), 
			'14' => get_string('japanese','block_openmeetings'), 
			'15' => get_string('indonesian','block_openmeetings'), 
			'16' => get_string('hungarian','block_openmeetings'), 
		    '17' => get_string('turkish','block_openmeetings'), 
		    '18' => get_string('ukrainian','block_openmeetings'), 
		    '19' => get_string('thai','block_openmeetings'), 
		    '20' => get_string('persian','block_openmeetings'), 
		    '21' => get_string('czech','block_openmeetings'), 
		    '22' => get_string('galician','block_openmeetings'), 
		    '23' => get_string('finnish','block_openmeetings'), 
		    '24' => get_string('polish','block_openmeetings'), 
		    '25' => get_string('greek','block_openmeetings'),
		    '26' => get_string('dutch','block_openmeetings'),
		    '27' => get_string('hebrew','block_openmeetings'));
	}
	
	return $langchoice;
}

	
?>