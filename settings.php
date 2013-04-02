<?php


$settings->add(new admin_setting_configtext('openmeetings_openmeetingsiFrameWidth',get_string('configframewidth', 'block_openmeetings'),
		    '','1000'));


$settings->add(new admin_setting_configtext('openmeetings_openmeetingsiFrameHeight',get_string('configframeheight', 'block_openmeetings'),
		    '','640'));

$options = array();
$options['any'] = get_string('sitemeeting_allsiteusers', 'block_openmeetings');
$options['cap'] = get_string('sitemeeting_onsystemcapability', 'block_openmeetings');
$options['course'] = get_string('sitemeeting_onlysiteenrolled', 'block_openmeetings');
$settings->add(new admin_setting_configselect('openmeetings_sitemeetingscatchusers', get_string('configsitemeetingscatchusers', 'block_openmeetings'), '', 'course', $options));


$syncstr = get_string('mainconfig', 'block_openmeetings');

$settings->add(new admin_setting_heading('synchronization', get_string('mainconfig', 'block_openmeetings'), "<a href=\"{$CFG->wwwroot}/blocks/openmeetings/config.php\">$syncstr</a>"));

?>