<?php //$Id: block_openmeetings.php,v 1.6 2012-02-07 19:20:53 vf Exp $

require_once($CFG->dirroot.'/blocks/openmeetings/lib.php');
require_once($CFG->dirroot.'/blocks/openmeetings/locallib.php');

class block_openmeetings extends block_base {

	function init() {
        $this->title = get_string('blockname', 'block_openmeetings');
        $this->version = 2011090900;
		$this->cron = 300;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : $this->title ;
    }

    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }
        
        // non connected people cannot use 
        if (isguest() || !isloggedin()){
            $this->content = new stdClass;
            $this->content->text = '';
            $this->content->footer = '';    
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = $this->view();
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Will be called before an instance of this block is backed up, so that any links in
     * any links in any HTML fields on config can be encoded.
     * @return string
     */
    function get_backup_encoded_config() {
        /// Prevent clone for non configured block instance. Delegate to parent as fallback.
        if (empty($this->config)) {
            return parent::get_backup_encoded_config();
        }
        $data = clone($this->config);
        $data->text = backup_encode_absolute_links($data->text);
        return base64_encode(serialize($data));
    }

    /*
     * Hide the title bar when none set..
     */
    function hide_header(){
        return empty($this->config->title);
    }
    
    // additional specific functions
    /**
    * provides a general entry point for making the block content
    */
    function view(){
        global $USER, $CFG, $COURSE;
		
		$systemcontext = get_context_instance(CONTEXT_SYSTEM);
		$coursecontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);
		$context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
		
		// Get serv info
		$omserver = get_server_info(@$this->config->server);

		$str = '';

		if(empty($CFG->openmeetings_servers)){
			if (has_capability('moodle/site:doanything', $systemcontext)){
				$str .= '<center><a href="/admin/settings.php?section=blocksettingopenmeetings">'.get_string('pleaseconfigure','block_openmeetings').'</a></center>';			
			} else {
				$str .= '<center>'.get_string('omnotconfigured','block_openmeetings').'</center>';			
			}
			return $str;
		}

		if(empty($omserver)){
			if (has_capability('moodle/course:manageactivities', $coursecontext)){
				$str .= '<center><a href="/admin/settings.php?section=blocksettingopenmeetings">'.get_string('pleaseconfigure','block_openmeetings').'</a></center>';			
			} else {
				$str .= '<center>'.get_string('instancenotconfigured','block_openmeetings').'</center>';			
			}
		}		
		
		$waitingformoderatorstr = get_string('waitingformoderator', 'block_openmeetings');
		$booksessionstr = get_string('booksession', 'block_openmeetings');
		$bookstr = get_string('book', 'block_openmeetings');
        
        $openmeetings = $this->get_lastused_or_current_meeting();		

		if ($openmeetings != null){	
			if (!empty($openmeetings->started) && $openmeetings->started == 1){
			
				$participantrec = $this->participant($openmeetings, $USER->id);
				if ($openmeetings->ispublic){
					if (!empty($participantrec->connected)){
						$str .= '<center>';
						$str .= print_heading(get_string('sessioninprogress', 'block_openmeetings', $openmeetings->name), 'center', 3, '', true);
						$str .= "<img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/meeting_in_progress.png\" /><br/>";
						$str .= $this->drawtimeline($openmeetings);
						$str .= '</center>';
						$current = time();
						$meetingduration = $openmeetings->duration * 60;
						$meetingend = $openmeetings->timestarted + $meetingduration;
						if($current > $openmeetings->timestarted && $current < $meetingend ){
							$str .= get_string('alreadyconnected', 'block_openmeetings');
						}
						$str .= $this->print_participants($openmeetings);
						$context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
						if (has_capability('block/openmeetings:start', $context)){
							$str .= "<center><a href=\"{$CFG->wwwroot}/blocks/openmeetings/book.php?id={$this->instance->id}&courseid={$COURSE->id}\">$booksessionstr</a></center>";
						}					
						if (has_capability('block/openmeetings:manage', $context)){	
							$str .= "<center><a href=\"{$CFG->wwwroot}/blocks/openmeetings/close.php?id={$this->instance->id}&omid={$openmeetings->id}&cid={$COURSE->id}\">".get_string('close', 'block_openmeetings')."</a></center>";
						}					
					} else {
						$joinstr = get_string('join', 'block_openmeetings');
						$str .= '<center>';
						$str .= print_heading(get_string('sessioninprogress', 'block_openmeetings', $openmeetings->name), 'center', 3, '', true);
						$str .= "<img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/meeting_in_progress.png\" /><br/>";
						$str .= $this->drawtimeline($openmeetings);
						$current = time();
						$meetingduration = $openmeetings->duration * 60;
						$meetingend = $openmeetings->timestarted + $meetingduration;							
						
						if($current > $openmeetings->timestarted && $current < $meetingend){
							if($openmeetings->is_moderated_room == 2 || $this->teacherIsCo($openmeetings) || (!empty($participantrec) && $participantrec->isadmin == 1)){
								$str .= "<br/><br/><a href=\"{$CFG->wwwroot}/blocks/openmeetings/join.php?id={$this->instance->id}&omid={$openmeetings->id}&courseid={$COURSE->id}\">$joinstr</a>";
							} else {
								$str .= "<br/><br/>$waitingformoderatorstr<br/>";
							}
						}					
							
						$str .= '</center>';
						$str .= $this->print_participants($openmeetings);
						$context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
						if (has_capability('block/openmeetings:start', $context)){
							 $str .= "<center><a href=\"{$CFG->wwwroot}/blocks/openmeetings/book.php?id={$this->instance->id}&courseid={$COURSE->id}\">$booksessionstr</a></center>";
						}
						if (has_capability('block/openmeetings:manage', $context)){	
							 $str .= "<center><a href=\"{$CFG->wwwroot}/blocks/openmeetings/close.php?id={$this->instance->id}&omid={$openmeetings->id}&cid={$COURSE->id}\">".get_string('close', 'block_openmeetings')."</a></center>";
						}
					}
				} else {
					// Non admin participant and the meetings has started
					$joinstr = get_string('join', 'block_openmeetings');
					$str .= '<center>';
					$str .= "<img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/private_meeting.png\" /><br/>";
					$str .= $this->drawtimeline($openmeetings);
					$str .= '<br/>';
					$current = time();
					$meetingduration = $openmeetings->duration * 60;
					$meetingend = $openmeetings->timestarted + $meetingduration;
					if(!empty($participantrec->id) && ($current > $openmeetings->timestarted) && ($current < $meetingend)){
						if(!empty($participantrec->connected) && ($participantrec->connected == 1)){
							$str .= get_string('alreadyconnected', 'block_openmeetings');
						} else {							
							if($openmeetings->is_moderated_room == 2 || $this->teacherIsCo($openmeetings) || (!empty($participantrec) && $participantrec->isadmin == 1)){
								$str .= "<br/><a href=\"{$CFG->wwwroot}/blocks/openmeetings/join.php?id={$this->instance->id}&omid={$openmeetings->id}&courseid={$COURSE->id}\">{$joinstr}</a>";
							} else {
								$str .= "<br/>$waitingformoderatorstr<br/>";
							}	
						}
					} else {
						$str .= get_string('meetingrunningnotinvited', 'block_openmeetings');
					}
					$str .= $this->print_participants($openmeetings);
					$str .= '</center>';
					$context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
					if (has_capability('block/openmeetings:start', $context)){
						$str .= "<center><a href=\"{$CFG->wwwroot}/blocks/openmeetings/book.php?id={$this->instance->id}&courseid={$COURSE->id}\">{$booksessionstr}</a></center>";
					}
					if (!empty($participantrec->isadmin) && $participantrec->isadmin == 1){	
						$str .= "<center><a href=\"{$CFG->wwwroot}/blocks/openmeetings/close.php?id={$this->instance->id}&omid={$openmeetings->id}&cid={$COURSE->id}\">".get_string('close', 'block_openmeetings')."</a></center>";
						//openmeetings_close_meeting($openmeetings);
					}				
				}
			} elseif (!$openmeetings->started != null && $openmeetings->started == 0){
				$participantrec = $this->participant($openmeetings, $USER->id);
				$nextom = $this->get_nextused_or_current_meeting();		
				if($nextom != null){
					$current = time();
					$meetingduration = $nextom->duration * 60;
					$meetingend = $nextom->timestarted + $meetingduration;				
				
					if($current > $nextom->timestarted and $current < $meetingend){
						if($nextom->is_moderated_room == 1){
							$str .= "<center><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/waiting_for_teacher.png\" /><br/>";	
							if(!empty($participantrec) && $participantrec->isadmin == 1){
								  $start = get_string('startthemeeting', 'block_openmeetings');
								  $str .= "<a href=\"{$CFG->wwwroot}/blocks/openmeetings/run.php?id={$this->instance->id}&omid=$openmeetings->id&courseid=$COURSE->id\">$start</a></center>";								
							} else {
								  $str .= "<br/>$waitingformoderator<br/>";
							}
						} else {
							 $str .= "<center><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/waiting_for_teacher.png\" /><br/>";	
							 $str .= "<a href=\"{$CFG->wwwroot}/blocks/openmeetings/run.php?id={$this->instance->id}&omid=$openmeetings->id&courseid=$COURSE->id\">$start</a></center>";
						}
						
					} else if ($current > $meetingend){
						$nextom->finish = 1;
						update_record('block_om_session', $nextom);
						$context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
						if (has_capability('block/openmeetings:start', $context)){
							$startstr = get_string('start', 'block_openmeetings');
							$str .= "<center><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/room_is_free.png\" /><br/>";
							$str .= "<a href=\"{$CFG->wwwroot}/blocks/openmeetings/start.php?id={$this->instance->id}&courseid={$COURSE->id}\">{$startstr}</a></center>";
							$str .= "<center><a href=\"{$CFG->wwwroot}/blocks/openmeetings/book.php?id={$this->instance->id}&courseid={$COURSE->id}\">{$booksessionstr}</a></center>";
							
						} else {
							$str .= "<center><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/room_is_free.png\" /><br/>";
							$str .= get_string('norunningmeeting', 'block_openmeetings');
						}						
					} else {
						$str .= "<br/>$start<br/>";
					}
				}
			}	
        } else {
            // participant will necessarily be host
            $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
            if (has_capability('block/openmeetings:start', $context)){
				
                $startstr = get_string('start', 'block_openmeetings');
                $str .= "<center><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/room_is_free.png\" /><br/>";
                $str .= "<a href=\"{$CFG->wwwroot}/blocks/openmeetings/start.php?id={$this->instance->id}&courseid=$COURSE->id\">$startstr</a></center>";
                $str .= "<center><a href=\"$CFG->wwwroot/blocks/openmeetings/book.php?id={$this->instance->id}&courseid=$COURSE->id\">$bookstr</a></center>";				
            } else {
                $str .= "<center><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/room_is_free.png\" /><br/>";
                $str .= get_string('norunningmeeting', 'block_openmeetings');
            }
        }     
        return $str;
    }
	
	function teacherIsCo($openmeetings){
		$participants = get_records('block_om_participant', 'openmeetingsid', $openmeetings->id);
		$ret = false;
		foreach($participants as $participant){
			if($participant->isadmin == 1 && $participant->connected == 1){
				$ret = true;
			}
		}
		return $ret;
	}
	
	/**
	* checks for the presence of a current meeting, and if not, get the last
	* passed meeting information of this room
	*
	*/
    function get_lastused_or_current_meeting(){
        $current = time();
        $lasttime = get_field_select('block_om_session', 'MAX(timestarted)', " blockid  = {$this->instance->id} AND ( (timestarted < $current OR started = 1) AND finish = 0 )");
		//echo $lasttime;
        if($lasttime){
            return (get_record('block_om_session', 'timestarted', $lasttime));
        } else {
            return null;
        }
    }	
	
	/**
	* checks for the presence of a current meeting, and if not,  
	* gets information about the next meeting scheduled in this room
	*/
    function get_nextused_or_current_meeting(){
        $current = time();
        $lasttime = get_field_select('block_om_session', 'MIN(timestarted)', " blockid  = {$this->instance->id} AND ( timestarted > $current OR (finish = 0 AND started = 0) )");
		//echo $lasttime;
        if($lasttime){
            return (get_record('block_om_session', 'timestarted', $lasttime));
        } else {
            return null;
        }
    }	
	
	/**
	* get the meeting participant record
	* @param ref $openmeetings the openmeeting session instance
	* @param int $userid
	*/
    function participant(&$openmeetings, $userid){
        return get_record('block_om_participant', 'openmeetingsid', $openmeetings->id, 'userid', $userid);
    }	

	/**
	* prints participant list on block
	* @param ref $openmeetings the openmeeting session instance
	*/	
    function print_participants(&$openmeetings){    		
        global $USER, $CFG, $COURSE;
		
		$str ='';
		
        $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
        if (has_capability('block/openmeetings:seeparticipants', $context)){
			
			$participants = get_records('block_om_participant', 'openmeetingsid', $openmeetings->id);
			
			$editable = false;
			if(has_capability('block/openmeetings:manage', $context)){
				$editable = true;
			}
			
			$str .= print_heading(get_string('participants', 'block_openmeetings'), 'center', 3, '', true);
			
			$str .= '<table width="95%">';
			//$participantids = array();
			if (!empty($participants)){
				foreach($participants as $participant){
					//if ($USER->id != $participant->userid) $participantids[] = $participant->userid;
					$str .= '<tr><td>';
					$user = get_record('user', 'id', $participant->userid);
					$str .= print_user_picture($user, $COURSE->id, NULL, 0, true, true, '',true) ;
					$str .= "<a href=\"{$CFG->wwwroot}/user/view.php?id={$user->id}\">".fullname($user).'</a>';
					$str .= '</td>';
					
					if ($participant->connected == 1){
						$str .= "<td><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/in.gif\" alt=\"".get_string('connected', 'block_openmeetings')."\" /></td>";
					} else {
						$str .= "<td><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/out.gif\" alt=\"".get_string('notconnected', 'block_openmeetings')."\" /></td>";
					}
					$str .= '</tr>';					
				}
			} else {
				$str .= '<tr><td>';
				$str .= get_string('noparticipants', 'block_openmeetings');
				$str .= '</td></tr>';
			}
			$str .= '</table>';
			
			if ($editable){
				$str .= "<br/><center><a href=\"{$CFG->wwwroot}/blocks/openmeetings/manage.php?id={$this->instance->id}&omid={$openmeetings->id}&courseid={$COURSE->id}\">".get_string('addparticipant', 'block_openmeetings').'</a></center>';
			}
		}		
		return $str;		
    }	

	/**
	* draws a graphical timeline in HTML
	*
	*/	
	function drawtimeline(&$openmeetings){
        global $CFG;
        
        $str = '';
        
        $barwidth = 103;
        $barheight = 13;
        $current = time();
        $meetingduration = $openmeetings->duration * 60;
        $meetingend = $openmeetings->timestarted + $meetingduration;
        if ($current < $openmeetings->timestarted){
            $str .= get_string('nextmeeting', 'block_openmeetings', userdate($openmeetings->timestarted));
        } elseif ($current > $meetingend){
            $str .= get_string('lastmeetingclosed', 'block_openmeetings');
            $openmeetings->started = 0;
        } else {
            $leftwidth = floor(($current - $openmeetings->timestarted) / $meetingduration * $barwidth);
            $rightwidth = floor(($meetingend - $current) / $meetingduration * $barwidth);
            $remaintime = (($meetingend - time()) > 0) ? $meetingend - time() : 0 ;
            $remainsstr = get_string('remains', 'block_openmeetings', format_time($remaintime));
			$starttime = date('G\hi',$openmeetings->timestarted);
			$endtime = date('G\hi',$meetingend);
            $str .= "$starttime <img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/bluebar.gif\" width=\"$leftwidth\" height=\"{$barheight}\"/><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/whitebar.gif\" width=\"5\" height=\"{$barheight}\"/><img src=\"{$CFG->wwwroot}/blocks/openmeetings/pix/bluebar.gif\" width=\"$rightwidth\"  height=\"{$barheight}\" title=\"$remainsstr\"/> $endtime";
        }        
        return $str;
    }
	
	/**
	* performs cleanup operations
	*/
	function cron(){		
		global $CFG;
	
		mtrace('Cron block_openmeetings\n');
		mtrace('Cleaning eventually stucked sessions\n');
		
		// get the block type from the name
		$blocktype = get_record( 'block', 'name', 'openmeetings' );
	 
		// get the instances of the block
		if (!$instances = get_records( 'block_instance','blockid', $blocktype->id )) return false;
	 
		// iterate over the instances
		$current = time();
		foreach ($instances as $instance) {
			$lasttime = get_field_select('block_om_session', 'MAX(timestarted)', " blockid  = ". $instance->id ." AND ( timestarted < $current OR started = 1 )");
			if($lasttime){
				$openmeetings = (get_record('block_om_session', 'timecreated', $lasttime));
			} else {
				$openmeetings = null;
			}			
			if(($openmeetings != null) && (!empty($openmeetings->duration)) && (!empty($openmeetings->timestarted))){		
				$current = time();
				$meetingduration = $openmeetings->duration * 60;
				$meetingend = $openmeetings->timestarted + $meetingduration;	
				if($current > $meetingend){
					openmeetings_close_meeting($openmeetings);
				}
			}			
		}		
		return true;
	}
	
}	
?>
