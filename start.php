<?php  

    require_once('../../config.php');
    require_once($CFG->libdir.'/dmllib.php');
	require_once($CFG->dirroot.'/blocks/openmeetings/lib.php');
	require_once($CFG->dirroot.'/blocks/openmeetings/locallib.php');
    require_once($CFG->dirroot.'/blocks/openmeetings/openmeetings_gateway.php');		

	require_js('yui_yahoo');
	require_js('yui_utilities');
	require_js('yui_dom');
	require_js('yui_event');
	require_js('yui_connection');
	require_js($CFG->wwwroot.'/blocks/openmeetings/js/invit.js');
	
    $id = required_param('id', PARAM_INT); // the openmeetings block instance
    $courseid = required_param('courseid', PARAM_INT); // the openmeetings block instance

    if (!$instance = get_record('block_instance', 'id', $id)){
        error("Block record ID was incorrect");
    }
    if (!$theblock = block_instance('openmeetings', $instance)){
        error("Block instance does'nt exist");
    }

    /// security

	require_capability('block/openmeetings:start', get_context_instance(CONTEXT_BLOCK, $id));

    /// setup return url

    if ($COURSE->id > SITEID){
        $returnurl = $CFG->wwwroot.'/course/view.php?id='.$COURSE->id;
    } else {
        $returnurl = $CFG->wwwroot;
    }

    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $blockcontext = get_context_instance(CONTEXT_BLOCK, $id);
    
    /// check config

	$navlinks[] = array('name' => get_string('blockname', 'block_openmeetings'),
						'url' => '',
						'type' => 'title');

    print_header(strip_tags(filter_string($SITE->fullname)), 
            filter_string($SITE->fullname), 
            build_navigation($navlinks), 
            '', 
            '<meta name="description" content="'. s(strip_tags($SITE->summary)) .'">', 
            true, 
            '', 
            '');
            
    print_heading(get_string('configuremeeting', 'block_openmeetings'));
    
    if (!isset($theblock->config)){
        echo '<br/>';
        notice(get_string('blocknotconfigured', 'block_openmeetings'), $returnurl);
    }

    if (empty($theblock->config->server)){
        echo '<br/>';
        notice(get_string('noomserverattached', 'block_openmeetings'), $returnurl);
    }
    
	$omserver = get_server_info($theblock->config->server);

	$CFG->openmeetings_red5host = $omserver->omhost;
	$CFG->openmeetings_red5port = $omserver->omhttpport;
	$CFG->openmeetings_openmeetingsAdminUser = $omserver->omadmin;
	$CFG->openmeetings_openmeetingsAdminUserPass = $omserver->omadminpass;
	
	if(optional_param('newmeeting', false, PARAM_TEXT)){

		$now = time();
		
		$openmeetings = new stdClass();
		$openmeetings->blockid = $id;
		$openmeetings->teacher = $USER->id;
		$openmeetings->type = required_param('roomtype', PARAM_INT);
		$openmeetings->is_moderated_room = required_param('waitforteacher', PARAM_INT);
		$openmeetings->max_user = optional_param('maxusers', $theblock->config->maxusers, PARAM_INT);
		$openmeetings->language = required_param('language', PARAM_INT);
		$openmeetings->name = optional_param('roomname', $theblock->config->roomname, PARAM_TEXT);
		$openmeetings->intro = required_param('roomdesc', PARAM_TEXT);
		$openmeetings->infroformat = 1;
		$openmeetings->timecreated = $now;
		$openmeetings->timemodified = $now;
		$openmeetings->room_recording_id = 0;
		$openmeetings->started = 1;
		$openmeetings->duration = required_param('duration', PARAM_INT);
		$openmeetings->ispublic = optional_param('ispublic', 0, PARAM_INT);
		$openmeetings->finish = 0;
		$openmeetings->timestarted = $now;
				
		if(openmeetings_is_valid($openmeetings)){
			$openmeetings_gateway = new openmeetings_gateway();
			if ($openmeetings_gateway->openmeetings_loginuser()) {
				
				//Roomtype 0 means its and recording, we don't need to create a recording for that
				if ($openmeetings->type != 0) {
					$openmeetings->room_id = $openmeetings_gateway->openmeetings_createRoomWithModAndType($openmeetings);
				}				
			} else {
				echo "Could not login User to OpenMeetings, check your OpenMeetings Module Configuration";
				if ($courseid > SITEID){
					$options['id'] = $courseid;
					print_single_button($CFG->wwwroot."/course/viex.php", $options, get_string('return', 'block_openmeetings'));
					print_footer($courseid);
				} else {
					print_single_button($CFG->wwwroot."/index.php", array(), get_string('return', 'block_openmeetings'));
					print_footer();
				}
				die;
			}
			
			$omid = insert_record('block_om_session', $openmeetings);
			
			$creator = new stdClass();
			$creator->openmeetingsid = $omid;
			$creator->userid = $USER->id;
			$creator->isadmin = 1;
			$creator->connected = 0;
			$creator->connecttime = null;
			insert_record('block_om_participant', $creator);	

			/* Why ?
			$admins = get_admins();
			foreach($admins as $admin){
				$a = new stdClass();
				$a->openmeetingsid = $omid;
				$a->userid = $admin->id;
				$a->isadmin = 1;
				$a->connected = 0;
				$a->connecttime = null;
				if(get_record('block_om_participant', 'userid', $admin->id, 'openmeetingsid', $omid)){
				} else {
					insert_record('block_om_participant', $a);
				}			
			}
			*/
			
			if(optional_param('selection', '', PARAM_INT)){
				$participantids = optional_param('selection', '', PARAM_INT);
				foreach($participantids as $p){
					if($myuser = get_record('user', 'id', $p)){
						$newuser = new stdClass();
						$newuser->openmeetingsid = $omid;
						$newuser->userid = $myuser->id;
						$newuser->isadmin = 0;
						$newuser->connected = 0;
						$newuser->connecttime = null;
						if(!get_record('block_om_participant', 'userid', $myuser->id, 'openmeetingsid', $omid)){
							insert_record('block_om_participant', $newuser);
						}
					}
				}
			}
			echo '<center>';
			$opts['id'] = $id;
			$opts['omid'] = $omid;
			$opts['courseid'] = $courseid;
			print_single_button('join.php', $opts, get_string('joinroom', 'block_openmeetings'));
			echo '</center>';
		} else {
			$url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
			$roomisbookedstr = get_string('roomisbooked', 'block_openmeetings');
			notice("<center>$roomisbookedstr</center>");
			echo '<center> <a href="'.$url.'">'.get_string('return', 'block_openmeetings').'</a></center>';				
		}		
	} else {
?>
	<br/>
	<form method="post" action="<?php echo $CFG->wwwroot.'/blocks/openmeetings/start.php?id='.$id; ?>" onSubmit="return select_all(this)">
		<fieldset>
			<legend><?php print_string('configdefault', 'block_openmeetings') ?></legend><br/>
			<table align="center" width="90%">
				<tr>
					<td width="20%">
						<?php print_string('roomname', 'block_openmeetings');?>
					</td>
					<td>
						<?php 
						if (has_capability('block/openmeetings:configureroom', $blockcontext)){ 
						?>
						<input type="text" name="roomname" size="30" value="<?php echo isset($theblock->config->roomname)?p($theblock->config->roomname):''; ?>" />
						<?php
						} else {
							echo $theblock->config->roomname;
						}
						?>
					</td>
				</tr>			
			<tr>
				<td>
					<?php print_string('roomdesc', 'block_openmeetings');?> 
				</td>
				<td>
					<textarea name="roomdesc" rows="5" COLS="75" ><?php echo isset($theblock->config->roomdesc)?p($theblock->config->roomdesc):''; ?></textarea>
				</td>
			</tr>			
			<tr>
				<td>
					<?php print_string('roomtype', 'block_openmeetings'); ?>
				</td>
				<td>
					<?php
					$enableoptions = openmeetings_get_roommodes();
					$theblock->config->roomtype = isset($theblock->config->roomtype) ? $theblock->config->roomtype : 1 ;
					choose_from_menu ($enableoptions, 'roomtype', $theblock->config->roomtype, '', '', '');
					?>
				</td>
			</tr>
						
			<?php // TODO : Le recording quand j'aurais openmeetings !!!!!! // ?>
			
			<tr>
				<td>
					<?php print_string('maxusers', 'block_openmeetings'); ?>
				</td>
				<td>
					<?php 
					if (has_capability('block/openmeetings:configureroom', $blockcontext)){ 
						$enableoptions = openmeetings_get_maxusers();
						$theblock->config->maxusers = isset($theblock->config->maxusers) ? $theblock->config->maxusers : 2 ;
						choose_from_menu ($enableoptions, 'maxusers', $theblock->config->maxusers, '', '', '');
					} else {
						echo $theblock->config->maxusers;
					}
					?>	
				</td>
			</tr>
			<tr>
				<td>
					<?php print_string('waitforteacher', 'block_openmeetings'); ?>
				</td>
				<td>
					<?php
					$enableoptions = openmeetings_get_moderations();
					$theblock->config->waitforteacher = isset($theblock->config->waitforteacher) ? $theblock->config->waitforteacher : 1 ;
					choose_from_menu ($enableoptions, 'waitforteacher', $theblock->config->waitforteacher, '', '', '');
					?>		
				</td>
			</tr>			
			<tr>
				<td>
					<?php print_string('language', 'block_openmeetings'); ?>
				</td>
				<td>
					<?php
					$langchoice = openmeetings_get_languages($omserver->omversion);
					$theblock->config->language = isset($theblock->config->language) ? $theblock->config->language : 1 ;				
					choose_from_menu($langchoice, 'language', $theblock->config->language);	
					?>
				</td>
			</tr>
			<tr>
				<td>
					<?php
					unset($enableoptions);
					print_string('defaultduration', 'block_openmeetings');
					?>
				</td>
				<td>
					<?php
					$enableoptions = openmeetings_get_durations();
					$theblock->config->duration = isset($theblock->config->duration) ? $theblock->config->duration : 60 ;
					choose_from_menu ($enableoptions, 'duration', $theblock->config->duration, '', '', '');
					?>		
				</td>
			</tr>	
			<tr>
				<td>
					<?php print_string('ispublic', 'block_openmeetings'); ?>
				</td>
				<td>
					<?php
					unset($enableoptions);
					$enableoptions[1] = get_string('yes');
					$enableoptions[2] = get_string('no');
					choose_from_menu($enableoptions, 'ispublic', @$theblock->config->ispublic, '', '', '');
					?>		
				</td>
			</tr>	
		</table>
		<br/>	
		</fieldset>	

		<fieldset>
			<legend><?php print_string('invitation', 'block_openmeetings') ?></legend>
			<center>
			<?php 
			if ($COURSE->id != SITEID){
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
			?>
			<br/>
			<?php print_heading(get_string('chooseusers', 'block_openmeetings')) ?>
			<table cellspacing="10">
				<tr valign="top">
					<td>
						<div id="userlist-container">
						<select class="multiple" id="id_users" name="liste_champs" multiple OnDblClick="javascript:selection_champs(this.form.liste_champs,this.form.selection)" style="width:250px">
							<?php 
							foreach ($courseusers as $u){
								echo '<option value="'.$u->id.'">'.fullname($u).'</option>';
							}
							?>
						</select></div><br/>
						<?php print_string('filter', 'block_openmeetings') ?>: 
						<input type="text" name="filter" value="" onchange="refreshuserlist('<?php echo $courseid ?>', '<?php echo $id ?>', '<?php echo $CFG->wwwroot ?>', this)"/>
						<!-- input type="button" name="void" value="" / -->
					</td>
					<td>
						<table>
							<tr><td><input class="bouton" type="button" name="selectionner" value=" >> " OnClick="javascript:selection_champs(this.form.liste_champs,this.form.selection)"></td></tr>
							<tr><td><input class="bouton" type="button" name="deselect" value=" << " OnClick="javascript:selection_champs(this.form.selection,this.form.liste_champs)"></td></tr>
						</table>
					</td>
					<td>
						<select name="selection" multiple class="multiple" OnDblClick="javascript:selection_champs(this.form.selection,this.form.liste_champs)" style="width:250px"></select>
					</td>
				</tr>
			</table>
			<br/>
			</center>
		</fieldset>	
		
		<?php echo '<input type="hidden" name="courseid" value="'.$courseid.'" />'; ?>
		<input type="hidden" name="newmeeting" value="1" />
		<center>
			<p><input type="submit" value="<?php print_string('startmeeting', 'block_openmeetings') ?>" /> <input type="button" value="<?php print_string('cancel')?>" onclick="document.location.href ='<?php echo $CFG->wwwroot.'/course/view.php?id='.$courseid ?>';" /></p>
		</center>
	</form>
<?php	

	}
	print_footer();
	
?>