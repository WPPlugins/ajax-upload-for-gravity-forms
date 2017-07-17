function itsg_ajaxupload_restore_input( ajaxupload_input ) {
	var ajaxupload_gf_input = ajaxupload_input.prev();
	var ajaxupload_progress = ajaxupload_input.siblings( '.progress' );
	var ajaxupload_results = ajaxupload_input.siblings( '.results' );
	var ajaxupload_cancel_button = ajaxupload_input.siblings( '.itsg_single_ajax_cancel' );
	var ajaxupload_remove_button = ajaxupload_input.siblings( '.itsg_single_ajax_remove' );
	var ajaxupload_dropzone = ajaxupload_input.parent( 'div.has-advanced-upload' );

	ajaxupload_input.show();
	ajaxupload_gf_input.val( '' ).change();
	ajaxupload_progress.remove();
	ajaxupload_results.remove();
	ajaxupload_cancel_button.remove();
	ajaxupload_remove_button.remove();
	ajaxupload_dropzone.addClass( 'itsg_ajax_upload_dropzone' );

	ajaxupload_input.focus();
}

function itsg_ajaxupload_maybe_restore_buttons( form_id ) {
	if ( ! window[ 'itsg_gf_ajaxupload_uploading_' + form_id ] ) {

		jQuery( '#gform_' + form_id + ' .gform_next_button:visible' ).attr( 'value', window[ 'itsg_gf_ajaxupload_next_value_' + form_id ] );
		jQuery( '#gform_' + form_id + ' .gform_previous_button:visible' ).attr( 'value', window[ 'itsg_gf_ajaxupload_previous_value_' + form_id ] );
		jQuery( '#gform_' + form_id + ' .gform_button[type="submit"]:visible' ).attr( 'value', window[ 'itsg_gf_ajaxupload_submit_value_' + form_id ] );
		jQuery( '#gform_' + form_id + ' a.gform_save_link:not(.top_button):visible' ).text( window[ 'itsg_gf_ajaxupload_save_value_' + form_id ] );
	}
}

// remove uploaded file from server, if current user has permission. There is also a server side check.
function itsg_ajaxupload_remove_upload( ajaxupload_gf_input_value, ajaxupload_input ) {
	// load localized settings
	var ajax_url = itsg_gf_ajaxupload_js_settings.ajax_url;
	var allowdelete = itsg_gf_ajaxupload_js_settings.allowdelete;
	var form_id = itsg_gf_ajaxupload_js_settings.form_id;
	var entry_user_id = itsg_gf_ajaxupload_js_settings.entry_user_id;

	if( '' != ajaxupload_gf_input_value && 'null' != ajaxupload_gf_input_value && undefined != ajaxupload_gf_input_value ) {
		if ( '1' == allowdelete ) {
			var file_name = decodeURIComponent( ajaxupload_gf_input_value.split( '/' ).pop().split( '?' ).shift() );
			var data = {
				'action': 'itsg_ajaxupload_delete_file',
				'file_name': file_name,
				'form_id': form_id,
				'entry_user_id': entry_user_id
				};

			var req = jQuery.ajax({
				url: ajax_url,
				type: 'POST',
				data: data
				});
		}
	}
	itsg_ajaxupload_restore_input( ajaxupload_input );
}

