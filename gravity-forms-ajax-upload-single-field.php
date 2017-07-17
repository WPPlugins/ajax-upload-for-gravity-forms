<?php

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/*
 *  Contains all the functions responsible for the single field ajax uploader
 */

class GF_Field_FileUploadAjax extends GF_Field {

	public $type = 'itsg_single_ajax';
	public $cssClass = 'itsg_single_ajax';

	public function get_form_editor_field_title() {
		return __( 'Ajax Upload', 'ajax-upload-for-gravity-forms' );
	} // END get_form_editor_field_title

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'visibility_setting',
			'description_setting',
			'css_class_setting',
		);
	} // END get_form_editor_field_settings

	public function get_field_input( $form, $value = '', $entry = null ) {
		$lead_id = intval( rgar( $entry, 'id' ) );
		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$id          = (int) $this->id;

		if ( $is_form_editor || ( $is_entry_detail && 'edit' != rgpost( 'screen_mode' ) )) {
			$content = $input = '<input  disabled = "disabled"  type="file" />';
		} else {
			$field_required = $this->isRequired;
			$field_label = $this->label;
			$field_failed_valid = $this->failed_validation;
			$field_description = $this->description;
			$attachment_label = esc_attr__( 'Attachment', 'ajax-upload-for-gravity-forms' );
			$remove_label = esc_attr__( 'Remove', 'ajax-upload-for-gravity-forms' );
			$cancel_label = esc_attr__( 'Cancel', 'ajax-upload-for-gravity-forms' );
			$content = '<div class="itsg_single_ajax itsg_ajax_upload_dropzone">';
			$content .= "<input class='itsg_single_ajax_input'  type='hidden' name='input_{$id}' value='{$value}'>";
			$content .= "<input id='input_{$form_id}_{$id}' aria-label='{$attachment_label}: {$field_label}' title='{$attachment_label}: {$field_label}' class='itsg_ajax_upload_browse' type='file'>";
			$content .=  '</div>';
		}
		return $content;
	} // END get_field_input

	/* preview field display  - back end and front end */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		$output = '';
		if ( ! empty( $value ) && GFCommon::is_valid_url( $value ) ) {
			// get Ajax Upload options
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();
			$file_url = wp_kses_post( $value );
			if ( GFCommon::is_ssl() && strpos( $file_url, 'http:' ) !== false ) {
				$file_url = str_replace( 'http:', 'https:', $file_url );
			}
			$file_name = pathinfo( $value, PATHINFO_BASENAME );  // get the file name out of the URL
			$file_name = parse_url( $file_name );
			$file_name = $file_name['path'];
			$file_name_decode = rawurldecode( $file_name );  // decode the URL - remove %20 etc
			$file_type = strtolower( pathinfo( $value, PATHINFO_EXTENSION ) );  // get the file type out of the URL
			$click_to_view_text = __( 'Click to view', 'ajax-upload-for-gravity-forms' );

			if( rgar( $ajax_upload_options, 'thumbnail_enable' ) && ( 'jpg' == $file_type || 'png' == $file_type || 'gif' == $file_type || 'jpeg' == $file_type ) ) {
				$file_url_thumb = ITSG_GF_AjaxUpload::get_thumbnail_url( $file_url, $this );
				$thumbnail_width = rgar( $ajax_upload_options, 'thumbnail_width' );
				$thumbnail_height = rgar( $ajax_upload_options, 'thumbnail_height' );
				$display_file_name = rgar( $ajax_upload_options, 'thumbnail_file_name_enable' ) ? "<div class='itsg_ajax_upload_file_name'><a href='{$file_url}' target='_blank' >{$file_name_decode}</a></div>" : '';
				$output = $format == 'text' ? $file_url . PHP_EOL : "<a href='{$file_url}' target='_blank' ><img width='{$thumbnail_width}'  class='thumbnail' src='{$file_url_thumb}' onerror='if (this.src != \"{$file_url}\") this.src = \"{$file_url}\";' /></a>{$display_file_name}";
			} else {
				$output = $format == 'text' ? $file_url . PHP_EOL : "<a href='{$file_url}' target='_blank' title='{$click_to_view_text}'>{$file_name_decode}</a>";
			}
		}
		$output = empty( $output ) || $format == 'text' ? $value : sprintf( '%s', $output );

		return $output;
	} // END get_value_entry_detail

	// how the field is displayed in the entry list
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		$file_url = $value;
		if ( ! empty( $file_url ) && GFCommon::is_valid_url( $file_url ) ) {
			$field = GFFormsModel::get_field( $form, $field_id );
			//displaying thumbnail (if file is an image) or an icon based on the extension
			$thumb     = GFEntryList::get_icon_url( $file_url );
			$file_url = ITSG_GF_AjaxUpload::get_download_url( $file_url, false, $field );
			$file_url = esc_attr( $file_url );
			$value     = "<a href='$file_url' target='_blank' title='" . esc_attr__( 'Click to view', 'gravityforms' ) . "'><img src='$thumb'/></a>";
		}
		return $value;
	} // END get_value_entry_list

} // END GF_Field_FileUploadAjax

