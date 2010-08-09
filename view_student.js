/**
 * Code for a student taking the quiz
 *
 * @author: Davosmith
 * @package realtimequiz
 **/

// Set up the variables used throughout the javascript
var realtimequiz=new Object();
realtimequiz.givenanswer=false;
realtimequiz.timeleft=-1;
realtimequiz.timer=null;
realtimequiz.questionnumber=-1;
realtimequiz.answernumber=-1;
realtimequiz.questionxml=null;
realtimequiz.controlquiz = false;
realtimequiz.lastrequest = '';
realtimequiz.sesskey=-1;
realtimequiz.coursepage='';
realtimequiz.siteroot='';
realtimequiz.myscore=0;
realtimequiz.myanswer=-1;
realtimequiz.resendtimer = null;

realtimequiz.text = new Array();

/**************************************************
* Debugging
**************************************************/
var realtimequiz_maxdebugmessages = 0;

function realtimequiz_debugmessage(message) {
    if (realtimequiz_maxdebugmessages > 0) {
        realtimequiz_maxdebugmessages -= 1;

        var dbarea = document.getElementById('debugarea');
        dbarea.innerHTML += message + '<br />';
    }
}


/**************************************************
* Some values that need to be passed in to the javascript
**************************************************/

function realtimequiz_set_maxanswers(number) {
    realtimequiz.maxanswers = number;
}

function realtimequiz_set_quizid(id) {
    realtimequiz.quizid = id;
}

function realtimequiz_set_userid(id) {
    realtimequiz.userid = id;
}

function realtimequiz_set_sesskey(key) {
    realtimequiz.sesskey = key;
}

function realtimequiz_set_text(name, value) {
    realtimequiz.text[name] = value;
}

function realtimequiz_set_coursepage(url) {
    realtimequiz.coursepage = url;
}

function realtimequiz_set_siteroot(url) {
    realtimequiz.siteroot = url;
}

/**************************************************
* Set up the basic layout of the student view
**************************************************/
function realtimequiz_init_student_view() {
    var msg="<center><input type='button' onclick='realtimequiz_join_quiz();' value='"+realtimequiz.text['joinquiz']+"' />"
    msg += "<p id='status'>"+realtimequiz.text['joininstruct']+"</p></center>"
    document.getElementById('questionarea').innerHTML = msg;
    
    realtimequiz_debugmessage("Starting student view...");
}

function realtimequiz_init_question_view() {
    if (realtimequiz.controlquiz) {
        document.getElementById("questionarea").innerHTML = "<h1><span id='questionnumber'>"+realtimequiz.text['waitstudent']+"</span></h1><div id='questionimage'></div><div id='questiontext'>"+realtimequiz.text['clicknext']+"</div><ul id='answers'></ul><p><span id='status'></span> <span id='timeleft'></span></p>";
        document.getElementById("questionarea").innerHTML += "<div id='questioncontrols'></div><br style='clear: both;' />";
        realtimequiz_update_next_button(true);
    } else {
        document.getElementById("questionarea").innerHTML = "<h1><span id='questionnumber'>"+realtimequiz.text['waitfirst']+"</span></h1><div id='questionimage'></div><div id='questiontext'></div><ul id='answers'></ul><p><span id='status'></span> <span id='timeleft'></span></p><br style='clear: both;' />";
        realtimequiz_get_question();
		realtimequiz.myscore = 0;
    }
    
    
}

/**************************************************
* Functions to display information on the screen
**************************************************/
function realtimequiz_set_status(status) {
    document.getElementById('status').innerHTML = status;
}

function realtimequiz_set_question_number(num, total) {
    document.getElementById('questionnumber').innerHTML = realtimequiz.text['question'] + num + ' / ' + total;
    realtimequiz.questionnumber = num;
}

function realtimequiz_set_question_text(text) {
    document.getElementById('questiontext').innerHTML = text.replace(/\n/g, '<br />');
}

function realtimequiz_set_question_image(url, width, height) {
    if (url) {
        document.getElementById('questionimage').innerHTML = '<image style="border: 1px solid black; float: right;" src="'+url+'" height="'+height+'px" width="'+width+'px" />';
    } else {
        document.getElementById('questionimage').innerHTML = '';
    }
}

function realtimequiz_clear_answers() {
    document.getElementById('answers').innerHTML = '';
    realtimequiz.answernumber = 0;
}