function itsg_gf_ajaxupload_display_upload( ajaxupload_input ){
	// load localized settings
	var thumbnail_enable = itsg_gf_ajaxupload_js_settings.thumbnail_enable;
	var thumbnail_width = parseInt( itsg_gf_ajaxupload_js_settings.thumbnail_width );
	var text_file = itsg_gf_ajaxupload_js_settings.text_file;
	var text_new_window = itsg_gf_ajaxupload_js_settings.text_new_window;
	var text_remove = itsg_gf_ajaxupload_js_settings.text_remove;

	var ajaxupload_gf_input = ajaxupload_input.prev();
	var ajaxupload_gf_input_value = ajaxupload_input.prev().val();

	var file_name = decodeURIComponent( ajaxupload_gf_input_value.split( '/' ).pop().split( '?' ).shift() );  // get file name out of the input URL
	var file_url = ajaxupload_gf_input_value;  // get file  URL
	var file_extension = file_name.substr( file_name.lastIndexOf('.') + 1 ).toLowerCase();

	// if results already exist - stop
	if ( typeof( ajaxupload_input.next( '.results' ) ) !== 'undefined' && ajaxupload_input.next( '.results' ).length ) return;

	if( ajaxupload_gf_input_value != '' && ajaxupload_gf_input_value != 'null' && ajaxupload_gf_input_value != undefined ) {
		// create results bar
		ajaxupload_input.after(
			jQuery('<div/>', {
				'class': 'results'
				}).append(
					jQuery('<div/>', {
						'class': 'results-bar results-bar-success'
					})
				)
		);

		var ajaxupload_results = ajaxupload_input.next( '.results' );
		var ajaxupload_results_div = ajaxupload_input.next( '.results' ).children().first();
		var time = jQuery.now();

		// create remove button
		jQuery('<input/>', {
			'class': 'button itsg_single_ajax_button itsg_single_ajax_remove',
			'type': 'button',
			'value': text_remove,
			'aria-describedby': 'remove_' + time
			}).append(
				jQuery('<span/>', {
						'id': 'remove_' + time,
						'class': 'sr-only',
						'text': text_remove + ' ' + text_file + ' ' + file_name
				})
				).insertAfter( ajaxupload_input.next( '.results' ) ).click( function() {
				itsg_ajaxupload_remove_upload( ajaxupload_gf_input_value, ajaxupload_input );
				jQuery( this ).remove(); // delete the remove button
			} );

		ajaxupload_input.hide();
		ajaxupload_results.show();

		if ( typeof( file_name ) !== 'undefined' && file_name.length > 30 ) {
			var file_name = file_name.substring( 0, 30 ) + '...';
		}
		// if thumbnails enabled and upload is jpg, png, gif, jpeg
		if ( ( '1' == thumbnail_enable ) && 'jpg' == file_extension || 'png' == file_extension || 'gif' == file_extension || 'jpeg' == file_extension ) {
			var file_name_encoded = ajaxupload_gf_input_value.split('/').pop();  // get file name out of the input URL
			var thumb_url = ajaxupload_gf_input_value.replace( file_name_encoded, 'thumbnail/' + file_name_encoded );  // get file  URL
			var display_file_name = '1' == itsg_gf_ajaxupload_js_settings.thumbnail_file_name_enable ? file_name : '';
			ajaxupload_results_div.append(
				jQuery( '<a/>', {
					'href': file_url,
					'target': '_blank',
					'class': 'thumbnail-link',
					'title': text_new_window
					}).append(
						jQuery('<img/>', {
							'src': thumb_url,
							'class': 'thumbnail',
							'onerror': 'if (this.src != \'' + file_url + '\') this.src = \'' + file_url + '\';',
							'alt': file_name,
							'title': file_name
						})
					).append(
						jQuery('<div/>', {
							'class': 'itsg_ajax_upload_file_name',
							'text': display_file_name
						})
					)
			);
			ajaxupload_input.parent( 'td.gfield_list_cell' ).css( 'width', '200px' );
			ajaxupload_results.addClass( 'done-thumbnail' );
		} else {
			ajaxupload_results_div.append(
				jQuery('<a/>', {
					'href': file_url,
					'target': '_blank',
					'alt': file_name,
					'title': text_new_window,
					'text': file_name
				})
			);
			ajaxupload_results.addClass( 'done' );
		}
		ajaxupload_results_div.find( 'a' ).focus();
	}
}

