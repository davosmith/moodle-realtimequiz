YUI().use('node','event', function(Y) {
    M.mod_realtimequiz = {
        lastradio: null,

        highlight_correct: function() {
            Y.all('.realtimequiz_answerradio').each(function(radiobtn) {
                if (radiobtn.get('checked')) {
                    var textbox = radiobtn.next();
                    if (textbox && textbox.get('value') == '' && this.lastradio) {
                        var lastradio = Y.one('#'+this.lastradio);
                        if (lastradio) {
                            lastradio.set('checked', true);
                            lastradio.get('parentNode').addClass('realtimequiz_highlight_correct');
                        }
                    } else {
                        radiobtn.get('parentNode').addClass('realtimequiz_highlight_correct');
                        this.lastradio = radiobtn.get('id');
                    }
                } else {
                    radiobtn.get('parentNode').removeClass('realtimequiz_highlight_correct');
                }
            }, this);
        },

        add_answer: function () {
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
        },

        add_answers: function (num) {
            for (var i = 1; i<=num; i++) {
                this.add_answer();
            }

            this.highlight_correct();
        },

        init_editpage: function() {
            Y.all('.realtimequiz_answerradio').on('click', this.highlight_correct, this);

            this.highlight_correct();
            /*
             var addbtn = YAHOO.util.Element('addanswers');
             addbtn.addListener('click', function() {
             YAHOO.util.Event.preventDefault();
             this.add_answers(3);
             }, false, this);*/
        }
    }
});