function realtimequiz_set_answer(id, text) {
    if (realtimequiz.answernumber > realtimequiz.maxanswers || realtimequiz.answernumber < 0) {
        alert(realtimequiz.text['invalidanswer'] + realtimequiz.answernumber + ' - ' + text);
    }

    var letter = String.fromCharCode(65 + realtimequiz.answernumber);        //ASCII 'A'
    var newanswer = '<li id="answer'+id+'"><input '
    if (realtimequiz.controlquiz) {
        newanswer += 'disabled=disabled ';
    }
    newanswer += 'type="button" OnClick="realtimequiz_select_choice('+id+');"';
    newanswer += ' value="&nbsp;&nbsp;'+letter+'&nbsp;&nbsp;" />&nbsp;&nbsp;';
    newanswer += text + '<span class="result"><img src="blank.gif" height="19" /></span><br /></li>';

    document.getElementById('answers').innerHTML += newanswer;
    realtimequiz.answernumber += 1;
}

function realtimequiz_set_question() {
    if (realtimequiz.questionxml == null) {
        alert('realtimequiz.questionxml is null');
        return;
    }
	var qnum = node_text(realtimequiz.questionxml.getElementsByTagName('questionnumber').item(0));
	var total = node_text(realtimequiz.questionxml.getElementsByTagName('questioncount').item(0));
    realtimequiz_set_question_number(qnum, total);
    realtimequiz_set_question_text(node_text(realtimequiz.questionxml.getElementsByTagName('questiontext').item(0)));
    var image = realtimequiz.questionxml.getElementsByTagName('imageurl');
    if (image.length) {
        image = node_text(image.item(0));
        var imagewidth = node_text(realtimequiz.questionxml.getElementsByTagName('imagewidth').item(0));
        var imageheight = node_text(realtimequiz.questionxml.getElementsByTagName('imageheight').item(0));
        realtimequiz_set_question_image(image, imagewidth, imageheight);
    } else {
        realtimequiz_set_question_image(false, 0, 0);
    }

    var answers = realtimequiz.questionxml.getElementsByTagName('answer');
    realtimequiz_clear_answers();
    for (var i=0; i<answers.length; i++) {
        realtimequiz_set_answer(parseInt(answers[i].getAttribute('id')), node_text(answers[i]));
    }
    realtimequiz.givenanswer = false;
	realtimequiz.myanswer = -1;
    realtimequiz_start_timer(parseInt(node_text(realtimequiz.questionxml.getElementsByTagName('questiontime').item(0))), false);
}

function realtimequiz_disable_answer(answerel) {
    answerel.innerHTML = answerel.innerHTML.replace(/<input /i,'<input disabled=disabled ');
}

function realtimequiz_set_result(answerid, correct, count) {
    var anscontainer = document.getElementById('answer'+answerid);
    if (anscontainer) {
        var ansimage = anscontainer.getElementsByTagName('span');
        for (var i=0; i<ansimage.length; i++) {
            if (ansimage[i].className == 'result') {
                var result = "&nbsp;&nbsp;<img src='";
                if (correct) {
                    result += "tick.gif' alt='tick'";
                } else {
                    result += "cross.gif' alt='cross'";
                }
                result += " height='19' />&nbsp;&nbsp; " + count;
                ansimage[i].innerHTML = result;
                break;
            }
        }
    }
}

function realtimequiz_show_final_results(quizresponse) {
    var classresult = node_text(quizresponse.getElementsByTagName('classresult').item(0));
    var msg = '<h1>'+realtimequiz.text['finalresults']+'</h1>';
    msg += '<p>'+realtimequiz.text['classresult']+classresult+'%'+realtimequiz.text['resultcorrect'];
	if (!realtimequiz.controlquiz) {
		msg += '<br><strong>'+realtimequiz.text['yourresult']+parseInt((realtimequiz.myscore * 100)/realtimequiz.questionnumber);
		msg += '%'+realtimequiz.text['resultcorrect']+'</strong>';
	}
	msg += '</p>';
    document.getElementById('questionarea').innerHTML = msg;
}

/**************************************************
* handle clicking on an answer
**************************************************/
function realtimequiz_select_choice(choice) {
    if (!realtimequiz.givenanswer) {
        realtimequiz_set_status('Sending answer:');
        realtimequiz.givenanswer=true;
		realtimequiz.myanswer = choice;
        var answers = document.getElementById('answers').getElementsByTagName('li');
        var chosenid = 'answer'+choice;
        for (var ans=0; ans<answers.length; ans++) {
            if (chosenid != answers[ans].id) {
                realtimequiz_disable_answer(answers[ans]);
            }
        }
        realtimequiz_post_answer(choice);
    } 
}