function itsg_gf_ajaxupload_init(){
	// load localized settings
	var ajax_url = itsg_gf_ajaxupload_js_settings.ajax_url;
	var file_size_kb = parseInt( itsg_gf_ajaxupload_js_settings.file_size_kb );
	var file_types = itsg_gf_ajaxupload_js_settings.file_types;
	var text_not_accepted_file_type = itsg_gf_ajaxupload_js_settings.text_not_accepted_file_type;
	var text_file_size_too_big = itsg_gf_ajaxupload_js_settings.text_file_size_too_big;
	var text_uploading = itsg_gf_ajaxupload_js_settings.text_uploading;
	var text_error_title = itsg_gf_ajaxupload_js_settings.text_error_title;
	var displayscripterrors = itsg_gf_ajaxupload_js_settings.displayscripterrors;
	var text_complete = itsg_gf_ajaxupload_js_settings.text_complete;
	var text_cancel = itsg_gf_ajaxupload_js_settings.text_cancel;
	var text_remove = itsg_gf_ajaxupload_js_settings.text_remove;
	var thumbnail_enable = itsg_gf_ajaxupload_js_settings.thumbnail_enable;
	var file_chunk_size = parseInt( itsg_gf_ajaxupload_js_settings.file_chunk_size );
	var form_id = itsg_gf_ajaxupload_js_settings.form_id;
	var user_id = itsg_gf_ajaxupload_js_settings.user_id;
	var entry_user_id = itsg_gf_ajaxupload_js_settings.entry_user_id;
	var text_error_0 = itsg_gf_ajaxupload_js_settings.text_error_0;
	var text_error_404 = itsg_gf_ajaxupload_js_settings.text_error_404;
	var text_error_500 = itsg_gf_ajaxupload_js_settings.text_error_500;
	var text_error_parse = itsg_gf_ajaxupload_js_settings.text_error_parse;
	var text_error_timeout = itsg_gf_ajaxupload_js_settings.text_error_timeout;
	var text_error_uncaught = itsg_gf_ajaxupload_js_settings.text_error_uncaught;
	var text_file = itsg_gf_ajaxupload_js_settings.text_file;

	var url = ajax_url;
	var i = 0;

	//applies the fileupload function to the file input field
	jQuery( 'input.itsg_ajax_upload_browse' ).each(function () {
		var form_id = jQuery( this ).parents( 'form' ).attr( 'id' ).split( '_' ).pop().trim();
		jQuery( this ).fileupload({
			maxNumberOfFiles: 1,
			singleFileUploads: false,
			dropZone: jQuery( this ).parent( '.itsg_ajax_upload_dropzone' ),
			maxChunkSize: file_chunk_size,
			url: url,
			dataType: 'json',
			progressInterval: '500',
			add: function( e, data ) {
				// error if more than one file dropped in dropzone
				if ( typeof( data.files ) !== 'undefined' && data.files.length > 1 ){
					alert( itsg_gf_ajaxupload_js_settings.text_only_one_file_message )
					return false;
				}
				// stop upload if file already uploaded to file
				if ( typeof( jQuery( this ).siblings( '.results' ) ) !== 'undefined' && jQuery( this ).siblings( '.results' ).length >= 1  ) {
					return false;
				}
				var ajaxupload_input = jQuery( this );
				var file_name = data.originalFiles[0]['name'];
				if ( itsg_gf_ajaxupload_js_settings.apostrophe_error_enable && file_name.indexOf("'") >= 0 ) {
					alert( itsg_gf_ajaxupload_js_settings.text_apostrophe_error_message )
					return false;
				}
				// make remove row button abort
				jQuery( document ).on( 'click', '.delete_list_item', function(){
					// when row is deleted - run the delete function to remove uploaded images from server
					//data.abort();
					var row = jQuery(this).parents( 'tr.gfield_list_group, tr.gfield_list_row_even, tr.gfield_list_row_odd' );
					itsg_gf_ajaxupload_remove_list_item( row );
				});

				var uploadErrors = [];

				// client side file type restrictions
				var acceptFileTypes = new RegExp( '(\.|\/)(' + file_types + ')$', 'i'); // ALLOWED FILE TYPES - CLIENT SIDE
				if( !acceptFileTypes.test( file_name ) ) {
					uploadErrors.push( text_not_accepted_file_type );
				}

				// client side file size restrictions
				if ( data.originalFiles[0]['size'] > file_size_kb ) {  // MAX FILE SIZE - CLIENT SIDE
					console.log( data.originalFiles[0]['size'] );
					uploadErrors.push( text_file_size_too_big );
				}

				// if there were client side upload errors - display them
				if ( typeof( uploadErrors ) !== 'undefined' && uploadErrors.length > 0 ) {
					alert( uploadErrors.join( '\n' ) );
				} else {
					jQuery.blueimp.fileupload.prototype.options.add.call( this, e, data );

					// all good - time to submit
					data.submit();

					// hide the ajaxupload field
					ajaxupload_input.hide();

					if ( ! window[ 'itsg_gf_ajaxupload_uploading_' + form_id ] ) {
						// get the form navigation button text values
						window[ 'itsg_gf_ajaxupload_next_value_' + form_id ] = jQuery( '#gform_' + form_id + ' .gform_next_button:visible' ).attr( 'value' );
						window[ 'itsg_gf_ajaxupload_previous_value_' + form_id ] = jQuery( '#gform_' + form_id + ' .gform_previous_button:visible' ).attr( 'value' );
						window[ 'itsg_gf_ajaxupload_submit_value_' + form_id ] = jQuery( '#gform_' + form_id + ' .gform_button[type="submit"]:visible' ).attr( 'value' );
						window[ 'itsg_gf_ajaxupload_save_value_' + form_id ] = jQuery( '#gform_' + form_id + ' a.gform_save_link:not(.top_button):visible' ).text();
					}

					window[ 'itsg_gf_ajaxupload_uploading_' + form_id ] = true;

					// replace with 'uploading' placeholeder text
					jQuery( '#gform_' + form_id + ' .gform_next_button:visible' ).attr( 'value', text_uploading );
					jQuery( '#gform_' + form_id + ' .gform_previous_button:visible' ).attr( 'value', text_uploading );
					jQuery( '#gform_' + form_id + ' .gform_button[type="submit"]:visible' ).attr( 'value', text_uploading );
					jQuery( '#gform_' + form_id + ' a.gform_save_link:not(.top_button):visible' ).text( text_uploading );

					var time = jQuery.now();

					// create cancel button
					jQuery('<input/>', {
						'class': 'button itsg_single_ajax_button itsg_single_ajax_cancel',
						'type': 'button',
						'value': text_cancel,
						'aria-describedby': 'cancel_' + time
					}).append(
					jQuery('<span/>', {
							'id': 'cancel_' + time,
							'class': 'sr-only',
							'text': text_cancel + ' ' + text_uploading + ' ' + text_file + ' ' + file_name
					})
					).insertAfter( ajaxupload_input ).click( function() {
							data.abort(); // abort the request
							jQuery( this ).remove(); // delete the remove button

							itsg_ajaxupload_maybe_restore_buttons( form_id ); // restore buttons
						});

					// create progress bar
					ajaxupload_input.after(
						jQuery('<div/>', {
							'class': 'progress uploading'
							}).append(
								jQuery('<div/>', {
									'class': 'progress-bar progress-bar-striped active',
									'role': 'progressbar',
									'aria-valuemin': '0',
									'aria-valuemax': '100',
								}).append(
									jQuery('<span/>', {
										'class': 'sr-only'
									})
								)
							)
					);
				}
			},
			done: function( e, data ) {
				// upload has stopped

				window[ 'itsg_gf_ajaxupload_uploading_' + form_id ] = false;

				var ajaxupload_input = jQuery( this );

				jQuery.each( data.result.files, function( index, file ) {
					// catch false positive server side error messages
					if ( file.error ) {
						alert( file.error );

						itsg_ajaxupload_restore_input( ajaxupload_input );

						return; // end the function
					}
					// set the file url to the gravity forms field
					ajaxupload_input.prev().val( file.url ).change();;

					// remove progress bar
					ajaxupload_input.next().remove();

					// display the uploaded file
					itsg_gf_ajaxupload_display_upload( ajaxupload_input );

					// remove cancel button
					ajaxupload_input.parent().find( '.itsg_single_ajax_cancel' ).remove();

					// remove dropzone
					ajaxupload_input.parent( '.itsg_ajax_upload_dropzone' ).removeClass( 'itsg_ajax_upload_dropzone' );
				});

				itsg_ajaxupload_maybe_restore_buttons( form_id );
			},
			error: function ( request, status, error ) {
				// upload has stopped
				window[ 'itsg_gf_ajaxupload_uploading_' + form_id ] = false;

				if ( '[object HTMLInputElement]' != error && '[object HTMLImageElement]' != error && '[object Object]' != error && 'abort' != error ) {
					 if ( request.status === 0 ) {
						error_message = text_error_0;
					} else if ( request.status == 404 ) {
						error_message = text_error_404;
					} else if ( request.status == 500 ) {
						error_message = text_error_500;
					} else if ( status === 'parsererror' ) {
						error_message = text_error_parse;
					} else if ( status === 'timeout' ) {
						error_message = text_error_timeout;
					} else {
						error_message = text_error_uncaught + request.responseText;
					}
					if ( '1' == displayscripterrors ) {
						alert( text_error_title + '\n\n' + error + '\n\n' + error_message );
					} else {
						alert( text_error_title  );
					}
					// log error message
					console.log( text_error_title + ' ' + error + ' ' + error_message );
				}
			},
			progress: function( e, data ) {
				// upload has started
				// console.log(data.bitrate);

				// calculates percentage
				var progress_percent = parseInt( data.loaded / data.total * 100, 10 );
				// adds the uploading message to the progress field
				jQuery( this ).next().children().first().attr( 'aria-valuenow', progress_percent );
				jQuery( this ).next().children().first().css( 'width', progress_percent + '%' );
				jQuery( this ).next().children().first().attr( 'aria-valuetext', progress_percent + '% ' + text_complete );
				jQuery( this ).next().find( 'span.sr-only' ).text( progress_percent + '% ' + text_complete );
				if ( '100' == progress_percent ) {
					jQuery( this ).next().addClass( '100' );
				}
				i = i + 1;
			},
			fail: function( e, data ) {
				var ajaxupload_input = jQuery( this );
				itsg_ajaxupload_restore_input( ajaxupload_input );
			},
		}).on({
			fileuploadsubmit: function( e, data ) {
				data.formData = {
					'action': 'itsg_ajaxupload_upload_file',
					'field_id': jQuery( this ).prev().attr( 'name' ).split( '_' ).pop().trim().replace( '[]', ''),
					'user_id': user_id,
					'form_id': jQuery( this ).closest( 'form' ).attr( 'id' ).split( '_' ).pop().trim(),
					'entry_user_id':entry_user_id
				};
			}
		});
	});
}

