<?php
/*
 *   Setup the settings page for configuring the options
 */
if ( class_exists( 'GFForms' ) ) {
	GFForms::include_addon_framework();
	class GFAjaxFileUpload extends GFAddOn {
		protected $_version = '2.7.3';
		protected $_min_gravityforms_version = '1.7.9999';
		protected $_slug = 'gfajaxfileupload';
		protected $_full_path = __FILE__;
		protected $_title = 'Ajax Upload for Gravity Forms';
		protected $_short_title = 'Ajax Upload';

		public function init(){
			parent::init();
			add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2);
        } // END init

		// Add the text in the plugin settings to the bottom of the form if enabled for this form
		function form_submit_button( $button, $form ){
			$settings = $this->get_form_settings( $form );
			if ( rgar( $settings, 'enabled' ) ){
				$text = $this->get_plugin_setting('mytextbox');
				$button = "<div>{$text}</div>" . $button;
			}
			return $button;
		} // END form_submit_button

		// add the options
		public function plugin_settings_fields() {
            return array(
                array(
                    'title'  => __( 'Upload Settings', 'ajax-upload-for-gravity-forms' ),
                    'fields' => array(
                        array(
                            'label'   => __( 'Maximum file size (MB)', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'filesize',
                            'tooltip' => __( 'This is the maximum file size that can be uploaded in megabytes (MB). Note that this cannot be larger than the maximum as defined in your servers configuration.' , 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'filesize',
                            'class'   => 'small'
                        ),
						array(
                            'label'   => __( 'Allowed file types', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'filetype',
                            'tooltip' => __( "Specify the file types that can be uploaded. These need to be the file extension separated with the vertical bar character '|'.", 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'medium',
							'default_value' => 'pdf|png|tif|jpeg|jpg|gif|doc|docx|rtf|txt|csv|xls|xlsx|mp4|mp3|m4a|ppt|pptx'
                        ),
						array(
                           'label'   => __( 'Upload directory', 'ajax-upload-for-gravity-forms' ),
                           'type'    => 'filedir',
                        )
                    )
                ),
				array(
                    'title'  => __( 'Image Processing Settings', 'ajax-upload-for-gravity-forms' ),
                    'fields' => array(
                        array(
                            'label'   => __( 'Reduce image width (px)', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'filewidth',
                            'tooltip' => __( 'Uploaded images can be reduced in size before being saved. This setting allows you to specify the MAXIMUM width for images. If the image will only be changed if it is larger.', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'small'
                        ),
						array(
                            'label'   => __( 'Reduce image height (px)', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'fileheight',
                            'tooltip' => __( 'Uploaded images can be reduced in size before being saved. This setting allows you to specify the MAXIMUM height for images. If the image will only be changed if it is larger.', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'small'
                        ),
						array(
                            'label'   => __( 'JPEG quality', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'filejpegquality',
                            'tooltip' => __( 'Uploaded images are processed before being saved. The JPEG quality controls the amount of compression applied.', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'small',
							'default_value' => '75'
                        ),
						array(
                            'label'   => __( 'Import to media library', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'import_media_library',
                            'tooltip' => __( 'If enabled uploaded media will be added to the WordPress media library.', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'import_media_library',
									'default_value' => false
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Auto orient images', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'auto_orient',
                            'tooltip' => __( 'If enabled images are auto rotated based on EXIF meta data.', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'auto_orient',
									'default_value' => false
                                )
                            )
                        )
                    )
                ),
				array(
                    'title'  => __( 'File Permissions', 'ajax-upload-for-gravity-forms' ),
                    'fields' => array(
						array(
                            'label'   => __( 'Allow logged in users to delete files', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'allowdelete',
                            'tooltip' => __( 'This option allows you to control whether users that ARE LOGGED IN can delete files. Without this option enabled uploaded files will remain on the server when a user removes it from the form.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'allowdelete',
									'default_value' => true
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Allow not logged in users to delete files', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'allownoprivdelete',
                            'tooltip' => __( 'This option allows you to control whether users that ARE NOT LOGGED IN can delete files. Without this option enabled uploaded files will remain on the server when a user removes it from the form.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'allownoprivdelete',
									'default_value' => false
                                )
                            )
                        )
                    )
                ),
				array(
                    'title'  => __( 'Formatting and Styles', 'ajax-upload-for-gravity-forms' ),
                    'fields' => array(
						array(
                            'label'   => __( 'Include CSS styles', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'includecss',
                            'tooltip' => __( 'This option allows you to control whether to use the CSS styles provided in the plugin. If this is not enabled you can apply styles through your theme.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'includecss',
									'default_value' => true
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Wrap single ajax upload field in fieldset', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'wrapsinglefieldset',
                            'tooltip' => __( 'This option allows you to control whether the single ajax upload field is wrapped in a field set. Doing this provides a more accessible upload field for users which rely on screen readers.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'wrapsinglefieldset',
									'default_value' => true
                                )
                            )
                        )
                    )
                ),
				array(
                    'title'  => __( 'Thumbnail settings', 'ajax-upload-for-gravity-forms' ),
                    'fields' => array(
						array(
                            'label'   => __( 'Enable thumbnails', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'thumbnail_enable',
                            'tooltip' => __( 'When enabled thumbnails will automatically be created. If an image is uploaded using either the list field or single upload field the thumbnail is displayed to the user.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'thumbnail_enable',
									'default_value' => true
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Display file name below thumbnail', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'thumbnail_file_name_enable',
                            'tooltip' => __( 'When enabled the file name will be displayed below the thumbnail.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'thumbnail_file_name_enable',
									'default_value' => false
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Crop thumbnails', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'thumbnail_crop',
                            'tooltip' => __( 'When enabled thumbnails will be cropped to exactly the width and height specified below.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'thumbnail_crop',
									'default_value' => false
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Thumbnail width', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'thumbnail_width',
                            'tooltip' => __( 'Width of thumbnail generated.', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'small',
							'default_value' => '200'
                        ),
						array(
                            'label'   => __( 'Thumbnail height', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'thumbnail_height',
                            'tooltip' => __( 'Height of thumbnail generated.', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'small',
							'default_value' => '200'
                        )
                    )
                ),
				array(
                    'title'  => __( 'Notification Settings', 'ajax-upload-for-gravity-forms' ),
                    'fields' => array(
						array(
                            'label'   => __( 'Zip file name', 'ajax-upload-for-gravity-forms' ),
                            'tooltip' => __( 'Name of the Zip file used in notification attachment.', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'zip_file_name',
                        )
                    )
                ),
				 array(
                    'title'  => __( 'Translations', 'ajax-upload-for-gravity-forms' ),
                    'fields' => array(
						array(
                            'label'   => __( 'Uploading', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'text_uploading',
                            'tooltip' => __( "Text used to temporarily replace 'Submit', 'Next page' and 'Previous page' buttons when upload is in progress.", 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'small',
							'default_value' => 'Uploading'
						),
						array(
                            'label'   => __( 'Remove', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'text_remove',
                            'tooltip' => __( "Text used in Ajax Upload field 'Remove' button.", 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'small',
							'default_value' => 'Remove'
						),
						array(
                            'label'   => __( 'Cancel', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'text_cancel',
                            'tooltip' => __( "Text used in Ajax Upload field 'Cancel' button.", 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'small',
							'default_value' => 'Cancel'
						)
                    )
                ),
				 array(
                    'title'  => __( 'Debug settings', 'ajax-upload-for-gravity-forms' ),
                    'fields' => array(
						array(
                            'label'   => __( 'Display script errors to users', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'displayscripterrors',
                            'tooltip' => __( 'With this option enabled script error messages will be displayed to users if they occur.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'displayscripterrors',
									'default_value' => true
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Gravity PDF: display thumbnails', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'gpdf_use_thumbnails',
                            'tooltip' => __( 'With this option enabled thumbnails will be displayed in the PDF.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'gpdf_use_thumbnails',
									'default_value' => true
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Gravity PDF: thumbnails use server path', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'gpdf_use_serverpath',
                            'tooltip' => __( 'With this option enabled thumbnails will be generated using the server path instead of the URL path.', 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'gpdf_use_serverpath',
									'default_value' => false
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Exclude special characters', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'exclude_special_characters',
                            'tooltip' => __( 'If enabled special characters are automatically removed from uploaded file names. This provides best cross-platform compatibility. Characters removed are:<br> \ / : ; * ? ! " ` \' < > { } [ ] , |, #', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'exclude_special_characters',
									'default_value' => true
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Chunk uploads (MB)', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'file_chunk_size',
                            'tooltip' => __( 'To upload large files in smaller chunks, set this option to a preferred maximum chunk size. If set to 0, null or undefined, or the browser does not support the required Blob API, files will be uploaded as a whole.', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'text',
                            'class'   => 'small',
							'default_value' => '0'
						),
						array(
                            'label'   => __( 'Use Gravity Forms Secure Download', 'ajax-upload-for-gravity-forms' ),
							'name'    => 'use_gf_secure_download',
                            'tooltip' => __( "If enabled file URL's are obscured so that the upload directory path is not exposed.", 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'use_gf_secure_download',
									'default_value' => true
                                )
                            )
                        ),
						array(
                            'label'   => __( 'Enable apostrophe/quote in file name error message', 'ajax-upload-for-gravity-forms' ),
                            'type'    => 'checkbox',
                            'name'    => 'apostrophe_error_enable',
                            'tooltip' => __( "Some web servers are configured to not allow file names that contain an apostrophe/quote ('). When enabled if an uploaded file has an apostrophe/quote in the file name an error message will be displayed and the upload will stop.", 'ajax-upload-for-gravity-forms' ),
                            'choices' => array(
                                array(
                                    'label' => __( 'Yes', 'ajax-upload-for-gravity-forms' ),
                                    'name'  => 'apostrophe_error_enable',
									'default_value' => false
                                )
                            )
                        ),
                    )
                )
            );
        } // END plugin_settings_fields

		public function settings_filesize() {
			$server_upload_limit_bytes = ITSG_GF_AjaxUpload::max_file_upload_in_bytes();
			$server_upload_limit_megabytes = $server_upload_limit_bytes / 1024 / 1024;
                $this->settings_text(
                    array(
                         'name'    => 'filesize',
						 'default_value' => '2'
                    )
                );
				printf(
					'<div><p>%s</p></div>',
						sprintf( __( 'Your servers maximum upload file size is currently configured as %s megabytes (MB). The setting above cannot exceed this.', 'ajax-upload-for-gravity-forms' ), $server_upload_limit_megabytes )
				);
        } // END settings_filesize

		public function settings_filedir() {
			// Generate the yearly and monthly dirs
			$time = current_time( 'mysql' );
			$year = substr( $time, 0, 4 );
			$month = substr( $time, 5, 2 );

			$wp_upload_dir = wp_upload_dir();
			$base_dir = $wp_upload_dir['basedir'];

            ?>
            <div>
                <?php echo $base_dir; ?>
            </div>
            <?php
                $this->settings_text(
                    array(
                         'name'    => 'filedir',
						 'class'   => 'large',
                         'default_value' => '/gravity_forms/{form_id}-{hashed_form_id}/{month}/{year}/'
                    )
                );
				?>
            <div>
                <p><?php _e( 'Keywords supported are', 'ajax-upload-for-gravity-forms' ) ?>:
					<br>{form_id} - <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ) ?> '1'
					<br>{hashed_form_id} - <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ) ?>  '<?php echo wp_hash(1);?>'
					<br>{user_login} - <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ) ?>  '<?php echo wp_get_current_user()->user_login;?>' (<?php _e( "note - if no user is logged in, this will be '0'", 'ajax-upload-for-gravity-forms' ) ?>)
					<br>{user_id} - <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ) ?>  '<?php echo get_current_user_id();?>' (<?php _e( "note - if no user is logged in, this will be '0'", 'ajax-upload-for-gravity-forms' ) ?>)
					<br>{hashed_user_id} - <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ) ?>  '<?php echo wp_hash(get_current_user_id());?>' (<?php _e( "note - if no user is logged in, this will be", 'ajax-upload-for-gravity-forms' ) ?> '<?php echo wp_hash(0);?>')
					<br>{year} - <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ) ?>  '<?php echo $year;?>'
					<br>{month}	- <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ) ?>  '<?php echo $month;?>'
				</p>
				<p><?php _e( 'If you set this field to', 'ajax-upload-for-gravity-forms' ) ?>
					<br><strong>/gravity_forms/{form_id}-{hashed_form_id}/{year}/{month}/</strong>
					<br><?php _e( 'Files will be uploaded to', 'ajax-upload-for-gravity-forms' ) ?>
					<br><strong><?php echo $base_dir . '/gravity_forms/1-' . wp_hash(1) .'/' . $year . '/' . $month ; ?></strong>
				</p>
            </div>
            <?php
        } // END settings_filedir

		public function settings_zip_file_name() {
			 $this->settings_text(
                    array(
                         'name'    => 'zip_file_name',
						 'class'   => 'medium',
                         'default_value' => 'uploaded_files_{entry_id}.zip'
                    )
                );
				?>
            <div>
                <p><?php _e( 'Keywords supported are', 'ajax-upload-for-gravity-forms' ) ?>:
					<br>{form_id} - <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ) ?> '1'
					<br>{entry_id} - <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ) ?> '1'
					<br>{timestamp} - <?php _e( 'for example', 'ajax-upload-for-gravity-forms' ); echo " '". time() . "'";?>
           </div>
            <?php
        } // END settings_zip_file_name

		public function scripts() {
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) || rgar( $ajax_upload_options, 'displayscripterrors' ) ? '' : '.min';
			$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? mt_rand() : $this->_version;

			$scripts = array(
				array(
					'handle'    => 'gform_jquery_ui_widget',
					'src'       => $this->get_base_url() . '/js/jquery.ui.widget.js',
					'version'   => $this->_version,
					'deps'      => array( 'jquery' ),
					'enqueue'   => array( array( $this, 'requires_script' ) ),
					'in_footer' => true,
				),
				array(
					'handle'    => 'gform_jquery_iframe',
					'src'       => $this->get_base_url() . '/js/jquery.iframe-transport.js',
					'version'   => $this->_version,
					'deps'      => array( 'jquery' ),
					'enqueue'   => array( array( $this, 'requires_script' ) ),
					'in_footer' => true,
				),
				array(
					'handle'    => 'gform_fileupload',
					'src'       => $this->get_base_url() . '/js/jquery.fileupload.js',
					'version'   => $this->_version,
					'deps'      => array( 'jquery' ),
					'enqueue'   => array( array( $this, 'requires_script' ) ),
					'in_footer' => true
				),
				array(
					'handle'    => 'itsg_gf_ajaxupload_js',
					'src'       => $this->get_base_url() . "/js/itsg_gf_ajaxupload_js{$min}.js",
					'version'   => $version,
					'deps'      => array( 'jquery' ),
					'enqueue'   => array( array( $this, 'requires_script' ) ),
					'in_footer' => true,
					'callback'  => array( $this, 'localize_scripts' ),
				),
				array(
					'handle'    => 'itsg_gf_ajaxupload_admin_js',
					'src'       => $this->get_base_url() . "/js/itsg_gf_ajaxupload_admin_js{$min}.js",
					'version'   => $version,
					'deps'      => array( 'jquery' ),
					'enqueue'   => array( array( $this, 'requires_script_admin' ) ),
					'in_footer' => true,
					'callback'  => array( $this, 'localize_scripts_admin' ),
				)
			);

			 return array_merge( parent::scripts(), $scripts );
		} // END scripts

		function requires_script_admin( $form, $is_ajax ) {
			return GFCommon::is_form_editor();
		}

		public function localize_scripts( $form, $is_ajax ) {
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
		} // END localize_scripts

		public function localize_scripts_admin( $form, $is_ajax ) {
			$settings_array = array(
				'text_ajax_upload' => esc_js( __( 'Ajax Upload', 'ajax-upload-for-gravity-forms' ) ),
				'text_instructions' => esc_js( __( 'Place a tick next to the field to make it an Ajax Upload field.', 'ajax-upload-for-gravity-forms' ) ),
				'text_make_ajax_upload' => esc_js( __( 'Make Ajax Upload', 'ajax-upload-for-gravity-forms' ) ),
			);

			wp_localize_script( 'itsg_gf_ajaxupload_admin_js', 'itsg_gf_ajaxupload_admin_js_settings', $settings_array );
		} // END localize_scripts_admin

		public function requires_script( $form, $is_ajax ) {
			if ( ! $this->is_form_editor() && is_array( $form ) ) {
				foreach ( $form['fields'] as $field ) {
					if ( 'itsg_single_ajax' == $field->get_input_type() ) {
						return true;
					} elseif ( 'list' == $field->get_input_type() ) {
						$has_columns = is_array( $field->choices );
						if ( $has_columns ) {
							foreach ( $field->choices as $choice ) {
								if ( rgar( $choice, 'isAjaxUpload' ) ) {
									return true;
								}
							}
						} elseif ( $field->itsg_list_field_ajaxupload ) {
							return true;
						}
					}
				}
			}

			return false;
		} // END requires_script

		public function styles() {
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) || rgar( $ajax_upload_options, 'displayscripterrors' ) ? '' : '.min';
			$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? mt_rand() : $this->_version;

			$styles = array(
				array(
					'handle'  => 'itsg_gf_ajaxupload_css',
					'src'     => $this->get_base_url() . "/css/itsg_gf_ajaxupload_css{$min}.css",
					'version' => $version,
					'media'   => 'screen',
					'enqueue' => array( array( $this, 'requires_stylesheet' ) ),
				),
			);

			return array_merge( parent::styles(), $styles );
		} // END styles

		public function requires_stylesheet( $form, $is_ajax ) {
			$ajax_upload_options = ITSG_GF_AjaxUpload::get_options();

			if ( rgar( $ajax_upload_options, 'includecss' ) && $this->requires_script( $form, $is_ajax ) ) {
				return true;
			}

			return false;
		} // END requires_stylesheet

    }
    new GFAjaxFileUpload();
}