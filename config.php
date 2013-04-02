<?php
	/**
	 * This page will be used to manually trigger the platforms catalog update_record
	 * This is an administration page and it's load is heavy.
	 *
	 * @author Edouard Poncelet
	 * @package block-publishflow
	 * @category blocks
	 *
	 **/

	require_once('../../config.php');
	require_once($CFG->dirroot.'/blocks/openmeetings/locallib.php');
	
	// echo '<script src="js/servers.js" type="text/javascript"></script>';
	
    global $CFG;
    
    $full = $SITE->fullname;
    $short = $SITE->shortname;

	$navlinks[] = array('name' => $full, 'link' => "$CFG->wwwroot", 'type' => 'misc');
	$navigation = build_navigation($navlinks);
	print_header($full, $short, $navigation, '', '', false, '');

	$servers = get_available_servers();

	$action = optional_param('what', '', PARAM_ALPHA);
	if ($action == 'delete'){
		$omid = required_param('omid', PARAM_INT);
		unset($servers[(int)$omid]);

		set_config('openmeetings_servers', base64_encode(json_encode($servers)));
	} else {
	
		if ($data = data_submitted()){
			if ($data->omid){
				$s->id = $data->omid;
			} else {
				if (!empty($servers)){
					$s->id = max(array_keys($servers)) + 1;
				} else {
					$s->id = 1;
				}
			}
			$s->omhost = clean_param($data->omhost, PARAM_TEXT);
			$s->omrtmpport = clean_param($data->omrtmpport, PARAM_TEXT);
			$s->omhttpport = clean_param($data->omhttpport, PARAM_TEXT);
			$s->omversion = clean_param($data->omversion, PARAM_TEXT);
			$s->omadmin = clean_param($data->omadmin, PARAM_TEXT);
			$s->omadminpass = clean_param($data->omadminpass, PARAM_TEXT);
			if (!empty($s->omhost)){
				$servers["$s->id"] = $s;
				
				set_config('openmeetings_servers', base64_encode(json_encode($servers)));
			}
		}
	}
	
	$hoststr = get_string('omhostshort', 'block_openmeetings');
	$httpstr = get_string('omhttpshort', 'block_openmeetings');
	$rtmpstr = get_string('omrtmpshort', 'block_openmeetings');
	$adminstr = get_string('admin');
	
	if (!empty($servers)){		
		$table->head = array("<b>$hoststr</b>", "<b>$rtmpstr</b>", "<b>$httpstr</b>", "<b>$adminstr</b>", '');
		$table->width = '80%';
		$table->size = array('50%', '20%', '10%', '10%', '10%');
		$table->align = array('left', 'left', 'left', 'left', 'right');
		$cmd = '';
		$editstr = get_string('edit');
		$deletestr = get_string('delete');
		foreach($servers as $s){
			$cmd = "<a href=\"javascript:loadomform('$s->id', '$s->omhost', '$s->omrtmpport', '$s->omhttpport', '$s->omversion', '$s->omadmin')\" title=\"$editstr\" ><img src=\"{$CFG->pixpath}/i/edit.gif\" /></a>";
			$cmd .= " <a href=\"{$CFG->wwwroot}/blocks/openmeetings/config.php?what=delete&amp;omid={$s->id}\" title=\"$deletestr\" ><img src=\"{$CFG->pixpath}/t/delete.gif\" /></a>";
			$table->data[] = array($s->omhost, $s->omrtmpport, $s->omhttpport, $s->omadmin, $cmd);
		}
		print_heading('omservers', 'block_openmeetings');		
		print_table($table);
	}

	// new server
	print_heading(get_string('omserver', 'block_openmeetings'));
	include $CFG->dirroot.'/blocks/openmeetings/forms/omhostform.html';
	
	print_footer($COURSE);