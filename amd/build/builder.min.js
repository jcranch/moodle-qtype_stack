define([],function() {
    var builder = {};

    builder.builders = [];
    
    builder.init = function(n) {
	var B = {};
	B.name = n;
	B.phrases = [];
	B.ans_box = document.getElementById(n + '_raw');
	B.used_ol = document.getElementById(n + '_used_ol');
	B.used_div = document.getElementById(n + '_used_div');
	B.unused_ol = document.getElementById(n + '_unused_ol');
	B.unused_div = document.getElementById(n + '_unused_div');

	var i = 1;
	var p = null;
	while (p = document.getElementById(n + '_' + i)) {
	    p.index = i;
	    p.is_used = (p.parentNode == B.used_ol);
	    B.phrases.push(p);
	    this.set_click_handler(p,n);
	    i++;
	}

	this.builders[n] = B;
    };

    builder.set_click_handler = function(p,n) {
	var me = this;
	p.onclick = function() { me.toggle_phrase(p,n); }
    };
    
    builder.squash = function(n) {
	var B = this.builders[n];
	var C = B.used_ol.children;
	var i;
	var s = '[';
	var comma = '';
    
	for (i = 0; i < C.length; i++) {
	    var p = C[i];
	    if (p.nodeName.toLowerCase() == 'li' &&
		p.className == 'builder_phrase') {
		s += comma + p.index;
		comma = ',';
	    }
	}

	s += ']';
	if (s == '[]') { s = ''; }
	B.ans_box.value = s;
    };

    builder.toggle_phrase = function(p,n) {
	var B = this.builders[n];

	if (p.parentElement == B.used_ol) {
	    B.used_ol.removeChild(p);
	    B.unused_ol.appendChild(p);
	    p.is_used = false;
	} else {
	    B.unused_ol.removeChild(p);
	    B.used_ol.appendChild(p);
	    p.is_used = true;
	}
	
	this.squash(n);
    };

    return(builder);
});

