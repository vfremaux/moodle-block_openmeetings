<?php  

    require_once('../../config.php');
    require_once($CFG->libdir.'/dmllib.php');
	require_once('lib.php');

    $id = required_param('id', PARAM_INT); // the openmeetings block instance
    $omid = required_param('omid', PARAM_INT); // the openmeetings block instance
    $courseid = required_param('cid', PARAM_INT); // the openmeetings block instance

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
		'name' => get_string('blockname', 'openmeetings'),
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
	
	if(optional_param('prolongate', false, PARAM_BOOL)){
	
		$myuser = get_record('block_om_participant', 'userid', $USER->id, 'openmeetingsid', $omid);
		
		if(!empty($myuser)){
			$openmeetings = get_record('block_om_session', 'id', $omid);
	//print_object($openmeetings);
			$newduration = required_param('duration', PARAM_INT);
			$openmeetings->duration = $openmeetings->duration + ($newduration);
	//print_object($openmeetings);	
			if(openmeetings_is_valid($openmeetings)){
				update_record('block_om_session', $openmeetings);
				echo 'Réunion prolongée';
			}
		} else {
			notice(get_string('notallowedtoclose', 'openmeetings'));
		}
		
	}else{
	
		echo '<form method="post" action="prolongate.php?id='.$id.'&omid='.$omid.'&courseid='.$courseid.'" >';
		?>
		
		<select name="duration">
			<option value="5"> 5 minutes</option>
			<option value="15"> 15 minutes</option>
			<option value="30"> 30 minutes</option>
			<option value="60"> 60 minutes</option>
			<option value="120"> 120 minutes</option>
		</select>
		
		<?php
		echo '<input type="hidden" name="prolongate" value="1"/>';
		echo '<input type="submit" />';
		echo '</form>';
	
	}
	//$url = $CFG->wwwroot."/course/view.php?id=".$cid;
	//echo '<center> <a href="'.$url.'">'.get_string('return', 'block_openmeetings').'</a></center>';
	
	print_footer();
	
?>