jQuery( document ).ready(function() {
    hide_logic(jQuery('select[name=listbuilder-plugin-active]').val());

    jQuery('select[name=listbuilder-plugin-active]').on('change', function() {
    	var elem = jQuery(this).val();
    	hide_logic(elem);
    });

    function hide_logic(value) {
    	if(value == 'klicktipp') {
    		jQuery('.klicktipp').show();
            jQuery('.general_settings').show();
    		jQuery('.autoresponder').hide();
    	} else if(value == 'autoresponder') {
    		jQuery('.autoresponder').show();
            jQuery('.general_settings').show();
    		jQuery('.klicktipp').hide();
    	} else {
    		jQuery('.autoresponder').hide();
    		jQuery('.klicktipp').hide();
            jQuery('.general_settings').hide();
    	}
    }
});