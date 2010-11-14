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