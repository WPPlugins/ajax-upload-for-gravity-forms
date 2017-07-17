<?php

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/*
 *   Setup the list field ajax upload class
 */

if ( !class_exists( 'ITSG_GF_AjaxUpload_ListField' ) ) {
    class ITSG_GF_AjaxUpload_ListField {

        public function __construct() {
			add_filter( 'gform_column_input_content', array( $this, 'change_column_content_file' ), 10, 6 );
			add_action( 'gform_field_css_class', array( $this, 'repeat_custom_class' ), 10, 3 );
			add_filter( 'gform_get_field_value', array( $this, 'clickable_list_values' ), 10, 3 );
			add_action( 'gform_field_standard_settings', array( $this, 'field_ajaxupload_settings' ) , 10, 2 );
			add_filter( 'gform_tooltips', array( $this, 'field_ajaxupload_tooltip' ) );
			add_filter( 'gform_entry_field_value', array( $this, 'display_field_value' ), 10, 4 );
		} // END __construct

		/*
		 * If it's an attachment row customise the fields to include the file upload, and hashed user_id and form_id codes
		 */
		function change_column_content_file( $input, $input_info, $field, $text, $value, $form_id ) {
			$is_form_editor  = GFCommon::is_form_editor();
			$has_columns = is_array( $field->choices );
				if ( $has_columns ) {
					foreach( $field->choices as $choice ) {
						if ( $text == rgar( $choice, 'text' ) && true  == rgar( $choice, 'isAjaxUpload' ) ) {
							if ( $is_form_editor ) {
								$input = '<input disabled = "disabled"  type="file" />';
							} else {
								$input = str_replace( "type='text'", "type='hidden'", $input );
								$input = '<div class="itsg_list_ajax itsg_ajax_upload_dropzone">' . $input;
								$input .= '<input class="itsg_ajax_upload_browse" type="file">';
								$input .=  '</div>';
							}
						}
					}
				} else {
					if ( 'on' == $field->itsg_list_field_ajaxupload ) {
						if ( $is_form_editor ) {
							$input = '<input disabled = "disabled"  type="file" />';
						} else {
							$input = str_replace( "type='text'", "type='hidden'", $input );
							$input = '<div class="itsg_list_ajax itsg_ajax_upload_dropzone">' . $input;
							$input .= '<input class="itsg_ajax_upload_browse" type="file">';
							$input .=  '</div>';
						}
					}
					return $input;
				}
			return $input;
		} // END change_column_content_file

		/*
		 * Add 'form_ajax_file_upload' css class to lists that contain the attachment column
		 */
		function repeat_custom_class( $classes, $field, $form ) {
			if( 'list' == $field->get_input_type() ) {
				$has_columns = is_array( $field->choices );
				if ( $has_columns ) {
					foreach( $field->choices as $choice ){
						if ( rgar( $choice, 'isAjaxUpload' ) ) {
							$classes .= ' gform_ajax_file_upload';
						}
					}
				}
			}
			return $classes;
		} // END repeat_custom_class

		public function clickable_list_values( $value, $entry, $field ) {
			if ( $value ) {
				$is_entry_detail = GFCommon::is_entry_detail();
				if ( !( $is_entry_detail && 'edit' == rgpost( 'screen_mode' ) ) && is_object( $field ) && 'list' == $field->get_input_type() ) {
					$has_columns = is_array( $field->choices );
					$values = unserialize( $value );
						if ( !empty( $values ) ) {
							// get Ajax Upload options
							$form_id = $entry['form_id'];
							foreach ( $values as &$val ) {
								if ( $has_columns ) {
									$number_of_columns = sizeof( $field->choices );
									$column_number = 0;
									foreach ( $val as &$column ) {
										if ( true  == rgar( $field['choices'][ $column_number ], 'isAjaxUpload' ) && GFCommon::is_valid_url( $column ) ) {
											$column = $this->make_column( $column, $field );
										}
										if ( $column_number >= ( $number_of_columns - 1 ) ) {
											$column_number = 0; // reset column number
										} else {
											$column_number = $column_number + 1; // increment column number
										}
									}
								} elseif ( 'on' == $field->itsg_list_field_ajaxupload && GFCommon::is_valid_url( $val ) ) {
									$val = $this->make_column( $val, $field );
								}
							}
						}
					$value = serialize( $values );
				}
			}
			return $value;
		} // END clickable_list_values

		function make_column( $file_url, $field ) {
			$file_url = wp_kses_post( $file_url );
			// get Ajax Upload options
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();
			$file_name = pathinfo( $file_url, PATHINFO_BASENAME );  // get the file name out of the URL
			$file_name = parse_url( $file_name );
			$file_name = $file_name['path'];
			$file_name_decode = rawurldecode( $file_name );  // decode the URL - remove %20 etc
			$file_type = strtolower( pathinfo( $file_url, PATHINFO_EXTENSION ) );  // get the file type out of the URL
			if( ( 'jpg' == $file_type || 'png' == $file_type || 'gif' == $file_type || 'jpeg' == $file_type ) &&
				( ( !isset( $_GET['gf_pdf'] ) && rgar( $ajax_upload_options, 'thumbnail_enable' ) ) || ( isset( $_GET['gf_pdf'] ) && rgar( $ajax_upload_options, 'gpdf_use_thumbnails' ) ) ) ) {
					$file_url_thumb = ITSG_GF_AjaxUpload::get_thumbnail_url( $file_url, $field );
					$file_url = ITSG_GF_AjaxUpload::get_download_url( $file_url, false, $field );
					if ( isset( $_GET['gf_pdf'] ) && rgar( $ajax_upload_options, 'gpdf_use_serverpath' ) ) {
						$file_path_thumb = rawurldecode( str_replace( site_url() . '/', ABSPATH, $file_url_thumb ) );
						// Read image path, convert to base64 encoding
						$imageData = base64_encode( file_get_contents( $file_path_thumb ) );
						// Format the image SRC:  data:{mime};base64,{data};
						$file_url_thumb = 'data: ' . mime_content_type( $file_path_thumb ) . ';base64,' . $imageData;
					}
					$thumbnail_width = $ajax_upload_options['thumbnail_width'];
					$display_file_name = rgar( $ajax_upload_options, 'thumbnail_file_name_enable' ) ? "<div class='itsg_ajax_upload_file_name'><a href='{$file_url}' target='_blank' >{$file_name_decode}</a></div>" : '';
					$column = "<a href='{$file_url}' target='_blank' >
						<img
						src='{$file_url_thumb}'
						width='{$thumbnail_width}'
						class='thumbnail'
						onerror='if (this.src != \"{$file_url}\") this.src = \"{$file_url}\";' />
					</a>{$display_file_name}";
			} else {
				if ( substr( $file_url, 0, strlen( site_url() ) ) === site_url() ) { // if URL belongs to same website treat link as file
					$file_name = pathinfo( $file_url, PATHINFO_BASENAME );  // get the file name out of the URL
					$file_name = parse_url( $file_name );
					$file_name = $file_name['path'];
					$file_url = ITSG_GF_AjaxUpload::get_download_url( $file_url, false, $field );
					$file_name_decode = rawurldecode( $file_name );  // decode the URL - remove %20 etc
					if ( strlen( $file_name_decode ) > 30 && !preg_match( '/\s/', $file_name_decode ) ) {
						$file_name_decode = substr( $file_name_decode, 0, 30 ). ' ...';
					}
					$column = "<a href='{$file_url}' target='_blank' >{$file_name_decode}</a>";
				} else { // treat link as external, show full URL
					$external_url = $file_url;
					if ( strlen( $external_url ) > 30 ) {
						$external_url = substr( $external_url, 0, 30 ). ' ...';
					}
					$column = "<a href='{$file_url}' target='_blank' >{$external_url}</a>";
				}
			}
		return $column;
		} // END make_column

		/*
          * Adds custom sortable setting for field
          */
        function field_ajaxupload_settings( $position, $form_id ) {
            // Create settings on position 50 (top position)
            if ( 50 == $position ) {
				?>
				<li class='itsg_list_field_ajaxupload field_setting'>
					<input type='checkbox' id='itsg_list_field_ajaxupload' onclick='SetFieldProperty( "itsg_list_field_ajaxupload", this.checked );'>
					<label class='inline' for='itsg_list_field_ajaxupload'>
					<?php _e( 'Enable Ajax Upload', 'ajax-upload-for-gravity-forms' ); ?>
					<?php gform_tooltip( 'itsg_list_field_ajaxupload' );?>
					</label>
				</li>
			<?php
            }
        } // END field_ajaxupload_settings

		/*
         * Tooltip for for ajaxupload option
         */
		function field_ajaxupload_tooltip( $tooltips ) {
			$tooltips['itsg_list_field_ajaxupload'] = "<h6>". __( 'Enable Ajax Upload', 'ajax-upload-for-gravity-forms' )."</h6>". __( 'This option will turn a list field into repeatable Ajax Upload field. Only applies to single column list fields.', 'ajax-upload-for-gravity-forms' );
			return $tooltips;
		} // END field_ajaxupload_tooltip

		/* how the field is displayed in in PDF's using Gravity PDF plugin  */
		function display_field_value( $value, $field, $lead, $form ) {
			$is_entry_detail = GFCommon::is_entry_detail();
			if ( is_object( $field ) && 'list' == $field->get_input_type() && ! $is_entry_detail ) {
				$value =  htmlspecialchars_decode( $value );
			}
			return $value;
		} // END display_field_value


	}
$ITSG_GF_AjaxUpload_ListField = new ITSG_GF_AjaxUpload_ListField();
}