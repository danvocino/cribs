<?php

 require_once('/home/sndvsn/php/HTTP/OAuth/Consumer.php');

 session_start();

 // consumer key/secret for twitter oauth sign-in
 $consumer_key = '-------------';
 $consumer_secret = '-------------';

 // twitter sign-in callback url
 if (!empty($_GET['c'])) {
   // user-created crib
   $callback_url = 'http://----------/index.php?c=' . $_GET['c'];
 } else {
   // lobby crib
   $callback_url = 'http://----------/index.php';
 }

$content = ''; // placeholder

if ($_REQUEST['cya'] === 'clear') {
    session_destroy();
    session_start();
}

try {

    $oauth = new HTTP_OAuth_Consumer($consumer_key, $consumer_secret);
    
    $http_request = new HTTP_Request2();
    $http_request->setConfig('ssl_verify_peer', false);
    $http_request->setConfig('ssl_verify_host', false);
    $http_request->setHeader('Accept-Encoding', '.*');

    $consumer_request = new HTTP_OAuth_Consumer_Request;
    $consumer_request->accept($http_request);
    $oauth->accept($consumer_request);

    if (!empty($_REQUEST['oauth_token']) && $_SESSION['oauth_state'] === 'start') {
        $_SESSION['oauth_state'] = 'returned';

        if (empty($_SESSION['oauth_access_token']) || empty($_SESSION['oauth_access_token_secret'])) {
            // no access token, grab it
            $oauth->setToken($_SESSION['oauth_request_token']);
            $oauth->setTokenSecret($_SESSION['oauth_request_token_secret']);

            $oauth_verifier = $_REQUEST['oauth_verifier'];
            $oauth->getAccessToken('https://twitter.com/oauth/access_token', $oauth_verifier);

            $_SESSION['oauth_access_token'] = $oauth->getToken();
            $_SESSION['oauth_access_token_secret'] = $oauth->getTokenSecret();
        }

    }

    if (!empty($_SESSION['oauth_access_token']) && !empty($_SESSION['oauth_access_token_secret'])) {

        $oauth->setToken($_SESSION['oauth_access_token']);
        $oauth->setTokenSecret($_SESSION['oauth_access_token_secret']);

        $result = $oauth->sendRequest('https://api.twitter.com/1.1/account/verify_credentials.json', array(), 'GET');
        
        $json = json_decode($result->getBody());
        $_SESSION['screen_name'] = (string)$json->screen_name;
        $_SESSION['profile_image_url'] = (string)$json->profile_image_url;

    } else {

        $oauth->getRequestToken('https://twitter.com/oauth/request_token', $callback_url);

        $_SESSION['oauth_request_token'] = $oauth->getToken();
        $_SESSION['oauth_request_token_secret'] = $oauth->getTokenSecret();

        $_SESSION['oauth_state'] = "start";

        $request_link = $oauth->getAuthorizeURL('https://twitter.com/oauth/authorize');
        $content = $request_link;

    }

} catch (Exception $e) {
    $content = $e->getMessage();
}
?>

                                 <!--
                            ___                               
                           /\  \                              
              ___         _\:\  \       ___           ___     
             /\__\       /\ \:\  \     /\__\         /\__\    
            /:/  /      _\:\ \:\  \   /:/__/        /:/  /    
           /:/__/      /\ \:\ \:\__\ /::\  \       /:/__/     
          /::\  \      \:\ \:\/:/  / \/\:\  \__   /::\  \     
         /:/\:\  \      \:\ \::/  /   ~~\:\/\__\ /:/\:\  \    
         \/__\:\  \      \:\/:/  /       \::/  / \/__\:\  \   
              \:\__\      \::/  /        /:/  /       \:\__\  
               \/__/       \/__/         \/__/         \/__/  
       ___           ___                                     ___     
      /\__\         /\  \                     _____         /\__\    
     /:/  /        /::\  \       ___         /::\  \       /::|  |   
    /:/  /        /:/\:\__\     /\__\       /:/\:\  \     /:/:|  |   
   /:/  /  ___   /:/ /:/  /    /:/__/      /:/ /::\__\   /:/|:|  |__ 
  /:/__/  /\__\ /:/_/:/__/___ /::\  \     /:/_/:/\:|__| /:/ |:| /\__\
  \:\  \ /:/  / \:\/:::::/  / \/\:\  \__  \:\/:/ /:/  / \/__|:|/:/  /
   \:\  /:/  /   \::/~~/~~~~   ~~\:\/\__\  \::/_/:/  /      |:/:/  / 
    \:\/:/  /     \:\~~\          \::/  /   \:\/:/  /       |::/  /  
     \::/  /       \:\__\         /:/  /     \::/  /        |:/  /   
      \/__/         \/__/         \/__/       \/__/         |/__/    



                                   -->
