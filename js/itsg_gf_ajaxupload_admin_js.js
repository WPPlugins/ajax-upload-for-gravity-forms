var text_ajax_upload = itsg_gf_ajaxupload_admin_js_settings.text_ajax_upload;
var text_instructions = itsg_gf_ajaxupload_admin_js_settings.text_instructions;
var text_make_ajax_upload = itsg_gf_ajaxupload_admin_js_settings.text_make_ajax_upload;

// ADD drop down options to list field in form editor - hooks into existing GetFieldChoices function.
(function (w){
	var GetFieldChoicesOld = w.GetFieldChoices;

	w.GetFieldChoices = function (){

		str = GetFieldChoicesOld.apply(this, [field]);

		if(field.choices == undefined)
			return "";

		for(var i=0; i<field.choices.length; i++){
			var inputType = GetInputType(field);
			var isAjaxUpload = field.choices[i].isAjaxUpload ? "checked" : "";

			var value = field.enableChoiceValue ? String(field.choices[i].value) : field.choices[i].text;
			if (inputType == 'list' ){
				if (i == 0 ){
					str += "<p><strong>" + text_ajax_upload + "</strong><br>" + text_instructions + "</p>";
				}
				str += "<div>";
				str += "<input type='checkbox' name='choice_ajaxupload' id='" + inputType + "_choice_ajaxupload_" + i + "' " + isAjaxUpload + " onclick=\"SetFieldChoiceAjaxUpload('" + inputType + "', " + i + ");\" /> ";
				str += "	<label class='inline' for='"+ inputType + "_choice_ajaxupload_" + i + "'>"+value+" - " + text_make_ajax_upload + "</label>";
				str += "</div>";
			}
		}
		return str;
	}
})(window || {});

// handles the 'make drop down' checkbox and option fields
function SetFieldChoiceAjaxUpload( inputType, index ) {
	var element = jQuery( '#' + inputType + '_choice_selected_' + index );

	if ('list' == inputType) {
		var element = jQuery( '#' + inputType + '_choice_ajaxupload_' + index );
		isAjaxUpload = element.is( ':checked' );
	}
	field = GetSelectedField();

	//set field selections
	jQuery( "#field_columns input[name='choice_ajaxupload']:checkbox" ).each( function( index ) {
		field.choices[index].isAjaxUpload = this.checked;
	});

	LoadBulkChoices( field );

	UpdateFieldChoices( GetInputType( field ) );

	for( var i = 0; i < field.choices.length; i++ ) {
		isAjaxUpload = jQuery( '#' + inputType + '_choice_ajaxupload_' + i ).is( ':checked' );
		column = i + 1;
		if ( true == isAjaxUpload ) {
			browse_input = '<input type="file" disabled="disabled" >';
			jQuery( 'li#field_' + field.id + ' table.gfield_list_container tbody tr td:nth-child(' + column + ')' ).html( browse_input );
		}
	}
}

// trigger for when field is opened
jQuery( document ).on( 'click', 'ul.gform_fields', function() {
	itsg_gf_list_ajaxupload_function();
});

// trigger for when column titles are updated
jQuery( document ).on( 'change', '#gfield_settings_columns_container #field_columns li.field-choice-row', function() {
	InsertFieldChoice(0);
	DeleteFieldChoice(0);
});

// trigger when 'Enable multiple columns' is ticked
jQuery( document ).on('change', '#field_settings input[id=field_columns_enabled]', function() {
	itsg_gf_list_ajaxupload_function();
});

// hand field value
jQuery( document ).bind( 'gform_load_field_settings', function( event, field, form ) {
	jQuery( '#itsg_list_field_ajaxupload' ).prop( 'checked', field['itsg_list_field_ajaxupload'] );
});

function itsg_gf_list_ajaxupload_function() {
	// only display this option if a single column list field
	jQuery( '#field_settings input[id=field_columns_enabled]:visible' ).each(function() {
		if ( jQuery( this ).is( ':checked' ) ) {
			jQuery( this ).closest( '#field_settings' ).find( '.itsg_list_field_ajaxupload' ).hide();
		} else {
			jQuery( this ).closest( '#field_settings' ).find( '.itsg_list_field_ajaxupload' ).show();
		}
	});
}