/**************************************************
* Functions to manage the on-screen timer
**************************************************/
function realtimequiz_start_timer(counttime, preview) {
    realtimequiz_stop_timer();
    if (preview) {
        realtimequiz_set_status('About to display next question:');
    } else {
        realtimequiz_set_status('Time left to answer:');
    }
    realtimequiz.timeleft=counttime+1;
    realtimequiz.timer=setInterval("realtimequiz_timer_tick("+preview+")", 1000);
    realtimequiz_timer_tick();
}

function realtimequiz_stop_timer() {
    if (realtimequiz.timer != null) {
        clearInterval(realtimequiz.timer);
        realtimequiz.timer = null;
    }
}

function realtimequiz_timer_tick(preview) {
    realtimequiz.timeleft--;
    if (realtimequiz.timeleft <= 0) {
        realtimequiz_stop_timer();
        realtimequiz.timeleft=0;
        if (preview) {
            realtimequiz_set_question();		
        } else {
            realtimequiz_set_status(realtimequiz.text['questionfinished']);
            document.getElementById('timeleft').innerHTML = "";
            if (!realtimequiz.givenanswer) {
                var answers = document.getElementById('answers').getElementsByTagName('li');
                for (var ans=0; ans<answers.length; ans++) {
                    realtimequiz_disable_answer(answers[ans]);
                }
            }
            setTimeout("realtimequiz_get_results()", 400);
        }
    } else {
        document.getElementById('timeleft').innerHTML = realtimequiz.timeleft;
    }
}

