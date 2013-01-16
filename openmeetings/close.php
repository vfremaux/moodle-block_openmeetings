<?php  

    require_once('../../config.php');
    require_once($CFG->libdir.'/dmllib.php');
	require_once($CFG->dirroot.'/blocks/openmeetings/lib.php');
    require_once($CFG->dirroot.'/blocks/openmeetings/openmeetings_gateway.php');

	//require_js($CFG->wwwroot.'/blocks/openmeetings/js/metadata.js');
	
    $id = required_param('id', PARAM_INT); // the openmeetings block instance
    $omid = required_param('omid', PARAM_INT); // the openmeetings block instance
    $courseid = required_param('cid', PARAM_INT); // the openmeetings courseid

    if (!$instance = get_record('block_instance', 'id', $id)){
        error("Block record ID was incorrect");
    }
    if (!$theblock = block_instance('openmeetings', $instance)){
        error("Block instance does'nt exist");
    }
    
    if (!$course = get_record('course', 'id', "$courseid")){
    	error("Bad course ID !");
    }
    
    // security
    require_login($course);

    /// setup return url

    if ($courseid > SITEID){
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
	    
	//print_header(filter_string($SITE->fullname), filter_string($SITE->fullname), build_navigation($navlinks), '', '', true, '$nbsp;','',false, 'onbeforeunload="javascript:go(\''.$CFG->wwwroot.'/blocks/openmeetings/js/ajax.php\' ,'. $omid .','. $USER->id .')"' ,false);
	print_header(filter_string($SITE->fullname), filter_string($SITE->fullname), build_navigation($navlinks));
    
    if (!isset($theblock->config)){
        echo '<br/>';
        notice(get_string('blocknotconfigured', 'block_openmeetings'), $returnurl);
    }
	
	$openmeetings = get_record('block_om_session', 'id', $omid);  	
	$omserver = get_server_info($theblock->config->server);

	$CFG->openmeetings_red5host = $omserver->omhost;
	$CFG->openmeetings_red5port = $omserver->omhttpport;
	$CFG->openmeetings_openmeetingsAdminUser = $omserver->omadmin;
	$CFG->openmeetings_openmeetingsAdminUserPass = $omserver->omadminpass;	
	
	$openmeetings_gateway = new openmeetings_gateway();
	if ($openmeetings_gateway->openmeetings_loginuser()) {
		$openmeetings_gateway->openmeetings_deleteRoom($openmeetings);
	}
	
	$myuser = get_record('block_om_participant', 'userid', $USER->id, 'openmeetingsid', $omid);
	
	if(!empty($myuser)){
		$openmeetings = get_record('block_om_session', 'id', $omid);
		openmeetings_close_meeting($openmeetings);
	} else {
		notice(get_string('notallowedtoclose', 'block_openmeetings'));
	}
	$url = $CFG->wwwroot."/course/view.php?id=".$courseid;
	echo '<center> <a href="'.$url.'">'.get_string('return', 'block_openmeetings').'</a></center>';
	
	print_footer();
	
?>