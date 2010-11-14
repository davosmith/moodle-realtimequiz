function highlight_correct() {
    var radiobtns = YAHOO.util.Dom.getElementsByClassName('realtimequiz_answerradio');
    for (var i in radiobtns) {
	if (radiobtns[i].checked) {
	    YAHOO.util.Dom.addClass(radiobtns[i].parentNode, 'realtimequiz_highlight_correct');
	} else {
	    YAHOO.util.Dom.removeClass(radiobtns[i].parentNode, 'realtimequiz_highlight_correct');
	}
    }
}

function add_answer() {
    var firstanswer = YAHOO.util.Dom.get('realtimequiz_first_answer');
    var newanswer = firstanswer.cloneNode(true);

    var answercount = firstanswer.parentNode.getElementsByTagName('tr').length - 4;
    var answernum = answercount + 1;

    newanswer.id = '';
    var td1 = newanswer.firstChild;
    var label = td1.firstChild;
    label.setAttribute('for', 'realtimequiz_answerradio'+answernum);
    label.innerHTML = label.innerHTML.replace('1',answernum);
    var td2 = td1.nextSibling;
    var radio = td2.firstChild;
    radio.checked = false;
    radio.value = answernum;
    radio.id = radio.id.replace('1',answernum);
    var textbox = radio.nextSibling;
    textbox.setAttribute('name', textbox.getAttribute('name').replace('1',answernum));
    textbox.value = '';
    var answerid = textbox.nextSibling;
    answerid.setAttribute('name', answerid.getAttribute('name').replace('1',answernum));
    answerid.value = 0;
    
    firstanswer.parentNode.appendChild(newanswer);
}

function add_answers(num) {
    for (var i = 1; i<=num; i++) {
	add_answer();
    }

    highlight_correct();
}