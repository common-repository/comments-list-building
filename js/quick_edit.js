(function($) {
   $('input#listbuilder-options-hide').parent().hide();

   var $wp_inline_edit = inlineEditPost.edit;
   inlineEditPost.edit = function( id ) {

      $wp_inline_edit.apply( this, arguments );

      var $post_id = 0;
      if ( typeof( id ) == 'object' )
         $post_id = parseInt( this.getId( id ) );

      if ( $post_id > 0 ) {

           var $edit_row = $( '#edit-' + $post_id );

	        var $options = jQuery.parseJSON($( '#listbuilder-options-' + $post_id ).text());

            if($options.listbuilder_plugin_active == 'klicktipp') {
               $edit_row.find('.klicktipp').show();
               $edit_row.find('.general_settings').show();
               $edit_row.find('.autoresponder').hide();
            } else if($options.listbuilder_plugin_active == 'autoresponder') {
               $edit_row.find('.autoresponder').show();
               $edit_row.find('.general_settings').show();
               $edit_row.find('.klicktipp').hide();
            } else {
               $edit_row.find('.autoresponder').hide();
               $edit_row.find('.klicktipp').hide();
               $edit_row.find('.general_settings').hide();
            }

	         $edit_row.find( 'select[name="listbuilder-plugin-active"]' ).val( $options.listbuilder_plugin_active );
           $edit_row.find( 'textarea[name="klicktippAdditionalText"]' ).val( $options.klicktipp_additional_text.replace(/\\/g, ''));
           $edit_row.find( 'input[name="klicktippOptInID"]' ).val( $options.klicktipp_optin_id );
           $edit_row.find( '.optin_name' ).text( '(Bezeichnung ' + $options.klicktipp_optin_name + ')' );
           $edit_row.find( '.tag_id' ).text( '(Tag ID ' + $options.klicktipp_tag_id + ')' );
           $edit_row.find( 'input[name="klicktippTag"]' ).val( $options.klicktipp_tag );
           $edit_row.find( 'textarea[name="ar_code"]' ).val( $options.autoresponder_code.replace(/\\/g, ''));
           $edit_row.find( 'input[name="ar_url"]' ).val( $options.autoresponder_url );
           $edit_row.find( 'input[name="ar_name"]' ).val( $options.autoresponder_name );
           $edit_row.find( 'input[name="ar_email"]' ).val( $options.autoresponder_email );
           $edit_row.find( 'textarea[name="ar_hidden"]' ).val( $options.autoresponder_hidden.replace(/\\/g, ''));
           $edit_row.find( 'input[name="klicktippCheckboxText"]' ).val( $options.klicktipp_checkbox_text );
           $edit_row.find( 'input[name="klicktippCheckboxChecked"]' ).prop('checked', $options.klicktipp_checkbox_checked == 'TRUE' ? true : false );
           $edit_row.find( 'input[name="klicktippAddSubscriberWithoutRelease"]' ).prop('checked', $options.klicktipp_add_subscriber_without_release == 'TRUE' ? true : false );
           $edit_row.find( 'input[name="privacy-url"]' ).val( $options.listbuilder_privacy_url );

           $edit_row.find( '#load_default').hide();
           $edit_row.find( 'select[name="listbuilder-plugin-active"]' ).on('change', function(){
              if($(this).val() == 'klicktipp' || $(this).val() == 'autoresponder') {
                $edit_row.find( '#load_default').show();
              } else {
                $edit_row.find( '#load_default').hide(); 
              }
           });
           if($edit_row.find( 'select[name="listbuilder-plugin-active"]' ).val() == 'klicktipp' || $edit_row.find( 'select[name="listbuilder-plugin-active"]' ).val() == 'autoresponder') {
              $edit_row.find( '#load_default').show();
           } else {
              $edit_row.find( '#load_default').hide();
           }
           $edit_row.find( '#load_default').on('click', function(e) {
           		e.preventDefault();
           		load_default($edit_row, $options);
           });
      }

   };

$( '#bulk_edit').live( 'click', function(event) {
     bulk_edit(event);
});
$(document).on('click', '#doaction', function(e){
	if($('#bulk-action-selector-top').val() != 'edit'){
		$('.column-listbuilder-options').remove();
	} else {
     bulk_edit(e);
  }
});
$(document).on('click', '#doaction2', function(e){
	if($('#bulk-action-selector-bottom').val() != 'edit'){
		$('.column-listbuilder-options').remove();
	} else {
     bulk_edit(e);
  }
});
function bulk_edit(event) {
  $('.column-listbuilder-options').remove();
     var $bulk_row = $( '#bulk-edit' );

     var $post_ids = new Array(); 
     $bulk_row.find( '#bulk-titles' ).children().each( function() {
        $post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
     });

     var $options = jQuery.parseJSON($( '#listbuilder-options-bulk' ).text());  

     var $active = $bulk_row.find( 'select[name="listbuilder-plugin-active"]' ).val();
     var $klicktippAdditionalText = $bulk_row.find( 'textarea[name="klicktippAdditionalText"]' ).val();
     var $klicktippOptInID = $bulk_row.find( 'input[name="klicktippOptInID"]' ).val();
     var $klicktippTag = $bulk_row.find( 'input[name="klicktippTag"]' ).val();
     var $ar_code = $bulk_row.find( 'textarea[name="ar_code"]' ).val();
     var $ar_url = $bulk_row.find( 'input[name="ar_url"]' ).val();
     var $ar_name = $bulk_row.find( 'input[name="ar_name"]' ).val();
     var $ar_email = $bulk_row.find( 'input[name="ar_email"]' ).val();
     var $ar_hidden = $bulk_row.find( 'textarea[name="ar_hidden"]' ).val();
     var $klicktippCheckboxText = $bulk_row.find( 'input[name="klicktippCheckboxText"]' ).val();
     var $klicktippCheckboxChecked = $bulk_row.find( 'input[name="klicktippCheckboxChecked"]' ).attr("checked") ? 'TRUE' : '';
     var $klicktippAddSubscriberWithoutRelease = $bulk_row.find( 'input[name="klicktippAddSubscriberWithoutRelease"]' ).attr("checked") ? 'TRUE' : '';
     var $privacyurl = $bulk_row.find( 'input[name="privacy-url"]' ).val();

      $bulk_row.find( '#load_default').hide();
      $bulk_row.find( 'select[name="listbuilder-plugin-active"]' ).on('change', function(){
          if($(this).val() == 'klicktipp' || $(this).val() == 'autoresponder') {
            $bulk_row.find( '#load_default').show();
          } else {
            $bulk_row.find( '#load_default').hide();
          }
       });
      if($bulk_row.find( 'select[name="listbuilder-plugin-active"]' ).val() == 'klicktipp' || $bulk_row.find( 'select[name="listbuilder-plugin-active"]' ).val() == 'autoresponder') {
          $bulk_row.find( '#load_default').show();
      } else {
          $bulk_row.find( '#load_default').hide();
      }
     $bulk_row.find( '#load_default').on('click', function(e) {
      e.preventDefault();
      load_default($bulk_row, $options);
     });

     $.ajax({
        url: ajaxurl,
        type: 'POST',
        async: false,
        cache: false,
        data: {
           'action': 'clb_save_bulk_edit',
           'post_ids': $post_ids,
           'listbuilder-plugin-active': $active,
           'klicktippAdditionalText': $klicktippAdditionalText,
           'klicktippOptInID': $klicktippOptInID,
           'klicktippTag': $klicktippTag,
           'ar_code': $ar_code,
           'ar_url': $ar_url,
           'ar_name': $ar_name,
           'ar_email': $ar_email,
           'ar_hidden': $ar_hidden,
           'klicktippCheckboxText': $klicktippCheckboxText,
           'klicktippCheckboxChecked': $klicktippCheckboxChecked,
           'klicktippAddSubscriberWithoutRelease': $klicktippAddSubscriberWithoutRelease,
           'privacy-url': $privacyurl
        }
     });
}
function load_default($row, $options) {
	var $defaults = jQuery.parseJSON($options.default);
   $row.find( 'textarea[name="klicktippAdditionalText"]' ).val( $defaults.klicktippAdditionalText.replace(/\\/g, ''));
   $row.find( 'input[name="klicktippOptInID"]' ).val( $defaults.klicktippOptInID );
   $row.find( '.optin_name' ).text( '(Bezeichnung ' + $defaults.klicktippOptInName + ')' );
   $row.find( '.tag_id' ).text( '(Tag ID ' + $defaults.klicktippTagID + ')' );
   $row.find( 'input[name="klicktippTag"]' ).val( $defaults.klicktippTag );
   $row.find( 'textarea[name="ar_code"]' ).val( $defaults.autoresponderCode.replace(/\\/g, ''));
   $row.find( 'input[name="ar_url"]' ).val( $defaults.autoresponderUrl );
   $row.find( 'input[name="ar_name"]' ).val( $defaults.autoresponderName );
   $row.find( 'input[name="ar_email"]' ).val( $defaults.autoresponderEmail );
   $row.find( 'textarea[name="ar_hidden"]' ).val( $defaults.autoresponderHidden.replace(/\\/g, ''));
   $row.find( 'input[name="klicktippCheckboxText"]' ).val( $defaults.klicktippCheckboxText );
   $row.find( 'input[name="klicktippCheckboxChecked"]' ).prop('checked', $defaults.klicktippCheckboxChecked == 'TRUE' ? true : false );
   $row.find( 'input[name="klicktippAddSubscriberWithoutRelease"]' ).prop('checked', $defaults.klicktippAddSubscriberWithoutRelease == 'TRUE' ? true : false );
   $row.find( 'input[name="privacy-url"]' ).val( $defaults.listbuilder_privacy_url );
}
})(jQuery);