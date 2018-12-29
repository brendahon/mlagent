var OTSession = undefined;
var publisher = undefined;
var isCamMuted = false;
var isMicMuted = false;
function handleError(error) {
  if (error) {
    alert(error.message);
  }
}

function getTokSessionInfo(sessionid){
	$.get("/opentok/token.php?session="+sessionid, function(data, status){
		console.log(data);
		var json = data;
		console.log(json.apiKey+" "+json.sessionId+" "+json.token);
		initializeSession(json.apiKey,json.sessionId,json.token);	
	});
}

function initializeSession(apikey,sessionid,token) {
	OTSession = OT.initSession(apikey, sessionid);

	OTSession.on('streamCreated', function(event) {
  		OTSession.subscribe(event.stream, 'subscriber', {
    			insertMode: 'append',
    			width: '100%',
    			height: '100%'
  		}, handleError);
	});
	
	OTSession.on('streamDestroyed', function(event) {
		$("#controls").css("visibility","hidden");
		//endTokSession();
		resetAgent();
	});

	publisher = OT.initPublisher('publisher', {
    		insertMode: 'append',
    		width: '100%',
    		height: '100%'
  		}, handleError);

	OTSession.connect(token, function(error) {
		if (error) {
      			handleError(error);
    		} else {
      			OTSession.publish(publisher, handleError);
    		}
  	});

    OT.registerScreenSharingExtension('chrome', 'nkhdompaojjdmfdinnaegnlpmndnkofk', 2);

}

function screenshare() {
      OT.checkScreenSharingCapability(function(response) {
        if (!response.supported || response.extensionRegistered === false) {
          alert('This browser does not support screen sharing.');
        //} else if (response.extensionInstalled === false) {
          //alert('Please install the screen sharing extension and load your app over https.');
        } else {
          
          var screenSharingPublisher = OT.initPublisher('screen-preview', {videoSource: 'screen'});
          OTSession.publish(screenSharingPublisher, function(error) {
            if (error) {
              alert('Could not share the screen: ' + error.message);
            }
          });
        }
      });
}

function endTokSession(){
	$("#controls").css("visibility","hidden");
	OTSession.disconnect();
}

function muteUnmuteCam(){
	if(isCamMuted){
		isCamMuted = false;
		publisher.publishVideo(true);
	}
	else {
		isCamMuted = true;
		publisher.publishVideo(false);
	}
	return isCamMuted;
}

function muteUnmuteMic(){
	if(isMicMuted){
		isMicMuted = false;
		publisher.publishAudio(true);
	}
	else {
		isMicMuted = true;
		publisher.publishAudio(false);
	}
	return isMicMuted;
}