GF_Fields::register( new GF_Field_FileUploadAjax() );

if ( !class_exists( 'ITSG_GF_AjaxUpload_SingleField' ) ) {
	class ITSG_GF_AjaxUpload_SingleField {

		function __construct() {
			// wrap single ajax upload field in field set
			add_action( 'gform_field_content' , array( $this, 'wrap_single_fieldset' ), 10, 5 );

			add_filter( 'gform_entry_field_value', array( $this, 'display_field_value' ), 10, 4 );

		} // END __construct

		/**
         * Displays the single upload field - note that it wraps in a fieldset for accessibility  - makes the 'browse' correctly label itself.
         */
        function wrap_single_fieldset( $content, $field, $value, $lead_id, $form_id ){
			// get Ajax Upload options
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();

			if ( rgar( $ajax_upload_options, 'wrapsinglefieldset' ) ) {
				$field_required = rgar( $field, 'isRequired' );
				$field_label = rgar( $field, 'label' );
				$field_id = rgar( $field, 'id' );
				$field_failed_valid = rgar( $field, 'failed_validation' );
				$field_description = rgar( $field, 'description' );
				if ( 'itsg_single_ajax' == rgar( $field, 'type' ) ) {
					if ( $field_required ) {
						$content = str_replace("<label class='gfield_label' for='input_{$form_id}_{$field_id}' >{$field_label}<span class='gfield_required'>*</span></label>","<fieldset class='gfieldset'><legend class='gfield_label'><label class='gfield_label' for='input_{$form_id}_{$field_id}' >{$field_label}<span class='gfield_required'>*</span><span class='sr-only'> ".__( 'File upload' ,'ajax-upload-for-gravity-forms')."</span></label></legend>", $content);
					} else {
						$content = str_replace("<label class='gfield_label' for='input_{$form_id}_{$field_id}' >{$field_label}</label>", "<fieldset class='gfieldset'><legend class='gfield_label'><label class='gfield_label' for='input_{$form_id}_{$field_id}' >{$field_label}<span class='sr-only'> ".__('File upload' ,'ajax-upload-for-gravity-forms')."</span></label></legend>", $content);
					}
					//if field has failed validation
					if( $field_failed_valid ){
						//add add aria-invalid='true' attribute to input
						$content = str_replace( " name='input_", " aria-invalid='true' name='input_", $content );
						//if aria-describedby attribute not already present
						if ( false !== strpos( strtolower( $content ), 'aria-describedby' ) )  {
							$content = str_replace( " aria-describedby='", " aria-describedby='field_{$form_id}_{$field_id}_vmessage ", $content );
						} else {
							// aria-describedby attribute is already present
							$content = str_replace( " title", " aria-describedby='field_{$form_id}_{$field_id}_vmessage' title", $content );
						}
						//add add class for aria-describedby error message
						$content = str_replace( " class='gfield_description validation_message'", " class='gfield_description validation_message' id='field_{$form_id}_{$field_id}_vmessage'", $content);
					}
					if( !empty( $field_description ) ){
					// if field has a description, link description to field using aria-describedby
					// dont apply to validation message - it already has an ID
					//if aria-describedby attribute not already present
						if ( false !== strpos( strtolower( $content ), 'aria-describedby' ) )  {
							$content = str_replace( " aria-describedby='", " aria-describedby='field_{$form_id}_{$field_id}_dmessage ", $content);
						} else {
							// aria-describedby attribute is already present
							$content = str_replace( " title", " aria-describedby='field_{$form_id}_{$field_id}_dmessage' title", $content);
						}
						//add add class for aria-describedby description message
						$content = str_replace( " class='gfield_description'"," id='field_{$form_id}_{$field_id}_dmessage' class='gfield_description'", $content);
					}
				}
			}
			return $content;
        } // END wrap_single_fieldset

		/* how the field is displayed in in PDF's using the Gravity PDF plugin  */
		function display_field_value( $value, $field, $lead, $form ) {
			$is_entry_detail = GFCommon::is_entry_detail();
			if ( 'itsg_single_ajax' == rgar( $field, 'type' ) && isset( $_GET['gf_pdf'] ) ) {
				$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();
				$form_id = $form['id'];
				$file_name = pathinfo( $value, PATHINFO_BASENAME );  // get the file name out of the URL
				$file_name = parse_url( $file_name );
				$file_name = $file_name['path'];
				$file_name_decode = rawurldecode( $file_name );  // decode the URL - remove %20 etc
				$file_type = strtolower( pathinfo( $value, PATHINFO_EXTENSION ) );  // get the file type out of the URL
				$file_url = wp_kses_post( $value );
				if( ( 'jpg' == $file_type || 'png' == $file_type || 'gif' == $file_type || 'jpeg' == $file_type ) &&
				( ( !isset( $_GET['gf_pdf'] ) && rgar( $ajax_upload_options, 'thumbnail_enable' ) ) || ( isset( $_GET['gf_pdf'] ) && rgar( $ajax_upload_options, 'gpdf_use_thumbnails' ) ) ) ) {
					$file_url_thumb = ITSG_GF_AjaxUpload::get_thumbnail_url( $file_url, $field );
					if ( isset( $_GET['gf_pdf'] ) && rgar( $ajax_upload_options, 'gpdf_use_serverpath' ) ) {
						$file_path_thumb = rawurldecode( str_replace( site_url() . '/', ABSPATH, $file_url_thumb ) );
						// Read image path, convert to base64 encoding
						$imageData = base64_encode( file_get_contents( $file_path_thumb ) );
						// Format the image SRC:  data:{mime};base64,{data};
						$file_url_thumb = 'data: ' . mime_content_type( $file_path_thumb ) . ';base64,' . $imageData;
					}
					$thumbnail_width = rgar( $ajax_upload_options, 'thumbnail_width' );
					$display_file_name = rgar( $ajax_upload_options, 'thumbnail_file_name_enable' ) ? "<div class='itsg_ajax_upload_file_name'><a href='{$file_url}' target='_blank' >{$file_name_decode}</a></div>" : '';
					$value = "<a href='{$file_url}' target='_blank' >
						<img
						src='{$file_url_thumb}'
						width='{$thumbnail_width}'
						class='thumbnail'
						onerror='if (this.src != \"{$file_url}\") this.src = \"{$file_url}\";' />
					</a>{$display_file_name}";
				} else {
					$file_name = pathinfo( $file_url, PATHINFO_BASENAME );  // get the file name out of the URL
					$file_name = parse_url( $file_name );
					$file_name = $file_name['path'];
					$file_name_decode = rawurldecode( $file_name );  // decode the URL - remove %20 etc
					if ( strlen( $file_name_decode ) > 30 && !preg_match( '/\s/', $file_name_decode ) ) {
						$file_name_decode = substr( $file_name_decode, 0, 30 ). ' ...';
					}
					$value = "<a href='{$file_url}' target='_blank'>{$file_name_decode}</a>";
				}
			}
			return $value;
		} // END display_field_value
	}
}
$ITSG_GF_AjaxUpload_SingleField = new ITSG_GF_AjaxUpload_SingleField();