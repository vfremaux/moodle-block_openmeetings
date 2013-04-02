<?php  // $Id: openmeetings_gateway.php,v 1.3 2012-02-07 16:35:52 vf Exp $
/*
 * Created on 13.05.2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 * 
 * Sebastian Wagner
 */
 
// in serializeType: name=parameters, type=http://services.axis.openmeetings.org:loginUser^, use=literal, encodingStyle=, unqualified=qualified
// in serializeType: name=parameters, type=http://services.axis.openmeetings.org:loginUser^, use=literal, encodingStyle=, unqualified=qualified


//<loginUser xmlns=http://services.axis.openmeetings.org><SID>dad083bbeb46d077e3748d40d11a28fe</SID><username>swagner</username><userpass>asdasd</userpass></loginUser>
//<loginUser xmlns="http://services.axis.openmeetings.org"><SID>93e7e34036e1b0a243a481c12bd4fb7d</SID><username>SebastianWagner</username><userpass>asdasd</userpass></loginUser>
//addRoom xmlns="http://services.axis.openmeetings.org"><SID>e4477f29c7c99bca7768c06a75e35cdc</SID><name>MOODLE_COURSE_ID_6_NAME_asdasdasd</name><roomtypes_id>1</roomtypes_id><comment>Created by SOAP-Gateway for Moodle Platform</comment><numberOfPartizipants>4</numberOfPartizipants><ispublic>true</ispublic><videoPodWidth>270</videoPodWidth><videoPodHeight>280</videoPodHeight><videoPodXPosition>2</videoPodXPosition><videoPodYPosition>2</videoPodYPosition><moderationPanelXPosition>400</moderationPanelXPosition><showWhiteBoard>true</showWhiteBoard><whiteBoardPanelXPosition>276</whiteBoardPanelXPosition><whiteBoardPanelYPosition>2</whiteBoardPanelYPosition><whiteBoardPanelHeight>592</whiteBoardPanelHeight><whiteBoardPanelWidth>660</whiteBoardPanelWidth><showFilesPanel>true</showFilesPanel><filesPanelXPosition>2</filesPanelXPosition><filesPanelYPosition>284</filesPanelYPosition><filesPanelHeight>310</filesPanelHeight><filesPanelWidth>270</filesPanelWidth></addRoom>

//echo "DIRROOT: ".$CFG->dirroot."<br/>";

require_once($CFG->dirroot.'/blocks/openmeetings/lib/nusoap.php');
require_once($CFG->dirroot.'/blocks/openmeetings/locallib.php');
//require_once($CFG->dirroot.'/lib/soaplib.php');

class openmeetings_gateway {
	
	var $session_id = "";

	/**
	 * TODO: Get Error Service and show detailed Error Message
	 */
	function openmeetings_loginuser() {
		global $USER, $CFG;
	
		//		echo $CFG->openmeetings_red5host."<br/>";
		//		echo $CFG->openmeetings_red5port."<br/>";	
		//		
		//		echo "USER: ".$CFG->openmeetings_openmeetingsAdminUser."<br/>";
		//		echo "Pass: ".$CFG->openmeetings_openmeetingsAdminUserPass."<br/>";
		
		//echo "DIRROOT: ".$CFG->dirroot."<br/>";
		
		$client_userService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/UserService?wsdl", "wsdl");
		$client_userService->setUseCurl(true);
		//echo "Client inited"."<br/>";
		$err = $client_userService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror', 'block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug', 'block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
		}  
	
		$result = $client_userService->call('getSession');
		
		if ($client_userService->fault) {
			echo '<h2>'.get_string('soapstructureerror', 'block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_userService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror', 'block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result); echo '</pre>';
				$this->session_id = $result['return']['session_id'];
				//echo '<h2>Result</h2><pre>'; printf(); echo '</pre>';
				$params = array(
	    			'SID' => $this->session_id,
	    			'username' => $CFG->openmeetings_openmeetingsAdminUser,
	    			'userpass' => $CFG->openmeetings_openmeetingsAdminUserPass
				);
				
				//$params = array();
				
				$result = $client_userService->call('loginUser',$params);
				
				//echo '<h2>Params</h2><pre>'; print_r($params); echo '</pre>';
				if ($client_userService->fault) {
					echo '<h2>'.get_string('soapstructureerror', 'block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
				} else {
					
					$err = $client_userService->getError();
					if ($err) {
						echo '<h2>'.get_string('soaperror', 'block_openmeetings').'</h2><pre>' . $err . '</pre>';
					} else {
					
						//echo '<h2>Result</h2><pre>'; print_r($result); echo '</pre>';
						$returnValue = $result["return"];	
						//echo '<h2>returnValue</h2><pre>'; printf($returnValue); echo '</pre>';		
					}
				}
			}
		}   
		if (@$returnValue > 0){
	    	return true;
		} else {
			return false;
		}
	}

