var $messages = $('.messages-content'),
    d, h, m,
    i = 0;

var lastIndex=1;
//var number="";
var channel="";
var taskid="";
var agentName="";
var customerName="";
var pollTimer="";
var queueTimer="";
var sessionStarted=false;
var lastselectedskills = "";
$(window).load(function() {
  $messages.mCustomScrollbar();
  $( "#dialog" ).dialog({
     autoOpen: false 
 });

   $('#cam').click(function(){
	var status = muteUnmuteCam();
	if(status==false){
		$('#cam').attr("src","img/cam-on.png");
	}
	else {
		$('#cam').attr("src","img/cam-off.png");
	}
   });
   $('#mic').click(function(){
	var status = muteUnmuteMic();
	if(status==false){
		$('#mic').attr("src","img/mic-on.png");
	}
	else {
		$('#mic').attr("src","img/mic-off.png");
	}
   });
   $('#fullscreen').click(function(){
	console.log(parseInt($("#videobox").css("left")));
	if(parseInt($("#videobox").css("left"))==0){
   		closeFullscreen(document.getElementById("videobox"));
	}
	else{
   		openFullscreen(document.getElementById("videobox"));
	}
   });

   $('#screenshare').click(function(){
	screenshare();
   });

});
function openFullscreen(elem) {
  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.mozRequestFullScreen) { /* Firefox */
    elem.mozRequestFullScreen();
  } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
    elem.webkitRequestFullscreen();
  } else if (elem.msRequestFullscreen) { /* IE/Edge */
    elem.msRequestFullscreen();
  }
}

function closeFullscreen(){
	if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
}


var expanded = false;

function showCheckboxes() {
  var checkboxes = document.getElementById("checkboxes");
  if (!expanded) {
    checkboxes.style.display = "block";
    expanded = true;
  } else {
    checkboxes.style.display = "none";
    expanded = false;
  }
}

function changeLayoutForVideo(){
	if($('#videobox').css("visibility")=="hidden"){
		$('#chatbox').animate({left: '60%',width: '300px'}, 2000);
		$('#infobox').animate({
    			left: '85%'}, {
    			duration: 2000,
    			complete: function() { 
				$("#videobox").css({visibility:"visible", opacity: 0.0}).animate({opacity: 1.0},500); 
			}
		});
	}
}

function changeLayoutForMessaging(){
	if($('#videobox').css("visibility") != "hidden"){
		$('#videobox').css({visibility:"visible",opacity:1.0}).animate({
    			opacity: 0.0}, {
    			duration: 500,
    			complete: function() { 
				$('#chatbox').animate({left: '22%',width: '600px'}, 2000);
				$('#infobox').animate({left: '60%'}, 2000);
				$("#videobox").css("visibility","hidden");
			}
		});
	}
}

$(document).mouseup(function(e) 
{
         var container = $("#checkboxes");

         if (!container.is(e.target) && container.has(e.target).length === 0) 
         {
               container.hide();
		expanded = false;
	       var skills = getSelectedSkills();
		//if(skills != lastselectedskills ){
			lastselectedskills = skills;
	       		changeAgentSkills(agentName,skills);
		//}
         }
});

$("#videobox").mousemove(function(e)
{
	if(channel != ""){
		$("#controls").css("visibility","visible");
	}
});

$("#videobox").mouseout(function(e)
{
	$("#controls").css("visibility","hidden");
});
function getSelectedSkills(){
	var skills="";
	if(document.getElementById("whatsapp").checked)
		skills += "whatsapp,";
	
	if(document.getElementById("messenger").checked)
		skills += "messenger,";	

	if(document.getElementById("video").checked)
		skills += "video,";	

	if(document.getElementById("voice").checked)
		skills += "voice,";	
	return skills;
}

function agentStatusChanged(){
	var agentstatus = document.getElementById("status").value;
	if(agentstatus == "online"){
		agentName = prompt("Please enter the agent name to login");
		alert("NOTE: Remember to set the Agent status to Offline, before closing the window");
		changeAgentStatus(agentName,"online","");
  		pollTimer = setInterval(function(){getWAMessages()},5000);
		document.getElementById("customername").innerHTML="Waiting for customer...";
		$("#channels").css("visibility","visible");
	}
	else{
		setAgentOffline();
		$("#channels").css("visibility","hidden");
		$('input:checkbox:checked').prop('checked', false);
	}
}

function resetAgent(){
	if(channel == "video"){
		endTokSession();
	}
	sessionStarted =false;
	if(agentName != ""){
		changeAgentStatus(agentName,"agentreset",customerName);
	}
	$('.mCSB_container').html("");
	document.getElementById("customerinfo").style.visibility="hidden";
	clearInterval(pollTimer);
  	pollTimer = setInterval(function(){getWAMessages()},5000);
	lastIndex=1;
	document.getElementById("customername").innerHTML="Waiting for customer...";
	document.getElementById("customertype").innerHTML="";
	var msg="Your session with "+ agentName + " has Ended.";
  	if(channel =="whatsapp"){  
		sendwa(msg);
  	}else if(channel == "messenger"){
		sendfb(msg);
 	}
	channel="";
}

function setAgentOffline(){
	sessionStarted =false;
	channel="";
	if(agentName != ""){
		changeAgentStatus(agentName,"offline","");
	}
	$('.mCSB_container').html("");
	document.getElementById("customerinfo").style.visibility="hidden";
	clearInterval(pollTimer);
	document.getElementById("status").value="offline";
	lastIndex=1;
	document.getElementById("customername").innerHTML="";
	document.getElementById("customertype").innerHTML="";
}

