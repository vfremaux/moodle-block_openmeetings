<?php  

    require_once('../../config.php');
    require_once($CFG->libdir.'/dmllib.php');
	require_once($CFG->dirroot.'/blocks/openmeetings/lib.php');
    require_once($CFG->dirroot.'/blocks/openmeetings/openmeetings_gateway.php');		

	require_js($CFG->wwwroot.'/blocks/openmeetings/js/invit.js');
	
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
        $returnurl = $CFG->wwwroot.'/course/view.php?id='.$COURSE->id;
    } else {
        $returnurl = $CFG->wwwroot;
    }
    
    /// check config
    
    $navlinks[] = array(
    	'name' => get_string('blockname', 'block_openmeetings'),
    	'url' => '',
    	'type' => 'title'
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

	$CFG->openmeetings_red5host = $omserver->omhost;
	$CFG->openmeetings_red5port = $omserver->omhttpport;
	$CFG->openmeetings_openmeetingsAdminUser = $omserver->omadmin;
	$CFG->openmeetings_openmeetingsAdminUserPass = $omserver->omadminpass;
		
	// Test d'ajout //	
	if(optional_param('newmeeting', false, PARAM_BOOL)){
		$roomname = required_param('roomname', PARAM_TEXT);
		$roomdesc = optional_param('roomdesc', '', PARAM_CLEANHTML);
		$roomtype = required_param('roomtype', PARAM_TEXT);
		$maxusers = required_param('maxusers', 2, PARAM_INT);
		$waitforteacher = optional_param('waitforteacher', 1, PARAM_INT);
		$language = optional_param('language', 0, PARAM_INT);
		$now = optional_param('dday', '', PARAM_TEXT);
		$starttime = optional_param('starttime', '', PARAM_TEXT);
		
		$duration = optional_param('duration', 60, PARAM_INT);
		$ispublic = optional_param('ispublic', 0, PARAM_INT);	
		$thetime = time();
		
		$openmeetings = new stdClass();
		$openmeetings->blockid = $id;
		$openmeetings->teacher = $USER->id;
		$openmeetings->type = $roomtype;
		$openmeetings->is_moderated_room = $waitforteacher;
		$openmeetings->max_user = $maxusers;
		$openmeetings->language = $language;
		$openmeetings->name = $roomname;
		$openmeetings->intro = $roomdesc;
		$openmeetings->infroformat = 1;
		$openmeetings->timecreated = $thetime;
		$openmeetings->timemodified = $thetime;
		$openmeetings->room_recording_id = 0;
		$openmeetings->started = 0;
		$openmeetings->duration = $duration;
		$openmeetings->ispublic = 0;
		$openmeetings->finish = 0;
		$openmeetings->timestarted = $now + $starttime*60*60;
		
	//	echo $now .' '. $starttime*60*60 . ' <<<< '.$openmeetings->timestarted;
		
		if($ispublic == 1){
			$openmeetings->ispublic = 1;		
		}
		
		if(openmeetings_is_valid($openmeetings)){
		
			$openmeetings_gateway = new openmeetings_gateway();
			if ($openmeetings_gateway->openmeetings_loginuser()) {
				
				//Roomtype 0 means its and recording, we don't need to create a recording for that
				if ($openmeetings->type != 0) {
					$openmeetings->room_id = $openmeetings_gateway->openmeetings_createRoomWithModAndType($openmeetings);
				}
				
			} else {
				echo "Could not login User to OpenMeetings, check your OpenMeetings Module Configuration";
			}
	
			$omid = insert_record('block_om_session', $openmeetings);
				
			$creator = new stdClass();
			$creator->openmeetingsid = $omid;
			$creator->userid = $USER->id;
			$creator->isadmin = 1;
			$creator->connected = 0;
			$creator->connecttime = null;
			insert_record('block_om_participant',$creator);	

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
					insert_record('block_om_participant',$a);
				}			
			}

			$roomisbookedstr = get_string('roomisbooked', 'block_openmeetings');
			
			$options['id'] = $id;
			$options['courseid'] = $courseid;
			echo '<center>';
			print_single_button('book.php', $options, $roomisbookedstr);
			echo '</center>';
		} else {
			$url = "book.php?id=".$id."&courseid=".$courseid;
			$roombookedatthistimestr = get_string('roombookedatthistime', 'block_openmeetings');
			echo "<br/><center>$roombookedatthistimestr</center>";
			$options['id'] = $id;
			$options['courseid'] = $courseid;
			echo '<center>';
			print_single_button('book.php', $options, $roombookedatthistimestr);
			echo '</center>';
		}
	
	
	// Affichage des creneaux disponibles + formulaire //
	} elseif (isset($_POST['day']) && isset($_POST['month']) && isset($_POST['year'])){
		 $result = checkdate($_POST['month'], $_POST['day'], $_POST['year']);
		 $now = mktime(0,0,0,date('n'),date('j'),date('Y'));
		 $datea = mktime(0, 0, 0, $_POST['month'], $_POST['day'], $_POST['year']);
		 $dateb = mktime(23, 59, 59, $_POST['month'], $_POST['day'], $_POST['year']);
		 
		 if (($now <= $datea) && $result){
			$meetings = array();
			if ($sessions = get_records('block_om_session', 'blockid', $id)){
				$i = 0;
				foreach ($sessions as $session){
					if ((($session->timestarted > $datea && $session->timestarted < $dateb) || (($session->timestarted + 60 * $session->duration) > $datea && ($session->timestarted + 60 * $session->duration) < $dateb)) && $session->finish == 0 ){
						$meetings[$i] = $session;
						$i++;
					}
				}
			}
			
			$tab = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
			if (!empty($meetings)){
				foreach ($meetings as $meeting){
					$nb = $meeting->duration / 60;
					$a = date('G',$meeting->timestarted);
					$m = date('n',$meeting->timestarted);
					$d = date('j',$meeting->timestarted);
					$y = date('Y',$meeting->timestarted);
					$theDay = mktime(0, 0, 0, $m, $d, $y);
					
					for ($i = 0 ; $i < $nb ; $i++){
						
						if ( $a < 24 && $theDay == $datea){
							unset($tab[array_search($a, $tab)]);
						} elseif ($a >= 24 && $theDay == $datea){
						} else {
							unset($tab[array_search($a-24, $tab)]);
						}
						$a = $a+1;
					}
				}
			}
			print_heading(get_string('bookingon', 'block_openmeetings', userdate($datea)));
			echo '<br/><center><fieldset style="width:90%"><legend><b>'.get_string('schedule', 'block_openmeetings').'</b></legend>';
			echo '<table border="1" width="150" align="center">';
			echo '<tr><th>'.get_string('date').'</th></tr>';
			
				for ($i = 0;$i < 24 ; $i++){
					echo '<tr><td';
					if (isset($tab[$i])){ echo ' bgcolor="#9deeb0"'; }else{ echo ' bgcolor="#ff8989"'; }
					echo ' align="center"> '.$i.'h00 - '.$i.'h59 </td></tr>';
				}
			
			echo '</table><br/></fieldset>';
	?>
	<form method="post" action="<?php echo 'book.php?id='.$id.'&courseid='.$courseid; ?>" onSubmit="return select_all(this)">
		<fieldset style="width:90%">
		<legend><?php print_string('configdefault', 'block_openmeetings') ?></legend><br/>
		<table align="center" width="90%">
			<tr valign="top">
				<td width="25%" align="right">
					<?php print_string('roomname', 'block_openmeetings');?> 
				</td>
				<td align="left">
					<input type="text" name="roomname" size="30" value="<?php echo isset($theblock->config->roomname)? p($theblock->config->roomname):''; ?>" />
				</td>
			</tr>
			<tr valign="top">
				<td align="right">
					<?php print_string('roomdesc', 'block_openmeetings');?> 
				</td>
				<td align="left">
					<textarea name="roomdesc" rows="5" cols="75" ><?php echo isset($theblock->config->roomdesc)? p($theblock->config->roomdesc):''; ?></textarea>
				</td>
			</tr>
			<?php echo '<input type="hidden" name="dday" value="'.$datea.'"/>'; ?>
			<tr valign="top">
				<td align="left">
					<?php print_string('starttime', 'block_openmeetings'); ?>
				</td>
				<td align="left">
					<?php
					if(!empty($tab)){
						foreach($tab as $value){
							$hourstring = ($value > 1) ? get_string('hours') : get_string('hour') ;
							$enableoptions[$value]  = $value.' '.$hourstring;
						
						}
					}
					choose_from_menu ($enableoptions, 'starttime', '', '', '', '');
					?>
				</td>
			</tr>
			<tr valign="top">
				<td align="right">
					<?php print_string('defaultduration', 'block_openmeetings'); ?>
				</td>
				<td align="left">
					<?php
					$durationoptions = openmeetings_get_durations();
					$theblock->config->duration = isset($theblock->config->duration) ? $theblock->config->duration : 60 ;
					choose_from_menu ($durationoptions, 'duration', $theblock->config->duration, '', '', '');
					?>
				</td>
			</tr>			
			<tr valign="top">
				<td align="right">
					<?php print_string('roomtype', 'block_openmeetings'); ?>
				</td>
				<td align="left">
					<?php
					$roomoptions = openmeetings_get_roommodes();
					$theblock->config->roomtype = isset($theblock->config->roomtype) ? $theblock->config->roomtype : 1 ;
					choose_from_menu ($roomoptions, 'roomtype', $theblock->config->roomtype, '', '', '');
					?>
				</td>
			</tr>
			 			
			<!-- // TODO : Le recording quand j'aurais openmeetings !!!!!! // -->
			
			<tr valign="top">
				<td align="right">
					<?php print_string('maxusers', 'block_openmeetings'); ?>
				</td>
				<td align="left">
					<?php
					$maxusersoptions = openmeetings_get_maxusers();
					$theblock->config->maxusers = isset($theblock->config->maxusers) ? $theblock->config->maxusers : 5 ;
					choose_from_menu ($maxusersoptions, 'maxusers', $theblock->config->maxusers, '', '', '');
					?>
				</td>
			</tr>
			<tr valign="top">
				<td align="right">

					<?php print_string('waitforteacher', 'block_openmeetings'); ?>
				</td>
				<td align="left">
					<?php
					$moderationoptions  = openmeetings_get_moderations();
					$theblock->config->waitforteacher = isset($theblock->config->waitforteacher) ? $theblock->config->waitforteacher : 1 ;
					choose_from_menu ($moderationoptions, 'waitforteacher', $theblock->config->waitforteacher, '', '', '');
					?>
				</td>
			</tr>
			<tr valign="top">
				<td align="right">
					<?php print_string('language', 'block_openmeetings'); ?>
				</td>
				<td align="left">
					<?php
					$langchoice = openmeetings_get_languages($omserver->omversion);
					$theblock->config->language = isset($theblock->config->language) ? $theblock->config->language : 1 ;				
					choose_from_menu($langchoice, 'language', $theblock->config->language,  '', '', '');	
					?>			
				</td>
			</tr>
			<tr valign="top">
				<td align="right">
					<?php
					unset($enableoptions);
					print_string('ispublic', 'block_openmeetings');
					echo '</td><td align="left">';
					$enableoptions[1]  = get_string('yes', 'block_openmeetings');
					$enableoptions[2]  = get_string('no', 'block_openmeetings');
		
					choose_from_menu ($enableoptions, 'ispublic', '', '', '', '');
					?>		
				</td>
			</tr>	
		</table>
		<br/>	
		</fieldset>	
		<?php echo '<input type="hidden" name="courseid" value="'.$courseid.'" />'; ?>
		<input type="hidden" name="newmeeting" value="1"/>
		<center>
			<p><input type="submit" value="<?php print_string('booksession', 'block_openmeetings') ?>"/> <input type="button" value="<?php print_string('cancel') ?>" onclick="document.location.href = '<?php echo $CFG->wwwroot.'/course/view.php?id='.$courseid; ?>'; " /></p>
		</center>
	</form>
<?php	
		}
	} else {
		
		// liste de toute nos réunion 
		// <b>nom </b>: 	timestart-duration
		//					description
		//					view / edit)
		// fieldset choix de nouvelle réunion //
		
		$deletestr = get_string('delete');
		$editstr = get_string('edit');
		
		echo '<br/><center><fieldset style="width:90%"><legend><b>'.get_string('meetingstocome', 'block_openmeetings').'</b></legend>';
		if ($meetings = openmeetings_get_all_meetings($id)){
			foreach($meetings as $meeting){
				if($meeting->finish == 1){
				}else{
					echo '<br/><b>'.$meeting->name.'</b> <br/>'.userdate($meeting->timestarted).' ( -> '. date("G\hi",$meeting->timestarted + $meeting->duration * 60) .')<br/>';
					echo '<b>'.get_string('description').'</b> : '. $meeting->intro .'<br/>';
					$now = time();
					$crit = $meeting->timestarted;
					
					if($now < $meeting->timestarted){
						echo '<a href="'.$CFG->wwwroot.'/blocks/openmeetings/manage.php?id='.$id.'&omid='.$meeting->id.'&courseid='.$courseid.'" title="'.$editstr.'"><img src="'.$CFG->pixpath.'/t/edit.gif" /></a>';
						echo '&nbsp;<a href="'.$CFG->wwwroot.'/blocks/openmeetings/close.php?id='.$id.'&omid='.$meeting->id.'&cid='.$courseid.'" title="'.$deletestr.'"><img src="'.$CFG->pixpath.'/t/delete.gif" /></a>';
						echo '<br/>'; 
					}else if($meeting->started == 1){
						echo '<font color="green">'.get_string('inprogress', 'block_openmeetings').'</font><br/>';
					}else if($meeting->started == 0){
						echo '<font color="blue">'.get_string('availablewaitingvalidation', 'block_openmeetings').'</font><br/>';
					}else if($now > $meeting->timestarted + 60 * $meeting->duration){
						echo '<font color="red">'.get_string('finished', 'block_openmeetings').'</font><br/>';
					}
				}
			}
		} else {
			print_box_start();
			print_string('nomeetings', 'block_openmeetings');
			print_box_end();
		}
		echo '<br/></fieldset>';
		
		
		echo '<br/><fieldset style="width:90%"><legend><b>'.get_string('booktheroom', 'block_openmeetings').'</b></legend>';
		echo '<form method="post" action="book.php?id='.$id.'&courseid='.$courseid.'">';
		echo '<center>'.get_string('choosetime', 'block_openmeetings');
		echo '<select name="day">';
		
		for($i = 1 ; $i < 32 ; $i++){
			echo '<option';
			if ($i == date('j')){ echo ' selected="selected" '; }
			echo ' value="'.$i.'">'.sprintf('%02d', $i).'</option>';
		}
		echo '</select>';

		echo '<select name="month">';
		for($i = 1;$i < 13 ; $i++){
			echo '<option';
			if ($i == date('n')){ echo ' selected'; }
			echo ' value="'.$i.'">'.sprintf('%02d', $i).'</option>';
		}
		echo '</select>';	
		$year = date('Y');
		echo '<select name="year">';
		for($i = $year ; $i < ($year + 3) ; $i++){
			echo '<option value="'.$i.'">'.$i.'</option>';
		}
		echo '</select>';		
		echo ' <p><input type="submit" value="'.get_string('update').'"/></p></center>';
		echo '</fieldset>';
		echo '</form>';

		$options['id'] = $courseid;		
		echo '<center><p>';
		print_single_button($CFG->wwwroot.'/course/view.php', $options, get_string('backtocourse', 'block_openmeetings'));
		echo '</p></center>';			
	}	
	
	print_footer();
			
?>