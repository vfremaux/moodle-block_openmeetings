<?php

require_once('../../../config.php');

$id = required_param('id', PARAM_INT); // course id
$omid = required_param('omid', PARAM_INT); // openmeetings block id
$filter = optional_param('filter', '', PARAM_TEXT);

if (!$course = get_record('course', 'id', $id)) die;
if (!$instance = get_record('block_instance', 'id', $omid)){
    print_error('badblockinstance', 'block_openmeetings');
}

$theBlock = block_instance('openmeetings', $instance);

require_login($course);
$context = get_context_instance(CONTEXT_BLOCK, $omid);
require_capability('block/openmeetings:start', $context);

$userfilter = optional_param('filter', '', PARAM_TEXT);

$filterclause = (!empty($filter)) ? " AND lastname LIKE '%$filter%' " : '' ;

if ($course->id != SITEID){
	$courseusers = get_users_by_capability($context, 'moodle/course:view', 'u.id, firstname, lastname', 'lastname,firstname'); 
} else {
	if (empty($CFG->openmeetings_sitemeetingscatchusers)) set_config(null, 'openmeetings_sitemeetingscatchusers', 'course');
	switch ($CFG->openmeetings_sitemeetingscatchusers){
		case 'course' : 
			$courseusers = get_users_by_capability($context, 'moodle/course:view', 'u.id, firstname, lastname', 'lastname,firstname'); 
		break;
		case 'cap' :
			$courseusers = get_users_by_capability(get_context_instance(CONTEXT_SYSTEM), 'block/openmeetings:usesitelevel', 'u.id, firstname, lastname', 'lastname,firstname'); 
		break;
		case 'any' : 
			// may be costfull option in large audience Moodles
			$courseusers = get_records('user', 'deleted', 0, 'lastname,firstname', 'id, firstname, lastname'); 
		break;
	}
}


if($courseusers){
	foreach($users as $u){
		if ($userfilter && !preg_match("/$userfilter/", $u->firstname) && !preg_match("/$userfilter/", $u->lastname)) continue;
		$useropts[$u->id] = fullname($u);
	}
	if (!empty($useropts)){
		choose_from_menu($useropts, 'liste_champs', '', '', '', 0, false, false, 0, 'id_users', true, true); 
	} else {
		echo '<p>'.get_string('nouserswiththisfilter', 'block_openmeetings').'</p>';
	}
} else {
	echo '<p>'.get_string('nousershere', 'block_openmeetings').'</p>';
}