function changeAgentStatus(agent,agentstatus,customerName){

	var url= "router.php?cmd="+agentstatus+"&agent="+agentName+"&customer="+customerName+"&taskid="+taskid;
	$.get(url, function(data, status){
		console.log(data);
	});
}

function changeAgentSkills(agent,skills){
	var url= "router.php?cmd=changeskills&agent="+agentName+"&skills="+skills;
	$.get(url, function(data, status){
		console.log(data);
	});
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}
function getWAMessages(){
	var url= "get_next_message.php?agent="+agentName;
	$.get(url, function(data, status){
		 if(data == "None")
			return;
   		 var lines = data.split("\n");
		 var linecount = lines.length - 1;	 
		 console.log("lines " + linecount);
		 if(linecount < lastIndex)
			return;
                 for(var i=lastIndex; i <= linecount; i++){
                         if(lines[i-1].trim() == "")
                                 continue;
	    		 $('.message.loading').remove();
			
			var parts = lines[i-1].split("__SP__");
			
		 	if(sessionStarted == false){
				channel = parts[0];
				taskid = parts[1];
				var channellabel ="";
				if(channel=="whatsapp"){
					customerName=parts[2];
					channellabel = "WhatsApp";
				}else if(channel=="messenger"){
					channellabel = "FB Messenger";
					customerName=parts[2];
				}
				else if(channel=="video"){
					channellabel = "Video";
					customerName = parts[2];
				}
				//changeAgentStatus(agentName,"busy",customerName);
				document.getElementById("customerinfo").style.visibility="visible";
				document.getElementById("channel").innerHTML=channellabel;;
				document.getElementById("customername").innerHTML="Mr.Jason Bourne";
				document.getElementById("customertype").innerHTML="Wealth customer";
				sessionStarted = true;
		 	}
		
			if(channel == "video"){
				showVideoCallDialog(parts[3]);	
			}
			else {
				changeLayoutForMessaging();		
				var imsg = parts[3];
				if(imsg.startsWith("__LOC__")){
					imsg = imsg.replace("__LOC__","");
					var coordinates = imsg.split(",");
    					$('<div class="message new"><figure class="avatar"><img src="customer.png" /></figure><div class="mapouter"><div class="gmap_canvas"><iframe width="300" height="200" id="gmap_canvas" src="https://maps.google.com/maps?q='+coordinates[0]+','+coordinates[1]+'&t=&z=13&ie=UTF8&iwloc=&output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe><a href="https://www.pureblack.de"></a></div><style>.mapouter{text-align:right;height:200px;width:300px;}.gmap_canvas {overflow:hidden;background:none!important;height:200px;width:300px;}</style></div></div>').appendTo($('.mCSB_container')).addClass('new');

				}
				else{
    					$('<div class="message new"><figure class="avatar"><img src="customer.png" /></figure>' +parts[3] + '</div>').appendTo($('.mCSB_container')).addClass('new');
				}	
    				setDate();
    				updateScrollbar();
			}
			console.log(lines[i-1]);
		}
		lastIndex = linecount+1;		
    	});

}

function showVideoCallDialog(sessioninfo){
		var parts = sessioninfo.split("|");
		var sessionid = parts[0];
		var apikey = parts[1];
		$("#dialog").dialog({
                         title: "Incoming Call",
                         buttons: {
                             Accept: function() {
				changeLayoutForVideo();
				getTokSessionInfo(sessionid);
				$(this).dialog("close");
                             }

                         },
			closeOnEscape: false,
    			open: function(event, ui) {
        			$(".ui-dialog-titlebar-close", ui.dialog | ui).hide();
    			}
                     });

		$( "#dialog" ).dialog( "open" );
                     return false;
}

function updateScrollbar() {
  $messages.mCustomScrollbar("update").mCustomScrollbar('scrollTo', 'bottom', {
    scrollInertia: 10,
    timeout: 0
  });
}

function setDate(){
  d = new Date()
  if (m != d.getMinutes()) {
    m = d.getMinutes();
    $('<div class="timestamp">' + d.getHours() + ':' + m + '</div>').appendTo($('.message:last'));
  }
}

function insertMessage() {
  msg = $('.message-input').val();
  if ($.trim(msg) == '') {
    return false;
  }
  $('<div class="message message-personal">' + msg + '</div>').appendTo($('.mCSB_container')).addClass('new');
  setDate();
  $('.message-input').val(null);
  updateScrollbar();
  if(channel =="whatsapp"){  
	sendwa(msg);
  }else{
	sendfb(msg);
 }
  /*setTimeout(function() {
    fakeMessage();
  }, 1000 + (Math.random() * 20) * 100);*/
}
function sendwa(message){
	 $.post("sendwa.php",
        {
                from: "44xxxxxxxx",
                to: customerName,
		four: message,
		type: "normal"
        },
        function(data, status){
                console.log(data);
                var json = JSON.parse(data);
        });

}
function sendfb(message){

	console.log("message=" + message + "\n");

	 $.post("sendfb.php",
        {
                from: "301365433815713",
                to: customerName,
		four: message,
		type: "normal"
        },
        function(data, status){
                console.log("sendfb : " + data + "\n");
                var json = JSON.parse(data);
        });

}

$('.message-submit').click(function() {
  insertMessage();
});

$(window).on('keydown', function(e) {
  if (e.which == 13) {
    insertMessage();
    return false;
  }
})

