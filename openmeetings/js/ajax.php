<?php  

	$userid = $_GET['id'];
	$omid = $_GET['omid'];
	
    require_once('../../../config.php');
    require_once($CFG->libdir.'/dmllib.php');

    if ($userid != null && $omid != null){	
		$myrecord = get_record('block_om_participant', 'openmeetingsid', $omid, 'userid', $userid);
		if($myrecord != null){		
			$myrecord->connected = 0;			
			update_record('block_om_participant',$myrecord);
		}
    }

?>