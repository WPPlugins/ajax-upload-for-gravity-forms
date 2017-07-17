<?php
/*
Plugin Name: Ajax Upload for Gravity Forms
Description: Provides two ajax file upload fields - a single field and the ability to make a list field column an upload field.
Version: 2.7.3
Author: Adrian Gordon
Author URI: http://www.itsupportguides.com
License: GPL2
Text Domain: ajax-upload-for-gravity-forms

------------------------------------------------------------------------
Copyright 2015 Adrian Gordon

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// include upload handler for image upload
if ( !class_exists('UploadHandler') && version_compare( phpversion(), '5.3', '>=' ) ) {
	require_once( plugin_dir_path( __FILE__ ).'UploadHandler.php' );

	class ITSG_AjaxUpload_UploadHandler extends UploadHandler {
		protected function trim_file_name( $file_path, $name, $size, $type, $error, $index, $content_range ) {
			$name = apply_filters( 'itsg_gf_ajaxupload_filename', $name, $file_path, $size, $type, $error, $index, $content_range );

			// get Ajax Upload options
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();

			if ( rgar( $ajax_upload_options, 'exclude_special_characters' ) ) {
				$exclude_characters = array(
					'\\',
					'/',
					':',
					';',
					'*',
					'?',
					'!',
					'"',
					'`',
					"'",
					'<',
					'>',
					'{',
					'}',
					'[',
					']',
					',',
					'|',
					'#',
					);
				$exclude_characters = (array)apply_filters( 'itsg_gf_ajaxupload_filename_exclude_characters', $exclude_characters );
				$replace_character = (string)apply_filters( 'itsg_gf_ajaxupload_filename_replace_characters', '' );
				$name = str_replace( $exclude_characters, $replace_character, $name );
			}

			return $name;
		}
	} // END ITSG_AjaxUpload_UploadHandler
}

add_action( 'admin_notices', array( 'ITSG_GF_AjaxUpload', 'admin_warnings' ), 20 );

/*
 *   Setup the main plugin class
 */
