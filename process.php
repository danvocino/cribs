<?php

  function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
  }

  $function = $_POST['function'];
  $crib_file = $_POST['crib'] . '.json';
    
  $log = array();
    
  switch($function) {

    case('update'):
      session_start();

    	$state = $_POST['state'];

      if(file_exists($crib_file)){
        $updates = json_decode(file_get_contents($crib_file), TRUE);

        $last_state = $updates[sizeof($updates) - 1][timestamp];
        $log['state'] = $last_state;

        if($state >= $last_state) { //greater-than (as opposed to ==) in case we increase $time adjustment
          $log['chats'] = false;
          $log['user'] = false;
        } 
        else {
          $new = array();
          $time = microtime_float() - 1344250000;
          
          foreach ($updates as $key => $val) {
            if($state == -1){ //new join or coming back from ghost
              if($val['type'] == 'xy' && $time - $val['timestamp'] < 120) {
                $new[] = $val; //collect all xy's
              }
            }
            else {
              if($val['timestamp'] > $state) {
                $new[] = $val;
              }
            }
          }

          if (!empty($_SESSION['screen_name'])) {
            $log['user'] = $_SESSION['screen_name'];
          }

          $log['chats'] = $new;
        }
      }
      break;

    case('xy'):
      session_start();
	         
      if ($_SESSION['screen_name'] != null) {
        $updates = array();
        $json = array();

        $updates = json_decode(file_get_contents($crib_file), TRUE);

        $time = microtime_float() - 1344250000;
        $incoming = array('timestamp' => $time,
                          'type' => 'xy', 'user' => $_SESSION['screen_name'],
                          'user_av' => $_SESSION['profile_image_url'],
                          'x' => $_POST['x'], 'y' => $_POST['y']);

		    foreach ($updates as $key => $val) { 
         // maybe ~300 so entries older than 5mins get left out
          if ($time - $val['timestamp'] < 120) {
           //if user is signing out, leave out all previous user entries
            if (!($_POST['x'] == -1 && $val['user'] == $_SESSION['screen_name'])) {
              $json[] = $val;
            }
          }
        }

        $json[] = $incoming;

        $fp = fopen($crib_file, 'w');
        fwrite($fp, json_encode($json));
        fclose($fp);

        file_put_contents($_POST['crib'] . '.txt', $time);
      }
      break;
    	 
    case('msg'):
      session_start();

      if ($_SESSION['screen_name'] != null) {

        $msg_end = substr($_POST['message'], -4);

        if ($msg_end != '%;">' && $msg_end != '%;">' && $msg_end != '%;">') {
          $message = htmlentities(strip_tags($_POST['message']));
        } else {
          $message = $_POST['message'];
        }

        $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		 
        if(($message) != "\n"){

          if(preg_match($reg_exUrl, $message, $url)) {
            $message = preg_replace($reg_exUrl,
                                    '<a href="'.$url[0].'" target="_blank">'.$url[0].'</a>',
                                    $message);
		      }
			 
          $updates = array();
      	  $json = array();

          $updates = json_decode(file_get_contents($crib_file), TRUE);

          $time = microtime_float() - 1344250000;
          $incoming = array('timestamp' => $time,
                            'type' => 'msg', 'user' => $_SESSION['screen_name'],
                            'user_av' => $_SESSION['profile_image_url'],
                            'data' => $message, 'x' => $_POST['x'],
                            'y' => $_POST['y']);

    		  foreach ($updates as $key => $val) {
            // this should be 300 so entries older than 5mins get left out
            if ($time - $val['timestamp'] < 120) {
              $json[] = $val;
            }
          }

          $json[] = $incoming;

          $fp = fopen($crib_file, 'w');
          fwrite($fp, json_encode($json));
          fclose($fp);

          file_put_contents($_POST['crib'] . '.txt', $time);
		    }
      }
      break;
    }
    
    echo json_encode($log);
?>
