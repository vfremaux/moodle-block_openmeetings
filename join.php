<?php  

	/**
	* joins a room that was started by a first user
	* @package blocks
	* @subpackage openmeetings
	* @author Thibaut Funk
	*/

    require_once('../../config.php');
    require_once($CFG->libdir.'/dmllib.php');
	require_once($CFG->dirroot.'/blocks/openmeetings/lib.php');
    require_once($CFG->dirroot.'/blocks/openmeetings/openmeetings_gateway.php');	

	require_js($CFG->wwwroot.'/blocks/openmeetings/js/metadata.js');
	require_js($CFG->wwwroot.'/blocks/openmeetings/js/Timer.js');
	
    $id = required_param('id', PARAM_INT); // the openmeetings block instance
    $omid = required_param('omid', PARAM_INT); // the openmeetings block instance
    $courseid = required_param('courseid', PARAM_INT); // the current courseid		

    if (!$instance = get_record('block_instance', 'id', $id)){
        error("Block record ID was incorrect");
    }
    if (!$theblock = block_instance('openmeetings', $instance)){
        error("Block instance does'nt exist");
    }

    /// setup return url

    if ($COURSE->id > SITEID){
        $returnurl = $CFG->wwwroot.'/course/view.php?id='.$COURSE->id;
    } else {
        $returnurl = $CFG->wwwroot;
    }
    
    /// check config

	$navlinks[] = array(
		'name' => get_string('blockname', 'block_openmeetings'),
		'url' => '',
		'type'=> 'title'
	);
	    
	print_header(filter_string($SITE->fullname), filter_string($SITE->fullname), build_navigation($navlinks), '', '', true, '','',false, 'onbeforeunload="javascript:go(\''.$CFG->wwwroot.'/blocks/openmeetings/js/ajax.php\' ,'. $omid .','. $USER->id .')"' ,false);

    if (!isset($theblock->config)){
        echo '<br/>';
        notice(get_string('blocknotconfigured', 'block_openmeetings'), $returnurl);
    }
    
	$omserver = get_server_info($theblock->config->server);
	
	$CFG->openmeetings_red5host = $omserver->omhost;
	$CFG->openmeetings_red5port = $omserver->omhttpport;
	$CFG->openmeetings_openmeetingsAdminUser = $omserver->omadmin;
	$CFG->openmeetings_openmeetingsAdminUserPass = $omserver->omadminpass;	
	
	$me = get_record('block_om_participant', 'userid', $USER->id, 'openmeetingsid', $omid);

	if(!empty($me)){
		$me->connected = 1;
		$me->connecttime = time();
		update_record('block_om_participant', $me);
	} else {
		$me->openmeetingsid = $omid;
		$me->userid = $USER->id;
		$me->isadmin = 0;
		$me->connected = 1;
		$me->connecttime = time();
		insert_record('block_om_participant', $me);
	}

	$openmeetings = get_record('block_om_session','id', $omid);
	
	if($openmeetings->started != 1){
		$openmeetings->started = 1;
		update_record('block_om_session', $openmeetings);
	}

	// Gestion du popup en timelimit - 5 minutes
//	echo '<br/>';
	if($me->isadmin == 1){
		$timeact = time();
		$timepop = $openmeetings->timestarted + $openmeetings->duration * 60 - 5 * 60;
		$duration = $timepop - $timeact;
		echo '<div id="timer">';
		echo '<script type="text/javascript">';
		echo 'window.onload=CreateTimer("timer",'.$duration.')';
		echo '</script>';
		echo '</div>';
	}
	
	echo '<table align="center" width="800">';
	echo '<tr>';	
	echo '<td width="400">';
	echo '<h1>'.$openmeetings->name.' ('.date("G\hi",$openmeetings->timestarted).' - '. date("G\hi",$openmeetings->timestarted + $openmeetings->duration * 60) .') </h1>';
	echo '</td>';
	
	if($me->isadmin == 1){
		echo '<td>';
		echo '<input type="button" value="'.get_string('manage', 'block_openmeetings').'" onClick="window.open(\''.$CFG->wwwroot.'/blocks/openmeetings/manage.php?id='.$id.'&omid='.$omid.'&cid='.$courseid.'\', \'Manage\', \'scrollbars=yes,width=800,height=600\')" />';
		echo '</td>';	
		echo '<td>';
		echo '<input type="button" value="'.get_string('prorogate', 'block_openmeetings').'" onClick="window.open(\''.$CFG->wwwroot.'/blocks/openmeetings/prorogate.php?id='.$id.'&omid='.$omid.'&cid='.$courseid.'\', \'Manage\', \'scrollbars=yes,width=800,height=400\')"/>';
		echo '</td>';		
		echo '<td>';
		echo '<a href="'.$CFG->wwwroot.'/blocks/openmeetings/close.php?id='.$id.'&omid='.$omid.'&cid='.$courseid.'"><input type="button" value="'.get_string('freeroom', 'block_openmeetings').'" /></a>';
		echo '</td>';		
		
	}
	//echo '<br/><center> Affichage de l\'iframe</center>';
	echo '</tr>';
	echo '</table>';

		
   	$becomemoderator = 0;
   	if ($me->isadmin == 1) {
   		$becomemoderator = 1;
   	}   	
		
	$openmeetings_gateway = new openmeetings_gateway();
	if ($openmeetings_gateway->openmeetings_loginuser()) {	
		if ($openmeetings->type != 0){
			$returnVal = $openmeetings_gateway->openmeetings_setUserObjectAndGenerateRoomHashByURL($USER->username, $USER->firstname, $USER->lastname, $USER->picture, $USER->email, $USER->id, 'moodle', $openmeetings->room_id, $becomemoderator);
		} else {
			$returnVal = $openmeetings_gateway->openmeetings_setUserObjectAndGenerateRecordingHashByURL($USER->username, $USER->firstname, $USER->lastname, $USER->id, 'moodle', $openmeetings->room_recording_id);
		}		
				
		if ($returnVal != '') {			
			$iframe_d = 'http://'.$CFG->openmeetings_red5host.':'.$CFG->openmeetings_red5port.
						 	'/openmeetings/?'.
							'secureHash='.$returnVal. 
							'&scopeRoomId='.$openmeetings->room_id.
							//'&swf=maindebug.swf8.swf'.
							'&language='.$openmeetings->language. 
							'&picture='.$USER->picture. 
							'&user_id='.$USER->id. 
							'&moodleRoom=1'. 
                            '&wwwroot='.$CFG->wwwroot;                                                                                                      
			
			echo "<center><iframe src='{$iframe_d}' width='{$CFG->openmeetings_openmeetingsiFrameWidth}' height='{$CFG->openmeetings_openmeetingsiFrameHeight}' /></center>";
		}
	} else {
		error("Could not login User to OpenMeetings, check your OpenMeetings Module Configuration", $CFG->wwwroot.'/course/view.php?id='.$course->id);
	}	

	print_footer();
	
?>