if ( !class_exists( 'ITSG_GF_AjaxUpload' ) ) {
	class ITSG_GF_AjaxUpload {

		private static $name = 'Ajax Upload for Gravity Forms';
		private static $slug = 'itsg_gf_ajaxupload';

		/*
         * Construct the plugin object
         */
		function __construct() {
			// register plugin functions through 'gform_loaded' -
			// this delays the registration until Gravity Forms has loaded, ensuring it does not run before Gravity Forms is available.
            add_action( 'gform_loaded', array( $this, 'register_actions' ) );
		} // END __construct

		/*
         * Register plugin functions
         */
		function register_actions() {
            if ( self::is_gravityforms_installed() && version_compare( phpversion(), '5.3', '>=' ) ) {
				// start the plugin

				load_plugin_textdomain( 'ajax-upload-for-gravity-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' );

				//  functions for single field ajax upload
				require_once( plugin_dir_path( __FILE__ ).'gravity-forms-ajax-upload-single-field.php' );
				//  functions for list field ajax upload
				require_once( plugin_dir_path( __FILE__ ).'gravity-forms-ajax-upload-list-field.php' );

				// addon framework
				require_once( plugin_dir_path( __FILE__ ).'gravity-forms-ajax-upload-addon.php' );

				// ajax hook - upload file for users that are logged in
				add_action( 'wp_ajax_itsg_ajaxupload_upload_file', array( $this, 'itsg_ajaxupload_upload_file' ) );

				// ajax hook - upload file for users that are not logged in
				add_action( 'wp_ajax_nopriv_itsg_ajaxupload_upload_file', array( $this, 'itsg_ajaxupload_upload_file' ) );

				// get Ajax Upload options
				$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();

				// ajax hook - delete file for users that are logged in
				if ( rgar( $ajax_upload_options, 'allowdelete' ) ) {
					add_action( 'wp_ajax_itsg_ajaxupload_delete_file', array( $this, 'itsg_ajaxupload_delete_file' ) );
				}

				// ajax hook - delete file for users that are not logged in
				if ( rgar( $ajax_upload_options, 'allownoprivdelete' ) ) {
					add_action( 'wp_ajax_nopriv_itsg_ajaxupload_delete_file', array( $this, 'itsg_ajaxupload_delete_file' ) );
				}

				// handles the change upload path settings
				add_filter( 'gform_upload_path', array( $this, 'change_upload_path' ), 999, 2 );

				add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_action_links' ) );

				// woocommerce fix
				add_filter( 'woocommerce_gforms_strip_meta_html', array( $this, 'configure_woocommerce_gforms_strip_meta_html' ), 5, 10 );

				// attach to notification
				add_filter( 'gform_notification', array( $this, 'notification_attachments' ), 10, 3 );

				add_filter( 'gform_notification_ui_settings', array( $this, 'notification_setting' ), 10, 3 );

				add_filter( 'gform_pre_notification_save', array( $this, 'notification_save' ), 10, 2 );

				add_action( 'gform_entry_info', array( $this, 'entry_download_attachments_button' ), 10, 2 );

				add_action( 'init', array( $this, 'maybe_process_zip_url' ) );

				if ( self::is_minimum_php_version() ) {
					require_once( plugin_dir_path( __FILE__ ) . 'includes/gravitypdf/gravitypdf.php' );
				}

				if ( ! class_exists( 'GF_Download' ) ) {
					require_once( plugin_dir_path( __FILE__ ) . 'includes/class-gf-download.php' );

					add_action( 'init', array( 'GF_Download', 'maybe_process' ) );

				}

				add_action( 'gform_entries_first_column_actions', array ( $this, 'first_column_actions' ), 10, 4 );

				// patch to allow JS and CSS to load when loading forms through wp-ajax requests
				add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_scripts' ), 90, 2 );

			}
		} // END register_actions

	/**
	 * BEGIN: patch to allow JS and CSS to load when loading forms through wp-ajax requests
	 *
	 */

		/*
         * Enqueue JavaScript to footer
         */
		public function enqueue_scripts( $form, $is_ajax ) {
			if ( $this->requires_scripts( $form, $is_ajax ) ) {
				$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();

				$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) || rgar( $ajax_upload_options, 'displayscripterrors' ) ? '' : '.min';

				wp_enqueue_script( 'gform_jquery_ui_widget', plugins_url( '/js/jquery.ui.widget.js', __FILE__ ), array( 'jquery' ) );
				wp_enqueue_script( 'gform_jquery_iframe', plugins_url( '/js/jquery.iframe-transport.js', __FILE__ ), array( 'jquery' ) );
				wp_enqueue_script( 'gform_fileupload', plugins_url( '/js/jquery.fileupload.js', __FILE__ ), array( 'jquery' ) );
				wp_enqueue_style( 'itsg_gf_ajaxupload_css', plugins_url( "/css/itsg_gf_ajaxupload_css{$min}.css", __FILE__ ) );
				wp_register_script( 'itsg_gf_ajaxupload_js', plugins_url( "/js/itsg_gf_ajaxupload_js{$min}.js", __FILE__ ), array( 'jquery' ) );

				// Localize the script with new data
				$this->localize_scripts( $form, $is_ajax );

			}
		} // END datepicker_js

		public function requires_scripts( $form, $is_ajax ) {
			if ( is_admin() && ! GFCommon::is_form_editor() && is_array( $form ) ) {
				foreach ( $form['fields'] as $field ) {
					if ( $this->is_ajaxupload_field( $field ) ) {
						return true;
					}
				}
			}

			return false;
		} // END requires_scripts

		function localize_scripts( $form, $is_ajax ) {
			// Localize the script with new data
			$form_id = $form['id'];
			$is_entry_detail = GFCommon::is_entry_detail();
			if ( $is_entry_detail && isset( $_GET['lid'] ) ) {
				$entry_id = rgar( $_GET, 'lid' );
				$entry = GFAPI::get_entry( $entry_id );
				$entry_user_id = rgar( $entry, 'created_by' );
			} else {
				$entry_user_id = get_current_user_id() ? get_current_user_id() : null;
			}
			$ajax_upload_options = apply_filters( 'itsg_gf_upload_file_options', ITSG_GF_AjaxUpload::get_options(), $form_id );

			$settings_array = array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'allowdelete' => ( is_user_logged_in() && rgar( $ajax_upload_options, 'allowdelete' ) ) || ( !is_user_logged_in() && rgar( $ajax_upload_options, 'allownoprivdelete' ) ),
				'form_id' => $form_id,
				'entry_user_id' => $entry_user_id,
				'thumbnail_enable' => rgar( $ajax_upload_options, 'thumbnail_enable' ),
				'thumbnail_file_name_enable' => rgar( $ajax_upload_options, 'thumbnail_file_name_enable' ),
				'thumbnail_width' => floatval( rgar( $ajax_upload_options, 'thumbnail_width' ) ),
				'file_chunk_size' => floatval( rgar( $ajax_upload_options, 'file_chunk_size' ) ) * 1024 * 1024,
				'file_size_kb' => floatval( rgar( $ajax_upload_options, 'filesize' ) ) * 1024 * 1024,
				'file_types' => rgar( $ajax_upload_options, 'filetype' ),
				'text_not_accepted_file_type' => esc_js( __( 'Not an accepted file type', 'ajax-upload-for-gravity-forms' ) ),
				'text_file_size_too_big' => esc_js( __( 'File size is too big', 'ajax-upload-for-gravity-forms' ) ),
				'text_uploading' => esc_js( rgar( $ajax_upload_options, 'text_uploading' ) ),
				'text_cancel' => esc_js( rgar( $ajax_upload_options, 'text_cancel' ) ),
				'text_remove' => esc_js( rgar( $ajax_upload_options, 'text_remove' ) ),
				'text_error_title' => esc_js( __( 'Error uploading file', 'ajax-upload-for-gravity-forms' ) ),
				'displayscripterrors' => rgar( $ajax_upload_options, 'displayscripterrors' ),
				'text_complete' => esc_js( __( 'complete', 'ajax-upload-for-gravity-forms' ) ),
				'user_id' => get_current_user_id() ? get_current_user_id() : '0',
				'text_error' => esc_js( __( 'Error', 'ajax-upload-for-gravity-forms' ) ),
				'text_file' => esc_js( __( 'File', 'ajax-upload-for-gravity-forms' ) ),
				'text_new_window' => esc_js( __( 'this link will open in a new window', 'ajax-upload-for-gravity-forms' ) ),
				'text_error_0' => esc_js( __( 'Not connect.\n Verify Network.', 'ajax-upload-for-gravity-forms' ) ),
				'text_error_404' => esc_js( __( 'Requested page not found [404]', 'ajax-upload-for-gravity-forms' ) ),
				'text_error_500' => esc_js( __( 'Internal Server Error [500]', 'ajax-upload-for-gravity-forms' ) ),
				'text_error_parse' => esc_js( __( 'Requested JSON parse failed.', 'ajax-upload-for-gravity-forms' ) ),
				'text_error_timeout' => esc_js( __( 'Time out error.', 'ajax-upload-for-gravity-forms' ) ),
				'text_error_uncaught' => esc_js( __( 'Uncaught Error.\n', 'ajax-upload-for-gravity-forms' ) ),
				'text_line_number' => esc_js( __( 'Line Number', 'ajax-upload-for-gravity-forms' ) ),
				'text_only_one_file_message' => esc_js( __( 'Error: only 1 file can be uploaded at a time.', 'ajax-upload-for-gravity-forms' ) ),
				'apostrophe_error_enable' => rgar( $ajax_upload_options, 'apostrophe_error_enable' ),
				'text_apostrophe_error_message' => esc_js( __( "Error: file name contains an apostrophe/quote (&lsquo;). Remove apostrophe/quote (&lsquo;) from file name and try again.", 'ajax-upload-for-gravity-forms' ) ),
				'is_entry_detail' => $is_entry_detail ? $is_entry_detail : 0,
			);

			wp_localize_script( 'itsg_gf_ajaxupload_js', 'itsg_gf_ajaxupload_js_settings', $settings_array );

			// Enqueued script with localized data.
			wp_enqueue_script( 'itsg_gf_ajaxupload_js' );

		} // END localize_scripts

	/**
	 * END: patch to allow JS and CSS to load when loading forms through wp-ajax requests
	 *
	 */

		/*
         * Check if PHP version is at least 5.4
         */
        private static function is_minimum_php_version() {
			return version_compare( phpversion(), '5.4', '>=' );
        } // END is_minimum_php_version

		function first_column_actions( $form_id, $field_id, $value, $entry ) {
			if ( ! $this->entry_has_files( $form_id, $entry ) ) {
				return;
			}

			$url = $this->get_zip_url( $entry );
			if ( ! is_Null ( $url ) ) {
				echo "| <span><a href=\"{$url}\">". __( 'Download File(s)', 'ajax-upload-for-gravity-forms' ) ."</a></span>";
			}
		} // END first_column_actions

		function entry_has_files( $form_id, $entry ) {

			$form = GFAPI::get_form( $form_id, true );

			$fileupload_fields = GFCommon::get_fields_by_type( $form, array( 'itsg_single_ajax' , 'list' ) );

			if ( ! is_array( $fileupload_fields ) ) {
				return false;
			}

			$uploaded_files = (array) $this->get_uploaded_files( $fileupload_fields, $entry );

			if ( sizeof( $uploaded_files ) >= 1 ) {
				return true;
			}

			return false;
		} // END entry_has_files

		function entry_download_attachments_button( $form_id, $entry ) {
			if ( ! $this->entry_has_files( $form_id, $entry ) ) {
				return;
			}

			$form = GFAPI::get_form( $form_id, true );

			$fileupload_fields = GFCommon::get_fields_by_type( $form, array( 'itsg_single_ajax' , 'list' ) );

			if ( ! is_array( $fileupload_fields ) ) {
				return;
			}

			$uploaded_files = (array) $this->get_uploaded_files( $fileupload_fields, $entry );

			if ( sizeof( $uploaded_files ) >= 1 ) {
				printf( '<h4>%s</h4>',
					esc_html__( 'Uploaded File(s)', 'ajax-upload-for-gravity-forms' )
				);

				$url =  $this->get_zip_url( $entry );
				if ( !is_Null( $url) ) {
					printf( '%s<br><br>',
						sprintf( "<a href='{$url}' class='button'>%s</a>",
							esc_html__( 'Download ZIP', 'ajax-upload-for-gravity-forms' )
						)
					);
				}

				foreach ( $uploaded_files as $field_id => $uploaded_file ) {
					foreach ( $form['fields'] as $field ) {
						$links = array();
						if ( $field_id == $field->id ) {
							$field_label = $field->label;

							if( 0 == strlen( $field_label ) ) {
								$field_label = __( 'Field', 'ajax-upload-for-gravity-forms' ) . '-' . $field_id;
							}

							foreach ( $uploaded_file as $file_url ) {
								if ( GFCommon::is_valid_url( $file_url ) ) {
									$file_name = pathinfo( $file_url, PATHINFO_BASENAME );  // get the file name out of the URL
									$file_name = parse_url( $file_name );
									$file_name = $file_name['path'];
									$file_name_decode = rawurldecode( $file_name );  // decode the URL - remove %20 etc

									$file_url = $this->get_download_url( $file_url, false, $field );

									$links[] = "<li><a href='{$file_url}' target='_blank' >{$file_name_decode}</a></li>";
								}

							}
							if ( sizeof( $links ) >= 1 ) {
								printf( '<em>%s</em><ul style="overflow-wrap: break-word;">%s</ul><br>', esc_html( $field_label ), implode ( $links ) );
							}
						}
					}

				}
			}
		} // END entry_download_attachments_button

		public function process_zip_download() {
			/* exit early if all the required URL parameters aren't met */

			$form_id = (int) rgget( 'form-id');
			$entry_id = (int) rgget( 'entry-id');

			if ( empty( $form_id ) || empty( $entry_id ) ) {
				GF_Download::die_404();
			}

			$form = GFAPI::get_form( $form_id, true );
			$entry = GFAPI::get_entry( $entry_id, true );

			$fileupload_fields = GFCommon::get_fields_by_type( $form, array( 'itsg_single_ajax' , 'list' ) );

			if ( ! is_array( $fileupload_fields ) ) {
				return;
			}

			$uploaded_files = (array) $this->get_uploaded_files( $fileupload_fields, $entry );

			if ( sizeof( $uploaded_files ) >= 1 ) {

				$zip_file = $this->create_zip_file( $form, $entry_id, $uploaded_files, $notification = null );

				$delete_after_download = true;

				self::deliver_file( $zip_file, $delete_after_download );

			}
		} // END process_zip_download

		function create_zip_file( $form, $entry_id, $uploaded_files, $notification ) {
			$form_id = $form['id'];
			$entry = GFAPI::get_entry( $entry_id );
			$upload_root = RGFormsModel::get_upload_root();
			$zip_file_name = $this->get_zip_file_name( $form_id, $entry_id, $form, $entry, $notification );

			$zip = new ZipArchive();
			$zip_file = $upload_root . '/'. $zip_file_name;
			$target = GFFormsModel::get_file_upload_path( $form_id, null );
			$target_path = pathinfo( $target['path'] );
			$zip->open( $zip_file, ZipArchive::CREATE );

			foreach ( $uploaded_files as $field_id => $uploaded_file ) {
				foreach ( $form['fields'] as $field ) {
					$links = array();
					if ( $field_id == $field->id ) {
						$field_label = $field->label;
						// remove non-english characters
						$field_label = preg_replace( '/[^A-Za-z0-9 ]/', '', $field_label );

						// check that field_label still has name and hasn't been stripped otherwise assign one
						if( 0 == strlen( $field_label ) ) {
							$field_label = __( 'Field', 'ajax-upload-for-gravity-forms' );
						}

						// now make sure the field_label is not more than 30 characters
						$field_label = substr( $field_label, 0, 29 );

						// prefix directory with field number to ensure they are unique
						$field_label = $field_id . ' - ' . $field_label;

						foreach ( $uploaded_file as $file_url ) {
							if ( GFCommon::is_valid_url( $file_url ) ) {
								$file_name = pathinfo( $file_url, PATHINFO_BASENAME );  // get the file name out of the URL
								$file_name = parse_url( $file_name );
								$file_name = $file_name['path'];
								$file_name_decode = rawurldecode( $file_name );  // decode the URL - remove %20 etc
								//$attachment = $target_path['dirname'] . '/' . $file_name_decode;
								$attachment = $_SERVER['DOCUMENT_ROOT'] . parse_url( rawurldecode( $file_url ), PHP_URL_PATH );
								$upload_dir = wp_upload_dir();

								$file_in_upload_dir = substr( $upload_dir['basedir'], 0, strlen( $attachment ) ) === $upload_dir['basedir'];

								if ( is_file( $attachment ) && $file_in_upload_dir ) {
									$new_filename = substr( $attachment, strrpos( $attachment, '/' ) + 1 );
									$zip->addFile( $attachment, $field_label . '/' . $new_filename );
								} elseif ( GFCommon::is_valid_url( $file_url ) && $this->is_url_exist( $file_url ) ) {
									$zip->addFromString( $field_label . '/' . $file_name_decode, file_get_contents( $file_url ) );
								}
							}
						}
					}
				}
			}
			$zip->close();

			return $zip_file;
		}

		public static function deliver_file( $file, $delete_after_download = false ) {
			if ( headers_sent() ) {
				echo 'HTTP header already sent';
			} else {
				if ( !is_file( $file ) ) {
					header( $_SERVER['SERVER_PROTOCOL'].' 404 Not Found' );
					echo 'File not found';
				} else if ( ! is_readable( $file ) ) {
					header( $_SERVER['SERVER_PROTOCOL'].' 403 Forbidden' );
					echo 'File not readable';
				} else {
					// disable PHP error messages - if present the file becomes corrupted
					error_reporting(0);
					ini_set( 'display_errors', 0 );

					$content_type = mime_content_type( $file );
					$content_disposition = rgget( 'dl' ) ? 'attachment' : 'inline';

					nocache_headers();
					header( 'Robots: none' );
					header( 'Content-Type: ' . $content_type );
					header( 'Content-Description: File Transfer' );
					header( 'Content-Disposition: ' . $content_disposition . '; filename="' . basename( $file ) . '"' );
					header( 'Content-Transfer-Encoding: binary' );
					header( 'Content-Length: ' . filesize( $file ) );
					self::readfile_chunked( $file );

					if ( $delete_after_download && is_file( $file ) ) {
						unlink( $file );
					}
				}
			}
			die();
		} // END deliver_file

		/**
		 * Reads file in chunks so big downloads are possible without changing PHP.INI
		 * See https://github.com/bcit-ci/CodeIgniter/wiki/Download-helper-for-large-files
		 *
		 * @access   public
		 * @param    string  $file      The file
		 * @param    boolean $retbytes  Return the bytes of file
		 * @return   bool|string        If string, $status || $cnt
		 */
		private static function readfile_chunked( $file, $retbytes = true ) {

			$chunksize = 1024 * 1024;
			$buffer    = '';
			$cnt       = 0;
			$handle    = @fopen( $file, 'r' );

			if ( $size = @filesize( $file ) ) {
				header("Content-Length: " . $size );
			}

			if ( false === $handle ) {
				return false;
			}

			while ( ! @feof( $handle ) ) {
				$buffer = @fread( $handle, $chunksize );
				echo $buffer;

				if ( $retbytes ) {
					$cnt += strlen( $buffer );
				}
			}

			$status = @fclose( $handle );

			if ( $retbytes && $status ) {
				return $cnt;
			}

			return $status;
		} // END readfile_chunked

		function notification_setting( $ui_settings, $notification, $form ) {
			if ( 'form_saved' != rgar( $notification, 'event' ) && 'form_save_email_requested' != rgar( $notification, 'event' ) ) {
				$setting_title = __( 'Ajax Upload', 'ajax-upload-for-gravity-forms' );
				$setting_option_include = __( 'Include uploads in notification', 'ajax-upload-for-gravity-forms' );
				$setting_option_include_filename = __( 'Zip file name', 'ajax-upload-for-gravity-forms' );
				$setting_option_delete = __( 'Delete files after notification sent', 'ajax-upload-for-gravity-forms' );

				$value = empty( $notification['itsg_ajaxupload_include_files_email'] ) ? '' : "checked='checked'";
				$ui_settings['itsg_ajaxupload_include_files_email'] = '
					<tr>
						<th scope="row">
							<label for="itsg_ajaxupload_include_files_email">'. $setting_title .'</label>
						</th>
						<td>
							<input id="itsg_ajaxupload_include_files_email" type="checkbox" '. $value .' name="itsg_ajaxupload_include_files_email">
							<label class="inline" for="itsg_ajaxupload_include_files_email">'. $setting_option_include .'</label>
						</td>
					</tr>';
				$value = empty( $notification['itsg_ajaxupload_delete_files_submit'] ) ? '' : "checked='checked'";
				$ui_settings['itsg_ajaxupload_delete_files_submit'] = '
					<tr>
						<th scope="row"></th>
						<td>
							<input id="itsg_ajaxupload_delete_files_submit" type="checkbox" '. $value .' name="itsg_ajaxupload_delete_files_submit">
							<label class="inline" for="itsg_ajaxupload_delete_files_submit">'. $setting_option_delete .'</label>
						</td>
					</tr>';
				$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();
				$zip_file_name = rgar( $ajax_upload_options, 'zip_file_name' );
				$value = empty( $notification['itsg_ajaxupload_include_files_email_filename'] ) ? $zip_file_name : $notification['itsg_ajaxupload_include_files_email_filename'];
				$ui_settings['itsg_ajaxupload_include_files_email_filename'] = '
					<tr>
						<th scope="row"></th>
						<td><label class="inline" for="itsg_ajaxupload_include_files_email_filename" style="font-weight:600;" >'. $setting_option_include_filename .'</label><br>
							<input id="itsg_ajaxupload_include_files_email_filename" class="merge-tag-support mt-hide_all_fields fieldwidth-2" type="text" value="'. $value .'" name="itsg_ajaxupload_include_files_email_filename">
						</td>
					</tr>';
			}

			return $ui_settings;
		} // END notification_setting

		function notification_save( $notification, $form ) {
			$notification['itsg_ajaxupload_include_files_email'] = rgpost( 'itsg_ajaxupload_include_files_email' );
			$notification['itsg_ajaxupload_delete_files_submit'] = rgpost( 'itsg_ajaxupload_delete_files_submit' );
			$notification['itsg_ajaxupload_include_files_email_filename'] = rgpost( 'itsg_ajaxupload_include_files_email_filename' );
			return $notification;
		} // END notification_save

		public function configure_woocommerce_gforms_strip_meta_html( $strip_html, $display_value, $field, $lead, $form_meta ) {
			if ( $this->is_ajaxupload_field( $field ) ) {
				$strip_html = false;
			}
			return $strip_html;
		} // END configure_woocommerce_gforms_strip_meta_html

		public static function get_thumbnail_url( $file_path, $field ) {
			$file_name = pathinfo( $file_path, PATHINFO_BASENAME );  // get the file name out of the URL

			$file_path = self::get_download_url( $file_path, false, $field );

			$url_thumb = str_replace( $file_name, 'thumbnail/' . $file_name, $file_path );
			if ( self::is_url_exist( $url_thumb ) ) {
				return $url_thumb;
			}
			return $file_path;
		} // END get_thumbnail_url

		public static function is_url_exist( $url ){
			$ch = curl_init( $url );
			curl_setopt( $ch, CURLOPT_NOBODY, true );
			curl_exec( $ch );
			$code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			if( 200 == $code || 302 == $code ){
				$status = true;
			} else {
				$status = false;
			}

			curl_close( $ch );

			return $status;
		} // END is_url_exist

		public static function itsg_ajaxupload_upload_file() {
			// get form_id from post request
			$form_id = isset( $_POST['form_id'] ) ? $_POST['form_id'] : null;
			$field_id = isset( $_POST['field_id'] ) ? $_POST['field_id'] : null;

			// get target path - also responsible for creating directories if path doesnt exist
			$target = GFFormsModel::get_file_upload_path( $form_id, null );
			$target_path = pathinfo( $target['path'] );
			$target_url = pathinfo( $target['url'] );

			// get Ajax Upload options
			$ajax_upload_options = apply_filters( 'itsg_gf_ajaxupload_options', ITSG_GF_AjaxUpload::get_options(), $form_id, $field_id );

			// calculate file size in KB from MB
			$file_size = $ajax_upload_options['filesize'];
			$file_size_kb = $file_size * 1024 * 1024;

			// push options to upload handler
			$options = array(
				'print_response' => false,
				'upload_dir' => $target_path['dirname'].'/',
				'upload_url' => $target_url['dirname'].'/',
				'image_versions' => array(
					'' => array(
						'max_width' => $ajax_upload_options['filewidth'],
						'max_height' => $ajax_upload_options['fileheight'],
						'jpeg_quality' => $ajax_upload_options['filejpegquality'],
						'auto_orient' => !$ajax_upload_options['auto_orient']
						)
				),
				'accept_file_types' => '/(\.|\/)('.$ajax_upload_options['filetype'].')$/i',
				'max_file_size' => $file_size_kb
			);

			if( rgar( $ajax_upload_options, 'thumbnail_enable' ) ) {
				$options['image_versions']['thumbnail'] = array(
					'max_width' => $ajax_upload_options['thumbnail_width'],
					'max_height' => $ajax_upload_options['thumbnail_height'],
					'crop' => empty( $ajax_upload_options['thumbnail_crop'] ) ? false : true
				);
			}

			// initialise the upload handler and pass the options
			$upload_handler = new ITSG_AjaxUpload_UploadHandler( $options );

			if ( $ajax_upload_options['import_media_library'] ) {
				$upload_hander_response = get_object_vars( $upload_handler );
				$upload_file_name = get_object_vars( $upload_hander_response['response']['files'][0] );
				$upload_file_path = $target_path['dirname'].'/';

				$media_srv_path = $upload_file_path . $upload_file_name['name'];

				$file_type = wp_check_filetype( basename( $media_srv_path ), null );

				// Prepare an array of post data for the attachment.
				$attachment = array(
					'guid' => $target_url['dirname'] . '/' . $upload_file_name['name'],
					'post_mime_type' => $file_type['type'],
					'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $media_srv_path ) ),
					'post_content' => '',
					'post_status' => 'inherit'
				);

				// Insert the attachment into the database/media library
				$attach_id = wp_insert_attachment( $attachment, $media_srv_path, 0 );

				// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it
				require_once( ABSPATH . 'wp-admin/includes/image.php' );

				// Generate the metadata for the attachment, and update the database record
				$attach_data = wp_generate_attachment_metadata($attach_id, $media_srv_path);
				wp_update_attachment_metadata($attach_id, $attach_data);
			}

			$upload_hander_response = get_object_vars( $upload_handler );
			$upload_file_name = get_object_vars( $upload_hander_response['response']['files'][0] );
			$upload_file_path = $target_path['dirname'].'/';
			$media_srv_path = $upload_file_path . $upload_file_name['name'];

			gf_do_action( array( 'itsg_gf_ajaxupload_post_upload', $form_id ), $media_srv_path );

			// terminate the function
			$upload_hander_response = get_object_vars( $upload_handler );
			$upload_file['files'] =  $upload_hander_response['response']['files'];

			$upload_file = apply_filters( 'itsg_gf_ajaxupload_response', $upload_file, $form_id, $field_id );

			die( json_encode( $upload_file ) );
		} // END itsg_ajaxupload_upload_file

		function notification_attachments( $notification, $form, $entry ) {
			if( rgar( $notification, 'isActive') && rgar( $notification, 'itsg_ajaxupload_include_files_email') ) {
				$form_id = $form['id'];
				$entry_id = $entry['id'];

				$fileupload_fields = GFCommon::get_fields_by_type( $form, array( 'itsg_single_ajax' , 'list' ) );

				if ( ! is_array( $fileupload_fields ) ) {
					return $notification;
				}

				$attachments = array();
				$uploaded_files = (array) $this->get_uploaded_files( $fileupload_fields, $entry );

				if ( sizeof( $uploaded_files ) >= 1 ) {

					if ( class_exists( 'ZipArchive' ) ) {
						$zip_file = $this->create_zip_file( $form, $entry_id, $uploaded_files, $notification );
						$attachments[] = $zip_file;
					} else {

						$target = GFFormsModel::get_file_upload_path( $form_id, null );
						$target_path = pathinfo( $target['path'] );
						foreach ( $uploaded_files as $field_id => $uploaded_file ) {
							foreach ( $uploaded_file as $file_url ) {
								if ( GFCommon::is_valid_url( $file_url ) ) {
									$file_name = pathinfo( $file_url, PATHINFO_BASENAME );  // get the file name
									$file_name = parse_url( $file_name );
									$file_name = $file_name['path'];
									$file_name_decode = rawurldecode( $file_name );  // decode the URL - remove %20 etc
									$attachment = $target_path['dirname'] . '/' . $file_name_decode;
									$attachments[] = $attachment;
								}
							}
						}
					}
				}

				if ( $notification['itsg_ajaxupload_delete_files_submit'] ) {
					$target = GFFormsModel::get_file_upload_path( $form_id, null );
					$target_path = pathinfo( $target['path'] );

					foreach ( $uploaded_files as $field_id => $uploaded_file ) {
						foreach ( $uploaded_file as $file_url ) {
							if ( GFCommon::is_valid_url( $file_url ) ) {
								$file_name = pathinfo( $file_url, PATHINFO_BASENAME );  // get the file name
								$file_name = parse_url( $file_name );
								$file_name = $file_name['path'];
								$file_name_decode = rawurldecode( $file_name );  // decode the URL - remove %20 etc
								$attachment = $target_path['dirname'] . '/' . $file_name_decode;
								if ( is_file( $attachment ) ) {
									unlink( $attachment );
								}
								$file_path_thumb = $target_path['dirname'] . '/thumbnail/' . $file_name_decode;
								if ( is_file( $file_path_thumb ) ) {
									unlink( $file_path_thumb );
								}
							}
						}
					}
				}

				add_filter( 'gform_confirmation', array( $this, 'remove_zip_attachment' ), 10, 4 );

				$notification['attachments'] = $attachments;
			}

			return $notification;
		} // END notification_attachments

		function remove_zip_attachment( $confirmation, $form, $entry, $ajax ) {
			foreach ( $form['notifications'] as $notification ) {
				if( rgar( $notification, 'isActive') && rgar( $notification, 'itsg_ajaxupload_include_files_email') ) {
					$form_id = $form['id'];
					$entry_id = $entry['id'];

					$upload_root = RGFormsModel::get_upload_root();

					$zip_file_name = $this->get_zip_file_name( $form_id, $entry_id, $form, $entry, $notification );

					$zip_file = $upload_root . '/'. $zip_file_name;

					if ( is_file( $zip_file ) ) {
						unlink( $zip_file );
					}
				}
			}
			return $confirmation;
		} // END remove_zip_attachment

		function get_uploaded_files( $fileupload_fields, $entry ) {
			$uploaded_files = array();
			foreach( $fileupload_fields as $field ) {
				$uploaded_file = array();
				if ( 'list' == $field->get_input_type() ) {
					$has_columns = is_array( $field->choices );
						if ( $has_columns ) {
							$number_of_columns = sizeof( $field->choices );
							$column_number = 0;
							$value = unserialize( $entry[ $field->id ] );
							if ( is_array( $value ) ) {
								foreach( $value as $row_number => $row_array ) {
									foreach( $row_array as $column_value ) {
										if ( true  == rgar( $field['choices'][ $column_number ], 'isAjaxUpload' ) )  {
											if ( ! empty( $column_value ) && GFCommon::is_valid_url( $column_value ) ) {
												$uploaded_file[] = $column_value;

											}
										}
										if ( $column_number >= ( $number_of_columns - 1 ) ) {
											$column_number = 0; // reset column number
										} else {
											$column_number = $column_number + 1; // increment column number
										}
									}
								}
								if ( sizeof( $uploaded_file ) >= 1 ) {
									$uploaded_files[ $field->id ] = $uploaded_file;
								}
							}
						} elseif ( $field->itsg_list_field_ajaxupload ) {
							$value = unserialize( $entry[ $field->id ] );
							if ( is_array( $value ) ) {
								foreach( $value as $key => $column_value ) {
									$value = $column_value;
									if ( ! empty( $value ) && GFCommon::is_valid_url( $value ) ) {
										$uploaded_file[] = $value;
									}
								}
								if ( sizeof( $uploaded_file ) >= 1 ) {
									$uploaded_files[ $field->id ] = $uploaded_file;
								}
							}
						}
				} elseif ( 'itsg_single_ajax' == $field->get_input_type() ) {
					$value = $entry[ $field->id ];
					if ( ! empty( $value ) && GFCommon::is_valid_url( $value ) ) {
						$uploaded_file[] = $value;
					}
					if ( sizeof( $uploaded_file ) >= 1 ) {
						$uploaded_files[ $field->id ] = $uploaded_file;
					}
				}
			}
			return $uploaded_files;
		} // END get_uploaded_files

		function get_zip_file_name( $form_id, $entry_id, $form, $entry, $notification ) {
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();
			$nofification_file_name = rgar( $notification, 'itsg_ajaxupload_include_files_email_filename' );
			$zip_file_name = $nofification_file_name ? $nofification_file_name : rgar( $ajax_upload_options, 'zip_file_name' );

			// check for tags
			$zip_file_name = str_replace( '{all_fields}', '', $zip_file_name );

			$zip_file_name = GFCommon::replace_variables( $zip_file_name, $form, $entry, false, false, false );
			// if {form_id} keyword used, replace with current form id
			//if ( false !== strpos( $zip_file_name,'{form_id}' ) ) {
			//	$zip_file_name = str_replace( '{form_id}', $form_id, $zip_file_name );
			//}

			// if {entry_id} keyword used, replace with current form id
			//if ( false !== strpos( $zip_file_name,'{entry_id}' ) ) {
			//	$zip_file_name = str_replace( '{entry_id}', $entry_id, $zip_file_name );
			//}

			// if {timestamp} keyword used, replace with current form id
			if ( false !== strpos( $zip_file_name,'{timestamp}' ) ) {
				$zip_file_name = str_replace( '{timestamp}', time(), $zip_file_name );
			}

			// make sure file name doesn't include .zip - this is added below
			$zip_file_name = str_replace( '.zip', '', $zip_file_name );

			// Decode HTML entities
			$zip_file_name = wp_specialchars_decode( $zip_file_name, ENT_QUOTES );

			// Remove any characters that cannot be present in a filename
			$characters = array( '/', '\\', '"', '*', '?', '|', ':', '<', '>' );
			$zip_file_name =  str_replace( $characters, '', $zip_file_name );

			// clean file name of any unsupported characters
			$zip_file_name = mb_ereg_replace( '([^\w\s\d\-_~,;\[\]\(\).])', '', $zip_file_name );

			$zip_file_name = trim( $zip_file_name );

			if ( empty( $zip_file_name ) ) {
				$zip_file_name = $entry_id;
			}

			return $zip_file_name . '.zip';
		} // END get_zip_file_name

		/*
		 *   Handles the plugin options.
		 *   Default values are stored in an array.
		 */
		public static function get_options() {
			$defaults = array(
				'filesize' => '2',
				'filetype' => 'pdf|png|tif|jpeg|jpg|gif|doc|docx|rtf|txt|csv|xls|xlsx|mp4|mp3|m4a|ppt|pptx',
				'filedir' => '/gravity_forms/{form_id}-{hashed_form_id}/{month}/{year}/',
				'includecss' => true,
				'filejpegquality' => '75',
				'filewidth' => '99999',
				'fileheight' => '99999',
				'allowdelete' => true,
				'allownoprivdelete' => false,
				'wrapsinglefieldset' => true,
				'thumbnail_enable' => true,
				'thumbnail_crop' => false,
				'thumbnail_width' => 200,
				'thumbnail_height' => 200,
				'displayscripterrors' => true,
				'gpdf_use_serverpath' => false,
				'gpdf_use_thumbnails' => true,
				'import_media_library' => false,
				'exclude_special_characters' => true,
				'file_chunk_size' => 0,
				'zip_file_name' => 'uploaded_files_{entry_id}.zip',
				'text_uploading' => __( 'Uploading', 'ajax-upload-for-gravity-forms' ),
				'text_remove' => __( 'Remove', 'ajax-upload-for-gravity-forms' ),
				'text_cancel' => __( 'Cancel', 'ajax-upload-for-gravity-forms' ),
				'auto_orient' => false,
			);
			$options = wp_parse_args( get_option( 'gravityformsaddon_gfajaxfileupload_settings' ), $defaults );
			return $options;
		} // END get_options

		/*
		 *   Handles the Ajax delete operation.
		 *   Calls GFFormsModel::get_file_upload_path to get the current directory structure to determine the file location.
		 */
		function itsg_ajaxupload_delete_file() {

			// get Ajax Upload options
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();

			$file_name = isset( $_POST['file_name'] ) ? $_POST['file_name'] : null;
			$form_id = isset( $_POST['form_id'] ) ? $_POST['form_id'] : null;
			$gf_upload_folder = GFFormsModel::get_file_upload_path( $form_id, $file_name );
			$gf_upload_folder_path = pathinfo( $gf_upload_folder['path'] );
			$file_path = $gf_upload_folder_path['dirname'] . '/' . $file_name;
			$file_path_thumb = $gf_upload_folder_path['dirname'] . '/thumbnail/' . $file_name;
			$upload_folder = $ajax_upload_options['filedir'];

			// if upload dir setting contains either the {user_id} or {hashed_user_id} keywords
			if ( false !== strpos( $upload_folder, '{user_id}' ) || false !== strpos( $upload_folder, '{hased_user_id}' ) ) {
				// if user is not logged in
				if ( !is_user_logged_in() ) {
					die( 'Authentication error - must be logged in to delete uploaded file.' );
				}
			}
			if( is_file( $file_path ) ) {
				if ( !unlink( $file_path ) ) {
					die( "Error deleting {$file_name}" );
				} else {
					if( is_file( $file_path_thumb ) ){
						unlink( $file_path_thumb );
					}
					die( "Deleted {$file_name}" );
				}
			} else {
				die( "File does not exist {$file_name}" );
			}
		}  // END itsg_ajaxupload_delete_file

		/*
		 *   Changes the upload path for Gravity Form uploads.
		 *   Changes made by this function will be seen when the Gravity Forms function  GFFormsModel::get_file_upload_path() is called.
		 *   The default upload path applied by this function matches the default for Gravity forms:
		 *   /gravity_forms/{form_id}-{hashed_form_id}/{month}/{year}/
		 */
		function change_upload_path( $path_info, $form_id ){
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();
			$file_dir = $ajax_upload_options['filedir'];

			if ( 0 != strlen( $file_dir ) ) {
				// Generate the yearly and monthly dirs
				$time            = current_time( 'mysql' );
				$y               = substr( $time, 0, 4 );
				$m               = substr( $time, 5, 2 );

				// removing leading forward slash, if present
				if( '/' == $file_dir[0] ) {
					$file_dir = ltrim( $file_dir, '/' );
				}

				// remove trailing forward slash, if present
				$file_dir = trailingslashit( $file_dir );

				// if {form_id} keyword used, replace with current form id
				if ( false !== strpos( $file_dir,'{form_id}' ) ) {
					$file_dir = str_replace( '{form_id}', $form_id, $file_dir );
				}

				// if {hashed_form_id} keyword used, replace with hashed current form id
				if ( false !== strpos( $file_dir, '{hashed_form_id}' ) ) {
					$file_dir = str_replace( '{hashed_form_id}', wp_hash( $form_id ), $file_dir );
				}

				// if {year} keyword used, replace with current year
				if ( false !== strpos($file_dir, '{year}' ) ) {
					$file_dir = str_replace( '{year}', $y, $file_dir );
				}

				// if {month} keyword used, replace with current month
				if ( false !== strpos($file_dir, '{month}' ) ) {
					$file_dir = str_replace("{month}",$m,$file_dir);
				}

				// if {user_id} keyword used, replace with current user id
				if ( false !== strpos( $file_dir,'{user_id}' ) ) {
					if ( isset( $_POST['entry_user_id'] ) ) {
						$entry_user_id = (int)$_POST['entry_user_id'];
						$file_dir = str_replace( '{user_id}', $entry_user_id, $file_dir );
					} else {
						$user_id = get_current_user_id() ? get_current_user_id() : '0';
						$file_dir = str_replace( '{user_id}', $user_id, $file_dir );
					}
				}

				// if {user_login} keyword used, replace with username
				if ( false !== strpos( $file_dir,'{user_login}' ) ) {
					if ( isset( $_POST['entry_user_id'] ) ) {
						$entry_user_id = (int)$_POST['entry_user_id'];
						$entry_user = get_user_by( 'id', $entry_user_id );
						$entry_login = is_object( $entry_user ) ? $entry_user->user_login : '0';
						$file_dir = str_replace( '{user_login}', $entry_login, $file_dir );
					} else {
						$current_user = wp_get_current_user();
						$user_login = get_current_user_id() ? $current_user->user_login : '0';
						$file_dir = str_replace( '{user_login}', $user_login, $file_dir );
					}
				}

				// if {hashed_user_id} keyword used, replace with hashed current user id
				if ( false !== strpos( $file_dir, '{hashed_user_id}' ) ) {
					if ( isset( $_POST['entry_user_id'] ) && 'null' !== rgar( $_POST, 'entry_user_id' ) ) {
						$entry_user_id = (int)$_POST['entry_user_id'];
						$hashed_entry_user_id = wp_hash( $entry_user_id );
						$file_dir = str_replace( '{hashed_user_id}', $hashed_entry_user_id, $file_dir );
					} else {
						$hashed_user_id = wp_hash( is_user_logged_in() ? get_current_user_id() : '0');
						$file_dir = str_replace( '{hashed_user_id}', $hashed_user_id, $file_dir );
					}
				}

				$file_dir = $this->clean_url( $file_dir ); // clean path

				$upload_dir = wp_upload_dir(); // get WordPress upload directory information - returns an array

				$path_info['path'] = $upload_dir['basedir'] . '/' . $file_dir;  // set the upload path
				$path_info['url'] = $upload_dir['baseurl'] . '/' . $file_dir;  // set the upload URL
			}
			return $path_info;
		} // END change_upload_path

		function clean_url( $url ) {
			$url = preg_replace( '([^\w\s\d\-\/_~,;\[\]\(\).])', '', $url );
			$url = preg_replace( '([\.]{2,})', '', $url );
			return $url;
		}

		/*
		 *   Converts php.ini memory limit string to bytes.
		 *   For example, 2MB would convert to 2097152
		 */
		public static function return_bytes( $val ) {
			$val = trim( $val );
			$last = strtolower( $val[strlen( $val )-1] );

			$val = intval( $val );

			switch( $last ) {
				case 'g':
					$val *= 1024;
				case 'm':
					$val *= 1024;
				case 'k':
					$val *= 1024;
			}
			return $val;
		} // END return_bytes

		/*
		 *   Determines the maximum upload file size.
		 *   Retrieves three values from php.ini and returns the smallest.
		 */
		public static function max_file_upload_in_bytes() {
			//select maximum upload size
			$max_upload = ITSG_GF_AjaxUpload::return_bytes( ini_get( 'upload_max_filesize' ) );
			//select post limit
			$max_post = ITSG_GF_AjaxUpload::return_bytes( ini_get( 'post_max_size' ) );
			//select memory limit
			//$memory_limit = ITSG_GF_AjaxUpload::return_bytes( ini_get( 'memory_limit' ) );
			// return the smallest of them, this defines the real limit
			return min( $max_upload, $max_post );
		} // END max_file_upload_in_bytes

		/*
         * Warning message if Gravity Forms is installed and enabled
         */
		public static function admin_warnings() {
			if ( ! self::is_gravityforms_installed() ) {
				printf(
					'<div class="error"><h3>%s</h3><p>%s</p><p>%s</p></div>',
						__( 'Warning', 'ajax-upload-for-gravity-forms' ),
						sprintf ( __( 'The plugin %s requires Gravity Forms to be installed.', 'ajax-upload-for-gravity-forms' ), '<strong>'. self::$name .'</strong>' ),
						sprintf ( esc_html__( 'Please %sdownload the latest version of Gravity Forms%s and try again.', 'ajax-upload-for-gravity-forms' ), '<a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=299380" target="_blank">', '</a>' )
				);
			}
			if ( ! version_compare( phpversion(), '5.3', '>=' ) ) {
				printf(
					'<div class="error"><h3>%s</h3><p>%s</p><p>%s</p></div>',
						__( 'Warning', 'ajax-upload-for-gravity-forms' ),
						sprintf ( __( 'The plugin %s requires PHP version 5.3 or higher.', 'ajax-upload-for-gravity-forms' ), '<strong>'. self::$name .'</strong>' ),
						sprintf( __( 'You are running an PHP version %s. Contact your web hosting provider to update.', 'ajax-upload-for-gravity-forms' ), phpversion() )
				);
			}
		} // END admin_warnings

		/*
         * Check if GF is installed
         */
        private static function is_gravityforms_installed() {
			return class_exists( 'GFCommon' );
        } // END is_gravityforms_installed

		/*
         * Add 'Settings' link to plugin in WordPress installed plugins page
         */
		function plugin_action_links( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=gf_settings&subview=gfajaxfileupload' ) . '">' . __( 'Settings', 'ajax-upload-for-gravity-forms' ) . '</a>',
			);

			return array_merge( $action_links, $links );
		} // END plugin_action_links

		/*
         * Checks if field is Ajax Upload enabled - single or list field
         */
		public function is_ajaxupload_field( $field ) {
			$field_type = $field->get_input_type();
			if ( 'itsg_single_ajax' == $field_type ) {
				return true;
			} elseif ( 'list' == $field_type ) {
				$has_columns = is_array( $field->choices );
				if ( $has_columns ) {
					foreach( $field->choices as $choice ){
						if ( rgar( $choice, 'isAjaxUpload' ) ) {
							return true;
						}
					}
				} elseif ( 'on' == $field->itsg_list_field_ajaxupload ) {
					return true;
				}
			}
			return false;
		} // END is_ajaxupload_field

		/*
         * Get field type
         */
		private function get_type( $field ) {
			$type = '';
			if ( array_key_exists( 'type', $field ) ) {
				$type = $field->type;
				if ( 'post_custom_field' == $type ) {
					if ( array_key_exists( 'inputType', $field ) ) {
						$type = $field['inputType'];
					}
				}
				return $type;
			}
		} // END get_type

		/**
		 * MODIFIED VERSION FROM GF 2.0.3.5 - includes additional parameter for $field
		 * NOTE CURRENT LIMITATION -- only supports default upload directory - /gravity_forms/{form_id}-{hashed_form_id}/{month}/{year}/
		 *
		 * Returns the download URL for a file. The URL is not escaped for output.
		 *
		 * @since 2.0
		 *
		 * @param string $file The complete file URL.
		 * @param bool $force_download Default: false
		 *
		 * @return string
		 */
		public static function get_download_url( $file, $force_download = false, $field ) {
			$download_url = $file;

			$secure_download_location = true;

			/**
			 * By default the real location of the uploaded file will be hidden and the download URL will be generated with a security token to prevent guessing or enumeration attacks to discover the location of other files.
			 *
			 * Return FALSE to display the real location.
			 *
			 * @param bool                $secure_download_location If the secure location should be used.  Defaults to true.
			 * @param string              $file                     The URL of the file.
			 * @param GF_Field_FileUpload $this                     The Field
			 */
			$secure_download_location = apply_filters( 'gform_secure_file_download_location', $secure_download_location, $file, $field );
			$secure_download_location = apply_filters( 'gform_secure_file_download_location_' . $field->formId, $secure_download_location, $file, $field );

			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();

			if ( ! $secure_download_location || true !== rgar( $ajax_upload_options, 'use_gf_secure_download' ) ) {
				return $download_url;
			}

			$upload_root = GFFormsModel::get_upload_url( $field->formId );
			$upload_root = trailingslashit( $upload_root );

			// Only hide the real URL if the location of the file is in the upload root for the form.
			// The upload root is calculated using the WP Salts so if the WP Salts have changed then file can't be located during the download request.
			if ( strpos( $file, $upload_root ) !== false ) {
				$file = str_replace( $upload_root, '', $file );
				$download_url = site_url( 'index.php' );
				$args = array(
					'gf-download' => urlencode( $file ),
					'form-id' => $field->formId,
					'field-id' => $field->id,
					'hash' => ITSG_GF_AjaxUpload::generate_download_hash( $field->formId, $field->id, $file ),
				);
				if ( $force_download ) {
					$args['dl'] = 1;
				}
				$download_url = add_query_arg( $args, $download_url );
			}
			return $download_url;
		} // END get_download_url

		public function get_zip_url( $entry ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				return;
			}

			$form_id = rgar( $entry, 'form_id' );
			$entry_id = rgar( $entry, 'id' );
			$form = GFAPI::get_form( $form_id, true );

			$zip_file_name = $this->get_zip_file_name( $form_id, $entry_id, $form, $entry, $notification = null );
			$target = GFFormsModel::get_file_upload_path( $form_id, $zip_file_name );

			$file = $zip_file_name;
			$download_url = site_url( 'index.php' );
			$args = array(
				'ajaxupload-zip-download' => urlencode( $file ),
				'form-id' => $form_id,
				'entry-id' => $entry_id,
				'hash' => ITSG_GF_AjaxUpload::generate_download_hash( $form_id, $entry_id, $file ),
			);

			$download_url = add_query_arg( $args, $download_url );

			$download_url = wp_nonce_url( $download_url, 'gf_au_zip_'.$entry_id );

			return $download_url;

		} // END get_zip_url

		public function maybe_process_zip_url() {
			if ( isset( $_GET['ajaxupload-zip-download'] ) ) {
				if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'gravityforms_view_entries' ) ) ) {
					GF_Download::die_401();
				}

				$entry_id = rgget( 'entry-id' );

				if ( ! check_admin_referer( 'gf_au_zip_'.$entry_id ) ) {
					GF_Download::die_401();
				}

				$file = $_GET['ajaxupload-zip-download'];
				$form_id = rgget( 'form-id' );

				if ( empty( $file ) || empty( $form_id ) ) {
					return;
				}

				$hash = rgget( 'hash' );

				if ( self::validate_download( $form_id, $entry_id, $file, $hash ) ) {
					$this->process_zip_download( $form_id, $file );
				} else {
					GF_Download::die_401();
				}
			}
		} // END maybe_process_zip_url

		/**
		 * FROM GF 2.0.3.5
		 * Verifies the hash for the download.
		 *
		 * @param int $form_id
		 * @param int $field_id
		 * @param string $file
		 * @param string $hash
		 *
		 * @return bool
		 */
		private static function validate_download( $form_id, $field_id, $file, $hash ) {
			if ( empty( $hash ) ) {
				return false;
			}

			$hash_check = ITSG_GF_AjaxUpload::generate_download_hash( $form_id, $field_id, $file );

			$valid = hash_equals( $hash, $hash_check );

			return $valid;
		} // END validate_download

		/**
		 * FROM GF 2.0.3.5
		 * Generates a hash for a Gravity Forms download.
		 *
		 * May return false if the algorithm is not available.
		 *
		 * @param int $form_id The Form ID.
		 * @param int $field_id The ID of the field used to upload the file.
		 * @param string $file The file url relative to the form's upload folder. E.g. 2016/04/my-file.pdf
		 *
		 * @return string|bool
		 */
		public static function generate_download_hash( $form_id, $field_id, $file ) {

			$key = absint( $form_id ) . ':' . absint( $field_id ) . ':' . urlencode( $file );

			$algo = 'sha256';

			/**
			 * Allows the hash algorithm to be changed when generating the file download hash.
			 *
			 * @param string $algo The algorithm. E.g. "md5", "sha256", "haval160,4", etc
			 */
			$algo  = apply_filters( 'gform_download_hash_algorithm', $algo );

			$hash = hash_hmac( $algo, $key, 'gform_download' . wp_salt() );
			/**
			 * Allows the hash to be modified.
			 *
			 * @param string $hash The hash.
			 * @param int $form_id The Form ID
			 * @param string $file The File path relative to the upload root for the form.
			 */
			$hash  = apply_filters( 'gform_download_hash', $hash, $form_id, $file );

			return $hash;
		} // END generate_download_hash
	}
}
$ITSG_GF_AjaxUpload = new ITSG_GF_AjaxUpload();