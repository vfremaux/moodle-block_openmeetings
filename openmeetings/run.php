<?php  

    require_once('../../config.php');
    require_once($CFG->libdir.'/dmllib.php');
	require_once($CFG->dirroot.'/blocks/openmeetings/lib.php');
    require_once($CFG->dirroot.'/blocks/openmeetings/openmeetings_gateway.php');	

	require_js($CFG->wwwroot.'/blocks/openmeetings/js/invit.js');
	
    $id = required_param('id', PARAM_INT); // the openmeetings block instance
    $omid = required_param('omid', PARAM_INT); // the openmeetings block instance
    $courseid = required_param('courseid', PARAM_INT); // the openmeetings block instance		

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
	    
	print_header(filter_string($SITE->fullname), filter_string($SITE->fullname), build_navigation($navlinks), '', '', true, '$nbsp;','',false, 'onbeforeunload="javascript:go(\''.$CFG->wwwroot.'/blocks/openmeetings/js/ajax.php\' ,'. $omid .','. $USER->id .')"' ,false);

	echo '<style type="text/css">
<!--
select.multiple{
width:200px;
height:200px;
}
-->
</style>';

    if (!isset($theblock->config)){
        echo '<br/>';
        notice(get_string('blocknotconfigured', 'block_openmeetings'), $returnurl);
    }
    
	$omserver = get_server_info($theblock->config->server);
	
	$CFG->openmeetings_red5host = $omserver->omhost;
	$CFG->openmeetings_red5port = $omserver->omhttpport;
	$CFG->openmeetings_openmeetingsAdminUser = $omserver->omadmin;
	$CFG->openmeetings_openmeetingsAdminUserPass = $omserver->omadminpass;	
	
	$openmeetings = get_record('block_om_session', 'id', $omid);
	$openmeetings->started = 1;
	update_record('block_om_session', $openmeetings);

	echo '<center><br/><a href="join.php?id='.$id.'&omid='.$omid.'&courseid='.$courseid.'"><input type="button" value="'.get_string('joinroom', 'block_openmeetings').'" /></a></center>';

	print_footer();
	
?>