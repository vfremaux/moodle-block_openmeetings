<?php  

    require_once('../../config.php');
    require_once($CFG->libdir.'/dmllib.php');
	require_once('lib.php');
    require_once($CFG->dirroot.'/blocks/openmeetings/openmeetings_gateway.php');		

	require_js($CFG->wwwroot.'/blocks/openmeetings/js/invit.js');

	echo '<style type="text/css">
<!--
select.multiple{
width:200px;
height:200px;
}
-->
</style>';
	
    $id = required_param('id', PARAM_INT); // the openmeetings block instance
    $courseid = required_param('courseid', PARAM_INT); // the openmeetings block instance

    if (!$instance = get_record('block_instance', 'id', $id)){
        error("Block record ID was incorrect");
    }
    if (!$theblock = block_instance('openmeetings', $instance)){
        error("Block instance does'nt exist");
    }

    /// setup return url

    if ($COURSE->id > SITEID){
        $returnurl = $CFG->wwwroot."/course/view.php?id={$COURSE->id}";
    } else {
        $returnurl = $CFG->wwwroot;
    }
    
    /// check config
    
    $navlinks[] = array('name' => get_string('blockname', 'block_openmeetings'),
    					'url' => '',
    					'type' => 'title');
    
    if (!isset($theblock->config)){
        print_header(strip_tags(filter_string($SITE->fullname)), 
                filter_string($SITE->fullname), 
                build_navigation($navlinks), 
                '', 
                '<meta name="description" content="'. s(strip_tags($SITE->summary)) .'">', 
                true, 
                '', 
                '');
        echo '<br/>';
        notice(get_string('blocknotconfigured', 'block_openmeetings'), $returnurl);
    } else {    
		print_header();
	}
	
	$omserver = get_server_info($theblock->config->server);

	$CFG->openmeetings_red5host = $omserver->omhost;
	$CFG->openmeetings_red5port = $omserver->omhttpport;
	$CFG->openmeetings_openmeetingsAdminUser = $omserver->omadmin;
	$CFG->openmeetings_openmeetingsAdminUserPass = $omserver->omadminpass;
		
	// Test d'ajout //	
	
	
	// Affichage des creneaux disponibles + formulaire //
	
	
	// liste de toute nos réunion 
	// <b>nom </b>: 	timestart-duration
	//					description
	//					view / edit)
	// fieldset choix de nouvelle réunion //
	
	echo '<br/><fieldset>';
	$meetings = openmeetings_get_all_meetings($id);
	
	foreach($meetings as $meeting){
		echo '<br/><b>'.$meeting->name.'</b> <br/>'.date('l jS \of F Y',$meeting->timestarted).' ('.date("G\hi",$meeting->timestarted).' - '. date("G\hi",$meeting->timestarted + $meeting->duration * 60) .')<br/>';
		echo '<b>'.get_string('description').'</b> : '. $meeting->intro .'<br/>';
		$now = time();
		$crit = $meeting->timestarted;
		if($meeting->finish == 1){
			echo '<font color="red">'.get_string('meetingclosed', 'block_openmeetings').'</font><br/>';
		}else if($now < $meeting->timestarted){
			echo '<a href="'.$CFG->wwwroot.'/blocks/openmeetings/manage.php?id='.$id.'&omid='.$meeting->id.'&courseid='.$courseid.'" title="'.get_string('edit').'" ><img src="'.$CFG->pixpath.'/t/edit.gif" /></a>';
			echo ' <a href="'.$CFG->wwwroot.'/blocks/openmeetings/close.php?id='.$id.'&omid='.$meeting->id.'&cid='.$courseid.'" title="'.get_string('delete').'" ><img src="'.$CFG->pixpath.'/t/delete.gif" /></a>';
			echo '<br/>';
		}else{
			echo '<font color="green">'.get_string('inprogress', 'block_openmeetings').'</font><br/>';
		}
	}
	echo '<br/></fieldset>';
	
	print_footer();		
?>