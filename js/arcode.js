jQuery(document).ready(function($){	
	
	$('#ar_code').on('change keyup', function() {
				change_selects();
			
		  return false;
		});
		
		jQuery.expr[":"].Contains = jQuery.expr.createPseudo(function(arg) {
			return function( elem ) {
				return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
			};
		});
		
		function change_selects(){
				var tags = ['a','iframe','frame','frameset','script', 'div'], reg, val = $('#ar_code').val(),
					hdn = $('#arcode_hdn_div2'), formurl = $('#ar_url'), hiddenfields = $('#ar_hidden');
			    formurl.val('');
				if(jQuery.trim(val) == '')
					return false;
				$('#arcode_hdn_div').html('');
				$('#arcode_hdn_div2').html('');
				for(var i=0;i<5;i++){
					reg = new RegExp('<'+tags[i]+'([^<>+]*[^\/])>.*?</'+tags[i]+'>', "gi");
					val = val.replace(reg,'');
					
					reg = new RegExp('<'+tags[i]+'([^<>+]*)>', "gi");
					val = val.replace(reg,'');
				}
				var tmpval;
				try {
					tmpval = decodeURIComponent(val);
				} catch(err){
					tmpval = val;
				}
				hdn.append(tmpval);
				
				var tempUrl = jQuery('form',hdn).attr('action');
				if (tempUrl.substring(0, 2) == "//") {
					tempUrl = 'http:' + tempUrl;
				} else if (tempUrl.substring(0, 4) != 'http') {
					tempUrl = 'http://' + tempUrl;
				}
				formurl.val(tempUrl);
				
				var name_selected = '';
				var email_selected = '';
				jQuery(':input[type="text"],:input[type="email"]', hdn).each(function() {
					if (jQuery(this).attr('name').toLowerCase().indexOf("name") >= 0) {
						if (name_selected == '') {
							name_selected = jQuery(this).attr('name');
							jQuery('#ar_name').val(name_selected);
						}
					}
					if (jQuery(this).attr('name').toLowerCase().indexOf("mail") >= 0) {
						if (email_selected == '') {
							email_selected = jQuery(this).attr('name');
							jQuery('#ar_email').val(email_selected);
						}
					}
					return true;
				});
				
				jQuery(':input[type=text],:input[type=hidden]',hdn).each(function(){
					if ((jQuery(this).attr('name') != name_selected) && (jQuery(this).attr('name') != email_selected)) {
						jQuery('#arcode_hdn_div').append(jQuery('<input type="hidden" name="'+jQuery(this).attr('name')+'" />').val(jQuery(this).val()));
					}
				});
				var hidden_f = jQuery('#arcode_hdn_div').html();
				hiddenfields.val(hidden_f);
				hdn.html('');
			};
}); 