	/**
	 * TODO: Check Error handling
	 * 
	 * @deprecated this method is deprecated
	 * 
	 */
	function openmeetings_createroom($openmeetings, $roomtypes_id) {
		global $USER, $CFG;
	
		//		echo $CFG->openmeetings_red5host."<br/>";
		//		echo $CFG->openmeetings_red5port."<br/>";	
		//		foreach ($CFG as $key => $value){
		//    		echo "KEY: ".$key." Value: ".$value."<br/>";
		//    	}
    	$course_name = 'MOODLE_COURSE_ID_'.$openmeetings->course.'_NAME_'.$openmeetings->name;
    	//echo "CourseName: ".$course_name."<br/>";	
		
		//echo $client_userService."<br/>";
	    
	 	$client_roomService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/RoomService?wsdl", true);
		
		$err = $client_roomService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror', 'block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug', 'block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}  
		$params = array(
			'SID' => $this->session_id,
			'name' => om_block_fix_charset($course_name),
			'roomtypes_id' => $roomtypes_id,
			'comment' => get_string('soapcomment', 'block_openmeetings'),
			'numberOfPartizipants' => 4,
			'ispublic' => true,
			'videoPodWidth' => 270, 
			'videoPodHeight' => 280,
			'videoPodXPosition' => 2, 
			'videoPodYPosition' => 2, 
			'moderationPanelXPosition' => 400, 
			'showWhiteBoard' => true, 
			'whiteBoardPanelXPosition' => 276, 
			'whiteBoardPanelYPosition' => 2, 
			'whiteBoardPanelHeight' => 592, 
			'whiteBoardPanelWidth' => 660, 
			'showFilesPanel' => true, 
			'filesPanelXPosition' => 2, 
			'filesPanelYPosition' => 284, 
			'filesPanelHeight' => 310, 
			'filesPanelWidth' => 270
		);
		$result = $client_roomService->call('addRoom', $params);
		if ($client_roomService->fault) {
			echo '<h2>'.get_string('soapstructureerror', 'block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_roomService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror', 'block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result["return"];
			}
		}   
		return -1;
	}
	
	function openmeetings_createroomwithmod($openmeetings) {
		global $USER, $CFG;
	
		//		echo $CFG->openmeetings_red5host."<br/>";
		//		echo $CFG->openmeetings_red5port."<br/>";	
		//		foreach ($CFG as $key => $value){
		//    		echo "KEY: ".$key." Value: ".$value."<br/>";
		//    	}
    	$course_name = 'MOODLE_COURSE_ID_'.$openmeetings->course.'_NAME_'.$openmeetings->name;
    	//echo "CourseName: ".$course_name."<br/>";	
		
		//echo $client_userService."<br/>";
	    
	 	$client_roomService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/RoomService?wsdl", true);
		
		$err = $client_roomService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror', 'block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug', 'block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}  
		
		$isModeratedRoom = false;
		if ($openmeetings->is_moderated_room == 1) {
			$isModeratedRoom = true;
		}
		
		$params = array(
			'SID' => $this->session_id,
			'name' => om_block_fix_charset($course_name),
			'roomtypes_id' => $openmeetings->type,
			'comment' => get_string('soapcomment', 'block_openmeetings'),
			'numberOfPartizipants' => $openmeetings->max_user,
			'ispublic' => true,
			'appointment' => false, 
			'isDemoRoom' => false, 
			'demoTime' => 0, 
			'isModeratedRoom' => $isModeratedRoom
		);
		$result = $client_roomService->call('addRoomWithModeration', $params);
		if ($client_roomService->fault) {
			echo '<h2>'.get_string('soapstructureerror', 'block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_roomService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror', 'block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result['return'];
			}
		}   
		return -1;
	}
	
	function openmeetings_createRoomWithModAndType($openmeetings) {
		global $USER, $CFG;
	
		//		echo $CFG->openmeetings_red5host."<br/>";
		//		echo $CFG->openmeetings_red5port."<br/>";	
		//		foreach ($CFG as $key => $value){
		//    		echo "KEY: ".$key." Value: ".$value."<br/>";
		//    	}
    	$course_name = 'MOODLE_COURSE_ID_'.$openmeetings->blockid.'_NAME_'.$openmeetings->name;
    	//echo "CourseName: ".$course_name."<br/>";	
		
		//echo $client_userService."<br/>";
	    
	 	$client_roomService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/RoomService?wsdl", true);
		
		$err = $client_roomService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror', 'block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug', 'block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}  
		
		$isModeratedRoom = false;
		if ($openmeetings->is_moderated_room == 1) {
			$isModeratedRoom = true;
		}

		$ispublic = false;
		if ($openmeetings->ispublic == 1) {
			$ispublic = true;
		}
		
		$params = array(
			'SID' => $this->session_id,
			'name' => om_block_fix_charset($course_name),
			'roomtypes_id' => $openmeetings->type,
			'comment' => get_string('soapmessage', 'block_openmeetings'),
			'numberOfPartizipants' => $openmeetings->max_user,
			'ispublic' => $ispublic,
			'appointment' => false, 
			'isDemoRoom' => false, 
			'demoTime' => 0, 
			'isModeratedRoom' => $isModeratedRoom,
			'externalRoomType' => 'moodle'
		);
		$result = $client_roomService->call('addRoomWithModerationAndExternalType',$params);
		if ($client_roomService->fault) {
			echo '<h2>'.get_string('soapstructureerror','block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_roomService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result["return"];
			}
		}   
		return -1;
	}
	
		
	function openmeetings_getRecordingsByExternalRooms(){
		global $USER, $CFG;
		
		$client_roomService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/RoomService?wsdl", true);
		
		$err = $client_roomService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug','block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}  
		$params = array(
			'SID' => $this->session_id,
			'externalRoomType' => 'moodle'
			);
		//We prefer the List ?!
		$result = $client_roomService->call('getFlvRecordingByExternalRoomTypeByList',$params);
		if ($client_roomService->fault) {
			echo '<h2>'.get_string('soapstructureerror','block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_roomService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result); echo '</pre>';
				return $result["return"];
			}
		}   
		return -1;
	}
	
	/*
	 * Usage if this Method will work if you have no need to simulate always the same user in 
	 * OpenMeetings, if you want to do this check the next method that also remembers the 
	 * ID of the external User
	 * 
	 * 
	 */
	function openmeetings_setUserObject($username, $firstname, $lastname,$profilePictureUrl, $email) {
	    global $USER, $CFG;
	 	$client_userService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/UserService?wsdl", true);
		
		$err = $client_userService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug','block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}  
		$params = array(
			'SID' => $this->session_id,
			'username' => om_block_fix_charset($username),
			'firstname' => om_block_fix_charset($firstname),
			'lastname' => om_block_fix_charset($lastname),
			'profilePictureUrl' => $profilePictureUrl,
			'email' => om_block_fix_charset($email)
		);
		$result = $client_userService->call('setUserObject',$params);
		if ($client_roomService->fault) {
			echo '<h2>'.get_string('soapstructureerror','block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_userService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result['return'];
			}
		}   
		return -1;
	}
	
	
	function openmeetings_deleteRoom($openmeetings) {
	    global $USER, $CFG;
		
	 	$client_roomService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/RoomService?wsdl", true);
		
		$err = $client_roomService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug','block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}  
		$params = array(
			'SID' => $this->session_id,
			'rooms_id' => $openmeetings->room_id
		);
		$result = $client_roomService->call('deleteRoom',$params);
		if ($client_roomService->fault) {
			echo '<h2>'.get_string('soapstructureerror','block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_roomService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result['return'];
			}
		}   
		return -1;
	}	
	/**
	 * Sets the User Id and remembers the User, 
	 * the value for $systemType is any Flag but usually should always be the same, 
	 * it only has a reason if you have more then one external Systems, so the $userId will not 
	 * be unique, then you can use the $systemType to give each system its own scope
	 * 
	 * so a unique external user is always the pair of: $userId + $systemType
	 * 
	 * in this case the $systemType is 'moodle'
	 * 
	 */
	function openmeetings_setUserObjectWithExternalUser($username, $firstname, $lastname, $profilePictureUrl, $email, $userId, $systemType) {
	    global $USER, $CFG;
	 	$client_userService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/UserService?wsdl", true);
		
		$err = $client_userService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug','block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}  
		$params = array(
			'SID' => $this->session_id,
			'username' => om_block_fix_charset($username),
			'firstname' => om_block_fix_charset($firstname),
			'lastname' => om_block_fix_charset($lastname),
			'profilePictureUrl' => $profilePictureUrl,
			'email' => om_block_fix_charset($email),
			'externalUserId' => $userId,
			'externalUserType' => $systemType
		);
		$result = $client_userService->call('setUserObjectWithExternalUser',$params);
		if ($client_roomService->fault) {
			echo '<h2>'.get_string('soapstructureerror','block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_userService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result["return"];
			}
		}   
		return -1;
	}
	
	/*
	
	public String setUserObjectAndGenerateRoomHashByURL(String SID, String username, String firstname, String lastname, 
			String profilePictureUrl, String email, Long externalUserId, String externalUserType,
			Long room_id, int becomeModeratorAsInt, int showAudioVideoTestAsInt)
			
			Array ( 
			[SID] => b46c5537c94f5bd0df4664edd3d471a1 
			[username] => admin 
			[firstname] => Sebastian 
			[lastname] => Wagner 
			[profilePictureUrl] => 1 
			[email] => seba.wagner@gmail.com 
			[externalUserId] => 2 
			[externalUserType] => moodle 
			[room_id] => 8 
			[becomeModeratorAsInt] => 
				[showAudioVideoTestAsInt] => 1 )
	
	*/
	function openmeetings_setUserObjectAndGenerateRoomHashByURL($username, $firstname, $lastname, $profilePictureUrl, $email, $userId, $systemType, $room_id, $becomeModerator) {
	    global $USER, $CFG;

	 	$client_userService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/UserService?wsdl", true);
		
		$err = $client_userService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug','block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}  
		$params = array(
			'SID' => $this->session_id,
			'username' => om_block_fix_charset($username),
			'firstname' => om_block_fix_charset($firstname),
			'lastname' => om_block_fix_charset($lastname),
			'profilePictureUrl' => $profilePictureUrl,
			'email' => om_block_fix_charset($email),
			'externalUserId' => $userId,
			'externalUserType' => $systemType,
			'room_id' => $room_id,
			'becomeModeratorAsInt' => $becomeModerator,
			'showAudioVideoTestAsInt' => 1
		);
		
		$result = $client_userService->call('setUserObjectAndGenerateRoomHashByURL',$params);

		if ($client_userService->fault) {
			echo '<h2>'.get_string('soapstructureerror','block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_userService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result["return"];
			}
		}   
		return -1;
	}
	
	/*
	 * public String setUserObjectAndGenerateRecordingHashByURL(String SID, String username, String firstname, String lastname,
					Long externalUserId, String externalUserType, Long recording_id)
	 */
	 function openmeetings_setUserObjectAndGenerateRecordingHashByURL($username, $firstname, $lastname, $userId, $systemType, $recording_id) {
	    global $USER, $CFG;

	 	$client_userService = new nusoap_client_om("http://".$CFG->openmeetings_red5host.":".$CFG->openmeetings_red5port."/openmeetings/services/UserService?wsdl", true);
		
		$err = $client_userService->getError();
		if ($err) {
			echo '<h2>'.get_string('soapconstructorerror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			echo '<h2>'.get_string('soapdebug','block_openmeetings').'</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}  
		$params = array(
			'SID' => $this->session_id,
			'username' => om_block_fix_charset($username),
			'firstname' => om_block_fix_charset($firstname),
			'lastname' => om_block_fix_charset($lastname),
			'externalUserId' => $userId,
			'externalUserType' => $systemType,
			'recording_id' => $recording_id
		);
		
		$result = $client_userService->call('setUserObjectAndGenerateRecordingHashByURL',$params);
		if ($client_userService->fault) {
			echo '<h2>'.get_string('soapstructureerror','block_openmeetings').'</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $client_userService->getError();
			if ($err) {
				echo '<h2>'.get_string('soaperror','block_openmeetings').'</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result["return"];
			}
		}   
		return -1;
	}
	
}

?>