function maybePreventDefault( event ) {

	var form_id = event.data.form_id;
	if ( window[ 'itsg_gf_ajaxupload_uploading_' + form_id ] ) {
		jQuery( '.gform_ajax_spinner' ).hide(); // TO DO - fix for multi forms on single page
		event.preventDefault();
	} else {
		return;
	}
}

function itsg_gf_ajaxupload_setup_dropzone_function() {
	// disable default behaviour
	jQuery( document ).bind( 'drop dragover', function (e) {
		return false;
	});

	// check is browser supports drop and drag file uploads
	var isAdvancedUpload = function() {
		var div = document.createElement('div');
		return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
	}();

	// define drop zone
	var $form = jQuery( '.itsg_ajax_upload_dropzone' );

	if ( isAdvancedUpload ) {
		$form.addClass( 'has-advanced-upload' );
	}

	if ( isAdvancedUpload ) {
		$form.on( 'dragover dragenter', function() {
			var ajaxupload_input = jQuery( this ).find( 'input.itsg_ajax_upload_browse' );
			if ( typeof( ajaxupload_input.siblings( '.results' ) ) !== 'undefined' && ajaxupload_input.siblings( '.results' ).length < 1 ) {
				jQuery( this ).addClass( 'is-dragover' );
			}
		})
		.on( 'dragleave dragend drop', function() {
			jQuery( this ).removeClass( 'is-dragover' );
		});
	}
}

