window.jQuery || document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js">\x3C/script>');
jQuery(function() {
	
	var refwidth = 210;
	
	jQuery('div.agendaEvent .moreInfo').bind('click', function() {
		jQuery(this).parent().animate({'marginLeft':-refwidth}, 'fast').siblings('.agendaEventDatetime').animate({'marginLeft':'0px'}, 'fast');
	});
	
	jQuery('div.agendaEvent .lessInfo').bind('click', function() {
		jQuery(this).parent().animate({'marginLeft':refwidth}, 'fast').siblings('.agendaEventDesc').animate({'marginLeft':'0px'}, 'fast');
	});

});