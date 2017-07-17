<?php
	add_filter( 'gfpdf_field_class', 'decode_wysiwgy_gravitypdf_4_1' , 10, 3 );	
	
	/*
	* Add Gravity PDF 4.0 support
	*/
	function decode_wysiwgy_gravitypdf_4_1( $class, $field, $entry ) {
		$ITSG_GF_AjaxUpload = new ITSG_GF_AjaxUpload();
		if ( $ITSG_GF_AjaxUpload->is_ajaxupload_field( $field ) ) {
			require_once( plugin_dir_path( __FILE__ ).'ITSG_GF_AjaxUpload_Field.php' );
			$class = new GFPDF\Helper\Fields\ITSG_GF_AjaxUpload_Field( $field, $entry, GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );
		}
		return $class;
	}