// when row is added - prepare row upload field, unbind and recreate fileupload
function itsg_gf_ajaxupload_add_list_item( new_row ) {
	jQuery( new_row ).find( 'input.itsg_ajax_upload_browse' ).each( function() {
		var ajaxupload_input = jQuery( this );
		ajaxupload_input.unbind().fileupload();
		ajaxupload_input.fileupload( 'destroy' );
		itsg_ajaxupload_restore_input( ajaxupload_input );
	});
	itsg_gf_ajaxupload_init();
}

// when row is removed - run each ajaxupload field through remove function
function itsg_gf_ajaxupload_remove_list_item( row ) {
	jQuery( row ).find( 'input.itsg_ajax_upload_browse' ).each( function() {
		var ajaxupload_input = jQuery( this );
		var ajaxupload_gf_input_value = jQuery( this ).prev().val();
		itsg_ajaxupload_remove_upload( ajaxupload_gf_input_value, ajaxupload_input )
	});
}

/* catch all for JavaScript errors */
window.onerror = function( msg, url, linenumber ) {
	// load localized settings
	var text_error = itsg_gf_ajaxupload_js_settings.text_error;
	var text_line_number = itsg_gf_ajaxupload_js_settings.text_line_number;
	var displayscripterrors = itsg_gf_ajaxupload_js_settings.displayscripterrors;

	console.log( text_error + ':  ' + msg + ' URL: ' + url + ' ' + text_line_number + ': ' + linenumber );
	if ( '1' == displayscripterrors ) {
		alert( text_error + ': \n' + msg + '\nURL: ' + url + '\n' + text_line_number + ': ' + linenumber );
	}
	return true;
}

