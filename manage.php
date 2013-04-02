<?php  


    require_once('../../config.php');
    require_once($CFG->libdir.'/dmllib.php');
	require_once($CFG->dirroot.'/blocks/openmeetings/lib.php');

	require_js($CFG->wwwroot.'/blocks/openmeetings/js/invit.js');
	
    $id = required_param('id', PARAM_INT); // the openmeetings block instance
    $omid = required_param('omid', PARAM_INT); // the openmeetings block instance
    $courseid = required_param('cid', PARAM_INT); // the course

    if (!$instance = get_record('block_instance', 'id', $id)){
        error("Block record ID was incorrect");
    }
    if (!$theblock = block_instance('openmeetings', $instance)){
        error("Block instance does'nt exist");
    }

    /// setup return url

    if ($courseid > SITEID){
        $returnurl = $CFG->wwwroot."/course/view.php?id={$courseid}";
    } else {
        $returnurl = $CFG->wwwroot;
    }

	$context = get_context_instance(CONTEXT_COURSE, $courseid);
	$blockcontext = get_context_instance(CONTEXT_BLOCK, $instance->id);
	
	require_capability('block/openmeetings:manage', $blockcontext);
    
    /// check config

	$navlinks[] = array(
		'name' => get_string('blockname', 'block_openmeetings'),
		'url' => '',
		'type'=> 'title'
	);

    print_header(strip_tags(filter_string($SITE->fullname)), 
            filter_string($SITE->fullname), 
            build_navigation($navlinks), 
            '', 
            '<meta name="description" content="'. s(strip_tags($SITE->summary)) .'">', 
            true, 
            '', 
            '');
    
    if (!isset($theblock->config)){
        echo '<br/>';
        notice(get_string('blocknotconfigured', 'block_openmeetings'), $returnurl);
    }
    
	$omserver = get_server_info($theblock->config->server);
	
	$openmeetings = get_record('block_om_session', 'id', $omid);
	
	if(optional_param('manage', false, PARAM_BOOL)){

		$roomname = required_param('roomname', PARAM_TEXT);
		$roomdesc = required_param('roomdesc', PARAM_TEXT);
		$roomtype = required_param('roomtype', PARAM_INT);
		$maxusers = required_param('maxusers', PARAM_INT);
		$waitforteacher = required_param('waitforteacher', PARAM_INT);
		$language = required_param('language', PARAM_INT);
		$duration = required_param('duration', PARAM_INT);
		$ispublic = optional_param('ispublic', 0, PARAM_BOOL);	
		$now = time();
		
		$openmeetings->type = $roomtype;
		$openmeetings->is_moderated_room = $waitforteacher;
		$openmeetings->max_user = $maxusers;
		$openmeetings->language = $language;
		$openmeetings->name = $roomname;
		$openmeetings->intro = $roomdesc;
		$openmeetings->timemodified = $now;
		$openmeetings->duration = $duration;
		$openmeetings->ispublic = $ispublic;
		
		update_record('block_om_session', $openmeetings);
		
		$userids = optional_param('selection', '', PARAM_INT); // returns an array of userids
		if(!empty($userids)) {
		
			$participants = get_records('block_om_participant', 'openmeetingsid', $omid);
			foreach($participants as $participant){
				if($participant->isadmin != 1){
					delete_records('block_om_participant', 'id', $participant->id);
				}
			}
						
			foreach($userids as $userid){
				$participant = new stdClass();
				$participant->openmeetingsid = $omid;
				$participant->userid = $userid;
				$participant->isadmin = 0;
				$participant->connected = 0;
				$participant->connecttime = null;
				if(get_record('block_om_participant', 'userid', $userid, 'openmeetingsid', $omid)){
				} else {
					insert_record('block_om_participant', $participant);
				}
			}
		}
		
		$url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
		echo '<center> <a href="'.$url.'"><input type="button" value="'.get_string('return', 'block_openmeetings').'"/> </a></center>';		
	
	} else {

?>
		<br/>
		<form method="post" action="<?php echo 'manage.php?id='.$id.'&omid='.$omid; ?>" onSubmit="return select_all(this)">
			<fieldset>		
				<legend><?php print_string('yourconfig', 'block_openmeetings') ?></legend><br/>
				<table align="center" width="90%">
					<tr>
							<td width="20%">
								<?php print_string('roomname', 'block_openmeetings');?> 
							</td>
							<td>
								<input type="text" name="roomname" size="30" value="<?php echo isset($openmeetings->name)? p($openmeetings->name):''; ?>" />
							</td>
					</tr>			
					<tr>
						<td>
							<?php print_string('roomdesc', 'block_openmeetings');?> 
						</td>
						<td>
							<textarea name="roomdesc" rows="5" COLS="75" ><?php echo isset($openmeetings->intro)?p($openmeetings->intro):''; ?></textarea>
						</td>
					</tr>		
					<tr>
						<td>
							<?php print_string('roomtype', 'block_openmeetings'); ?>
						</td>
						<td>
							<?php
							$enableoptions = openmeetings_get_roommodes();
							$openmeetings->type = isset($openmeetings->type) ? $openmeetings->type : 1 ;
							choose_from_menu ($enableoptions, 'roomtype', $openmeetings->type, '', '', '');
							?>
						</td>
					</tr>	
					<tr>
						<td>
							<?php print_string('maxusers', 'block_openmeetings'); ?>
						</td>
						<td>
							<?php
							$enableoptions = openmeetings_get_maxusers();
							$openmeetings->max_user = isset($openmeetings->max_user) ? $openmeetings->max_user : 2 ;
							choose_from_menu ($enableoptions, 'maxusers', $openmeetings->max_user, '', '', '');
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
							$openmeetings->is_moderated_room  = isset($openmeetings->is_moderated_room ) ? $openmeetings->is_moderated_room  : 1 ;
							choose_from_menu ($enableoptions, 'waitforteacher', $openmeetings->is_moderated_room , '', '', '');
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
							$openmeetings->language  = isset($openmeetings->language) ? $openmeetings->language : 1 ;				
							choose_from_menu($langchoice, 'language', $openmeetings->language,  $openmeetings->language, '', '', '');			
							?>
						</td>
					</tr>
					<tr>
						<td>
							<?php print_string('defaultduration', 'block_openmeetings'); ?>
						</td>
						<td>
							<?php
							$enableoptions = openmeetings_get_durations();
							$openmeetings->duration = isset($openmeetings->duration) ? $openmeetings->duration : 60 ;
							choose_from_menu ($enableoptions, 'duration', $openmeetings->duration, '', '', '');
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
							$enableoptions[1]  = get_string('yes', 'block_openmeetings');
							$enableoptions[2]  = get_string('no', 'block_openmeetings');			
							$openmeetings->ispublic = (isset($openmeetings->ispublic) && ($openmeetings->ispublic==1)) ? $openmeetings->ispublic : 2 ;
							choose_from_menu ($enableoptions, 'ispublic', $openmeetings->ispublic, '', '', '');
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
					$participants = get_records('block_om_participant', 'openmeetingsid', $omid);
					$parts = '';
					
					foreach($participants as $p){					
						$user = get_record('user','id',$p->userid);	
						$u->id = $user->id;
						$u->lastname = $user->lastname;
						$u->firstname = $user->firstname;
						$parts[$p->userid] = $u;
						unset($u);
					}

				
					?>
				<br/>
				<?php print_heading(get_string('chooseusers', 'block_openmeetings')) ?><br/>
				<table cellspacing="10">
					<tr>
						<td>
						<div id="userlist-container">
						<select class="multiple" name="liste_champs" multiple OnDblClick="javascript:selection_champs(this.form.liste_champs,this.form.selection)" style="width:250px">
							<?php 
							foreach ($courseusers as $u){		
								if(!isset($parts[$u->id])){
									echo '<option value="'.$u->id.'">'.fullname($u).'</option>';	
								}
							}
							?>			
						</select>
						</div>
						<input type="text" name="filter" value="" onchange="refreshuserlist('<?php echo $courseid ?>', '<?php echo $id ?>', '<?php echo $CFG->wwwroot ?>', this)"/>
						<input type="button" name="void" value="" />
						</td>
						<td>
							<table>
								<tr><td><input class="bouton" type="button" name="selectionner" value=" >> " OnClick="javascript:selection_champs(this.form.liste_champs,this.form.selection)"></td></tr>
								<tr><td><input class="bouton" type="button" name="deselect" value=" << " OnClick="javascript:selection_champs(this.form.selection,this.form.liste_champs)"></td></tr>
							</table>
						</td>
						<td>
							<select name="selection" multiple class="multiple" OnDblClick="javascript:selection_champs(this.form.selection,this.form.liste_champs)" style="width:250px">
								<?php 
								foreach ($parts as $user){	
									
									echo '<option value="'.$user->id.'">'.fullname($user).'</option>';						
								}
								?>
							</select>
						</td>
					</tr>
				</table>
				<br/>
				</center>
			</fieldset>	
			<?php echo '<input type="hidden" name="courseid" value="'.$courseid.'" />'; ?>
			<input type="hidden" name="manage" value="1"/>
			<center>
				<input type="submit" value="<?php print_string('update') ?>" />
				<input type="button" value="<?php print_string('return', 'block_openmeetings') ?>" onclick="document.location.href='/course/view.php?id=<?php echo $courseid ?>'" />
			</center>
		</form>	
			
<?php
	}
	print_footer();	
?>