/**************************************************
* Functions to communicate with server
**************************************************/
function realtimequiz_create_request(parameters) {
    // Create and send an XMLHttpRequest to the server
    
    // Sending a new request, so forget about resending an old request	
    if (realtimequiz.resendtimer != null) {
        clearTimeout(realtimequiz.resendtimer);
        realtimequiz.resendtimer = null;
    }
	
	realtimequiz.lastrequest = parameters;
   
    var httpRequest;

    if (window.XMLHttpRequest) { // Mozilla, Safari, ...
        httpRequest = new XMLHttpRequest();
        if (httpRequest.overrideMimeType) {
            httpRequest.overrideMimeType('text/xml');
        }
    } else if (window.ActiveXObject) { // IE
        try {
            httpRequest = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (e) {
            try {
                httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch (e) {
            }
        }
    }

    if (!httpRequest) {
        alert(realtimequiz.text['httprequestfail']);
        return false;
    }
    httpRequest.onreadystatechange = function() { 
        realtimequiz_process_contents(httpRequest); 
    };
    httpRequest.open('POST', realtimequiz.siteroot+'/mod/realtimequiz/quizdata.php', true);
    httpRequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    httpRequest.setRequestHeader('Content-length', parameters.length);
    httpRequest.setRequestHeader('Connection', 'close');
    //    httpRequest.overrideMimeType('text/xml');
    httpRequest.send(parameters+'&sesskey='+realtimequiz.sesskey);

   // Resend the request if nothing back from the server within 2 seconds
   realtimequiz.resendtimer = setTimeout("realtimequiz_resend_request()", 2000);
}

function realtimequiz_resend_request() { // Only needed if something went wrong
	realtimequiz_create_request(realtimequiz.lastrequest);
}

function realtimequiz_return_course() { // Go back to the course screen if something went wrong
	if (realtimequiz.coursepage == '') {
        alert('realtimequiz.coursepage not set');
    } else { 
        //window.location = realtimequiz.coursepage;
    }
}

function node_text(node) { // Cross-browser - extract text from XML node
    var text = node.textContent;
    if (text != undefined) {
        return text;
    } else {
        return node.text;
    }
}

// Various requests that can be sent to the server
function realtimequiz_get_question() {
    realtimequiz_create_request('requesttype=getquestion&quizid='+realtimequiz.quizid);
}

function realtimequiz_get_results() {
    realtimequiz_create_request('requesttype=getresults&quizid='+realtimequiz.quizid+'&question='+realtimequiz.questionnumber);
}

function realtimequiz_post_answer(ans) {
    realtimequiz_create_request('requesttype=postanswer&quizid='+realtimequiz.quizid+'&question='+realtimequiz.questionnumber+'&userid='+realtimequiz.userid+'&answer='+ans);
}

function realtimequiz_join_quiz() {
    realtimequiz_create_request('requesttype=quizrunning&quizid='+realtimequiz.quizid+'');
}

// Process the server's response
function realtimequiz_process_contents(httpRequest) {
    if (httpRequest.readyState == 4) {   
        // We've heard back from the server, so do not need to resend the request 
        if (realtimequiz.resendtimer != null) {
            clearTimeout(realtimequiz.resendtimer);
            realtimequiz.resendtimer = null;
        }
        if (httpRequest.status == 200) {
            var quizresponse = httpRequest.responseXML.getElementsByTagName('realtimequiz').item(0);
            var quizstatus = node_text(quizresponse.getElementsByTagName('status').item(0));
            if (quizstatus == 'showquestion') {
                realtimequiz.questionxml = quizresponse.getElementsByTagName('question').item(0);
                if (!realtimequiz.questionxml) {
                    alert(realtimequiz.text['noquestion']+httpRequest.responseHTML);
					if (confirm(realtimequiz.text['tryagain'])) {
						realtimequiz_resend_request();
					} else {
						realtimequiz_return_course();
					}
                } else {
                    var delay = realtimequiz.questionxml.getElementsByTagName('delay').item(0);
                    if (delay) {
                        realtimequiz_start_timer(parseInt(node_text(delay)), true);
                    } else {
                        realtimequiz_set_question();
                    }
                }
                
            } else if (quizstatus == 'showresults') {
                //bob += 5;
                var results = quizresponse.getElementsByTagName('result');
                for (var i=0; i<results.length; i++) {
                    var iscorrect = (results[i].getAttribute('correct') == 'true');
					var answerid = parseInt(results[i].getAttribute('id'));
                    realtimequiz_set_result(answerid, iscorrect, parseInt(node_text(results[i])));
					if (iscorrect && (realtimequiz.myanswer == answerid)) {
						realtimequiz.myscore++;
					}
                }
                var statistics = quizresponse.getElementsByTagName('statistics').item(0);
                var status = realtimequiz.text['resultthisquestion']+'<strong>';
                status += node_text(statistics.getElementsByTagName('questionresult').item(0));
                status += '%</strong>'+realtimequiz.text['resultoverall'];
                status += node_text(statistics.getElementsByTagName('classresult').item(0));
                status += '%'+realtimequiz.text['resultcorrect'];
				if (!realtimequiz.controlquiz) {
					status += '<strong> '+realtimequiz.text['yourresult']+parseInt((realtimequiz.myscore * 100) / realtimequiz.questionnumber);
					status += '%'+realtimequiz.text['resultcorrect']+'</strong>'; 
				}
                realtimequiz_set_status(status);

                if (realtimequiz.controlquiz) {
                    realtimequiz_update_next_button(true);  // Teacher controls when to display the next question
                } else {
                    setTimeout("realtimequiz_get_question()",900); // Wait for next question to be displayed
                }
                
            } else if (quizstatus == 'answerreceived') {
                if (realtimequiz.timeleft > 0) {
                    realtimequiz_set_status(realtimequiz.text['answersent']);
                }
                
            } else if (quizstatus == 'waitforquestion') {
                var waittime = quizresponse.getElementsByTagName('waittime').item(0);
                if (waittime) {
                    waittime = parseFloat(node_text(waittime)) * 1000;
                } else {
                    waittime = 600;
                }

                setTimeout("realtimequiz_get_question()", waittime);
                
            } else if (quizstatus == 'waitforresults') {
                var waittime = quizresponse.getElementsByTagName('waittime').item(0);
                if (waittime) {
                    waittime = parseFloat(node_text(waittime)) * 1000;
                } else { 
                    waittime = 1000;
                }               

                setTimeout("realtimequiz_get_results()", waittime);
                
            } else if (quizstatus == 'quizrunning') {
                realtimequiz_init_question_view();
                
            } else if (quizstatus == 'quiznotrunning') {
                realtimequiz_set_status(realtimequiz.text['quiznotrunning']);
                
            } else if (quizstatus == 'finalresults') {
                realtimequiz_show_final_results(quizresponse);
                
            } else if (quizstatus == 'error') {
                var errmsg = node_text(quizresponse.getElementsByTagName('message').item(0));
                alert(realtimequiz.text['servererror']+errmsg);
                
            } else {
                alert(realtimequiz.text['badresponse']+httpRequest.responseText);
				if (confirm(realtimequiz.text['tryagain'])) {
					realtimequiz_resend_request();
				} else {
					realtimequiz_return_course();
				}
            }
        } else {
            // Server responded with anything other than OK
            alert(realtimequiz.text['httperror']+httpRequest.status);
			if (confirm(realtimequiz.text['tryagain'])) {
				realtimequiz_resend_request();
			} else {
				realtimequiz_return_course();
			}
		}
    }
}