if ( '1' == itsg_gf_ajaxupload_js_settings.is_entry_detail ) {
	// runs the main function when the page loads -- entry editor -- configures any existing upload fields
	jQuery(document).ready( function($) {
		itsg_gf_ajaxupload_init();
		itsg_gf_ajaxupload_setup_dropzone_function();
		jQuery( 'input.itsg_ajax_upload_browse' ).each( function() {
			var ajaxupload_input = jQuery( this );
			itsg_gf_ajaxupload_display_upload( ajaxupload_input );
		});

		// when field is added to repeater, runs the main function passing the current row
		jQuery( '.gfield_list' ).on( 'click', '.add_list_item', function(){
			var new_row = jQuery( this ).parents( 'tr.gfield_list_group, tr.gfield_list_row_even, tr.gfield_list_row_odd' ).next( 'tr.gfield_list_group, tr.gfield_list_row_even, tr.gfield_list_row_odd' );
			itsg_gf_ajaxupload_add_list_item( new_row );
		});

	});
} else {
	// runs the main function when the page loads -- front end forms -- configures any existing upload fields
	jQuery( document ).bind( 'gform_post_render', function($) {
		itsg_gf_ajaxupload_init();
		itsg_gf_ajaxupload_setup_dropzone_function();

		jQuery( 'input.itsg_ajax_upload_browse' ).each( function() {
			var ajaxupload_input = jQuery( this );
			itsg_gf_ajaxupload_display_upload( ajaxupload_input );
		});

		// when field is added to repeater, runs the main function passing the current row
		jQuery( '.gfield_list' ).on( 'click', '.add_list_item', function(){
			var new_row = jQuery( this ).parents( 'tr.gfield_list_group, tr.gfield_list_row_even, tr.gfield_list_row_odd' ).next( 'tr.gfield_list_group, tr.gfield_list_row_even, tr.gfield_list_row_odd' );
			itsg_gf_ajaxupload_add_list_item( new_row );
		});

		// stop pages being navigated or form submitted when upload in progress
		jQuery( '.gform_wrapper form' ).each(function () {

			// get form id
			var form_id = jQuery( this ).attr( 'id' ).split( '_' ).pop().trim();

			jQuery( this ).bind( 'submit', { form_id: form_id }, maybePreventDefault );

			// get the form navigation button text values
		//	window[ 'itsg_gf_ajaxupload_next_value_' + form_id ] = jQuery( '#gform_' + form_id + ' .gform_next_button' ).first().attr( 'value' );
		//	window[ 'itsg_gf_ajaxupload_previous_value_' + form_id ] = jQuery( '#gform_' + form_id + ' .gform_previous_button' ).first().attr( 'value' );
		//	window[ 'itsg_gf_ajaxupload_submit_value_' + form_id ] = jQuery( '#gform_' + form_id + ' .gform_button[type="submit"]' ).first().attr( 'value' );
		//	window[ 'itsg_gf_ajaxupload_save_value_' + form_id ] = jQuery( '#gform_' + form_id + ' a.gform_save_link:not(.top_button)' ).first().text();
		});
	});
}