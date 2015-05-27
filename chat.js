                                  /*
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



                                    */

var user;
var crib;
var state=0;
var guest_list = {};
var updating=false;
var mes;
var file;

function Chat () {
    this.update = updateChat;
    this.send = sendChat;
    this.sendXY = sendXY;
    this.getState = getStateOfChat;
    this.ping = ping;
    this.user = '';
    this.crib = '';
}

function getStateOfChat(){
  console.log('getting state of chat...');
var time = new Date().getTime();
		 $.ajax({
			   type: "GET",
			   url: chat.crib + ".txt",
                           cache: false,
			   dataType: "text",
			   data: { 'time': time },
			   success: function(data){
				     if (chat.state == 0) {  // this client is joining
				       chat.state = data;
				       $("#spnStatus").html("(new join) " + data);
				     }

             else if (chat.state < data && chat.state != -1) {
				       $("#spnStatus").html("(loading) " + chat.state + "->" + data + "<br />");
				       updateChat();
				     }

             else {
				       $("#spnStatus").html("(current state) " + chat.state);
				     }
			   },
			});
}

function updateChat(){
	 if(!updating){
		 updating = true;
	     $.ajax({
			   type: "POST",
			   url: "process.php",
			   data: {  
			   			'function': 'update',
              'crib': chat.crib,
						'state': chat.state,
						'file': file
						},
			   dataType: "json",
			   success: function(data){
				   if(data.chats){  // received update array
				      for (var i = 0; i < data.chats.length; i++) {
                $('#spnStatus').append($("<p>"+ data.chats[i].user + ": " + data.chats[i].type +"</p>"));
              }
              
              performUpdates(data.chats);
				   }

				   updating = false;
				   chat.state = data.state;
			   },
			});
	 }
	 else {
		 setTimeout(updateChat, 1500);
	 }
}

function performUpdates(data) {

  for (var x = 0; x < data.length; x++) {
    update = data[x];

    switch(update.type) {
      case "xy":

       if (update.x == -1) { // user has signed off deliberately
         if ($('#' + update.user).length > 0) ghostUser([update.user]);
       
       }

       else {
            if ($('#' + update.user).length == 0) {    //no av exists for user
                $('#avs').append('<div class="av" id="' + update.user + '" room="true"><img class="avIcon" src="'
                                                        + update.user_av + '" /></div>');
            }
            
            clearTimeout(guest_list[update.user]);
            guest_list[update.user] = setTimeout(ghostUser, 120000, [update.user]);
            // if a user doesnt ping their XY within 2 minutes, they are ghosted from the client

            if (update.user != chat.user) {
                $('#' + update.user).offset({left: update.x - 20,top: update.y - 20});
            }
        }

        break;

      case "msg":

            if ($('#' + update.user).length == 0) {    //no av exists for user
              $('#avs').append('<div class="av" id="' + update.user + '" room="true"><img class="avIcon" src="'
                                                      + update.user_av + '" /></div>');
            }

            $('[id="bubble2"][user=' + update.user + ']').remove();

            $('#' + update.user).append($("#bubble.live-tipsy").clone().attr("id", "bubble2")
                                                                       .attr("user", update.user)
                                                                       .attr("title", update.data));

            $('[id="bubble2"][user=' + update.user + ']').trigger('mouseover').trigger('mouseout');

            var tipCount = $('[class^="tipsy"][user=' + update.user + ']').length;
            var tipHeight=new Array();

            tipHeight[0] = $('[class^="tipsy"][user=' + update.user + ']').eq(0).find('div').eq(1).height();

            $('[class^="tipsy"][user=' + update.user + ']').eq(1).find('div').eq(0).remove();
            tipHeight[1] = $('[class^="tipsy"][user=' + update.user + ']').eq(1).find('div').height();

            // cascades previous tips up
            for (n=tipCount-1; n>0; n--) {
                currentPos = $('[class^="tipsy"][user=' + update.user + ']').eq(n).offset();
                $('[class^="tipsy"][user=' + update.user + ']').eq(n).removeClass("noEase"); //for motion tweening
                $('[class^="tipsy"][user=' + update.user + ']').eq(n)
                   .offset({left: currentPos.left,top:currentPos.top - (tipHeight[0] / 2) - (tipHeight[1] / 2) - 10});
            }

            $('[class^="tipsy"][user=' + update.user + ']').eq(0).removeClass("noEase");
            $('[class^="tipsy"][user=' + update.user + ']').eq(0).addClass("fastEase"); //motion
            var newTipPos = $('[class^="tipsy"][user=' + update.user + ']').eq(0).offset();
            $('[class^="tipsy"][user=' + update.user + ']').eq(0).offset({
                                                     left: newTipPos.left,
                                                     top: newTipPos.top - 20});
        break;
    }
  }
}

function sendChat(message)
{       
   if (chat.user != '') {
     $.ajax({
		   type: "POST",
		   url: "process.php",
		   data: {  
		   			'function': 'msg',
		   			'crib': chat.crib,
					'message': message,
					'x': x,
					'y': y,
					'file': file
				 },
		   dataType: "json",
		   success: function(data){
			   updateChat();
		   },
		});
   }
}

function sendXY(x, y)
{       
   if (chat.user != '') {
     $.ajax({
		   type: "POST",
		   url: "process.php",
		   data: {  
		   			'function': 'xy',
		   			'crib': chat.crib,
					'x': x,
					'y': y,
					'file': file
				 },
		   dataType: "json",
		   success: function(data){
			   updateChat(); 
		   },
		});
   }
}

function ghostUser(u)
{
  delete guest_list[u[0]];

  if (u[0] == chat.user) {
    chat.state = -1;
  }
  
  else {
    $('[id="'+u[0]+'"]').remove();
  }
}

function ping()
{
        if(chat.state > 0) {
            var oset = $('#' + chat.user).offset();
            chat.sendXY(oset.left + 20, oset.top + 20);
        }
}
