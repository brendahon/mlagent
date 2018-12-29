
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
	var session = OT.initSession(apikey, sessionid);

	session.on('streamCreated', function(event) {
  		session.subscribe(event.stream, 'subscriber', {
    			insertMode: 'append',
    			width: '100%',
    			height: '100%'
  		}, handleError);
	});

	var publisher = OT.initPublisher('publisher', {
    		insertMode: 'append',
    		width: '100%',
    		height: '100%'
  		}, handleError);

	session.connect(token, function(error) {
		if (error) {
      			handleError(error);
    		} else {
      			session.publish(publisher, handleError);
    		}
  	});
}