<html>
<head>
 <title>twitcri.bz</title>

<link rel="stylesheet" href="style.css" type="text/css" />
<link rel="stylesheet" type="text/css" href="tipsy.css" />

<script type='text/javascript' src='http://code.jquery.com/jquery-1.6.4.js'></script>
<script type="text/javascript" src="jquery.tipsy.js"></script>
<script type="text/javascript" src="chat.js"></script>

<script type='text/javascript'>

        var chat =  new Chat();
        chat.state = -1;

    	$(function() {
    	
    		 // watch textarea for key presses
             $("#sendie").keydown(function(event) {  
             
                 var key = event.which;  
           
                 //all keys including return.
                 if (key >= 33) {
                   
                     var maxLength = $(this).attr("maxlength");  
                     var length = this.value.length;  
                     
                     // don't allow new content if length is maxed out
                     if (length >= maxLength) {  
                         event.preventDefault();  
                     }
                 }
              });
    	         // watch textarea for release of key press
              $('#sendie').keyup(function(e) {	

    	             if (e.keyCode == 13) { 
    			  
                     var text = $(this).val();
    		     var maxLength = $(this).attr("maxlength");  
                     var length = text.length; 
                     
                          // send 
                         if (length <= maxLength + 1) { 
    		                   chat.send(text);
    		                   $(this).val("");
                         } else {
    		                   $(this).val(text.substring(0, maxLength));
    		                 }		
    	             }
             });
            
    	});
</script>

<script type='text/javascript'>

var user='', crib='', x=-1, y=-1;

$(window).load(function(){
 $(document).ready(function(){


    <?php
      if (!empty($_SESSION['screen_name'])) {
        echo 'chat.user = "' . $_SESSION['screen_name'] . '";';
      }

      if (!empty($_GET['c'])) {
        $crib = htmlentities(strip_tags($_GET['c']));
        echo "\n    " . 'chat.crib = "' . $crib . '";';
      } else {
        $crib = 'lobby';
        echo "\n    " . 'chat.crib = "lobby";';
      }

      if (!file_exists($crib . '.txt')) { file_put_contents($crib . '.txt','42069'); }
      
      if (!file_exists($crib . '.json')) {
        $json = json_encode(array('timestamp' => 42069,
                                  'type' => 'new', 'user' => ''));
        file_put_contents($crib . '.json',$json);
      }
    ?>

    chat.update();

    setInterval(chat.ping, 60000); // client will ghost users when their ping stops
    document.getElementById("sendie").focus();

    $('[room="true"]').click(function(e){

        if (e.target.className == 'signIn') {
            document.getElementById('clickSignIn').click();
        }

        if (e.target.className == 'signout') {
          $.ajax({
            type: "POST",
            async: false,
            url: "process.php",
            data: {  
		   			'function': 'xy',
		   			'crib': chat.crib,
					'x': -1,
					'y': -1,
					'file': file
				 },
            success: function(msg){
             // logout success
            }
          });
        }

      if (chat.state == -1) $('#' + chat.user).find('img').css('height', '7%');

      if (e.target.className !== 'avIcon') {
        // user clicked empty space in room, move avatar to that location
        $('#' + chat.user).fadeIn(1200);
        $('#' + chat.user).offset({left:e.pageX-20,top:e.pageY-20});
        x = e.pageX;
        y = e.pageY;

        chat.sendXY(x, y); // post to server

        document.getElementById("sendie").focus(); //give focus back to chat input
      }

    });

    $(document).mousemove(function(e){
        $('#spnCursor').html("x: " + e.pageX + " y: " + e.pageY);
    });

 });
});

$(window).unload(function () { 
    $.ajax({
        type: "POST",
        async: false,
        url: "process.php",
        data: {  
		   			'function': 'xy',
		   			'crib': chat.crib,
					'x': -1,
					'y': -1,
					'file': file
				 },
        success: function(msg){
             // logout success
        }
    });
});

</script>

<!-- get click-throughs to work with IE -->
    
    <!--[if IE]>
      <style type="text/css">

        .av {
          filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='transparent.png', sizingMethod='scale');
          background:none !important;
        }

        </style>
    <![endif]-->

</head>

<body<?php if (!empty($_SESSION['screen_name'])) { echo ' onload="setInterval(\'chat.getState()\', 5000)"'; } ?>>

<div class="floor">

  <div id="menu" room="true" style="opacity:1;position:absolute;
                                    top:0px;left:0px;width:100%;
                                    z-index:999998;background-image:url('menu.png');
                                    background-repeat:repeat-x;">
    <img src="menu.png" />
  </div>

  <div id="room" style="height:500px;position:absolute;top:0px;left:0px;">
    <img class="roomImage" src="minimal_wall.gif"
         style="height: 100%;vertical-align: top;" room="true">
  </div>

  <?php
    if ($_SESSION['oauth_state'] == 'start' && empty($_REQUEST['oauth_token'])) {
      echo '<div class="signIn" room="true"><a href="' . $content . '" id="clickSignIn"><img src="sign-in-with-twitter.png"></a></div>';
    } else {
      //echo '<div style="position:absolute;top:0px;left:0px;"><a href="' . $content . '"><img src="sign-in-with-twitter.png"></a></div>';
    }
  ?>

</div>

<div id="avs" style="top:440px;left:20px;position:absolute;" room="true">

  <?php
    if (!empty($_SESSION['screen_name'])) {
      echo '<div class="av" id="' . $_SESSION['screen_name'] . '" room="true"><img class="avIcon" src="' . $_SESSION['profile_image_url'] . '" /></div>';
    }
  ?>

</div>

<div style="position:absolute;top:40px;z-index:30;">
  <p><a href='index.php?cya=clear' class='signout'>sign out</a></p>
  <span id="spnCursor"></span>
  <br />
  <span id="spnStatus"></span>
</div>

<div id="chat-stuff" style="top:500px;left:20px;position:absolute;">

  <div style="float:left;">
    <form id="send-message-area">
      <textarea id="sendie" maxlength = '140' ></textarea>
    </form>
  </div>

  <br />
  <div id="page-wrap" style="display:none;float:left;clear:both;">
    <div id="chat-wrap">
      <div id="chat-area">
        <?
          echo '<pre>';
          print_r($_SESSION);
          echo '</pre>';
        ?>
      </div>
    </div>
  </div>

<div id='live-example' class='example'>
  <div id='live-template' style='display:inline'>
    <a href='#' class='live-tipsy' id='bubble' title='<img src="MountainsWater.gif" style="width:100%;">' user='booop'></a>
  </div>
</div>

<script type='text/javascript'>
  $('a.live-tipsy').tipsy({live: true, delayOut: 7000, html: true, gravity: $.fn.tipsy.autoWE});
</script>

</div>
</body>


</html>