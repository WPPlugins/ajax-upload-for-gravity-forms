=== Ajax Upload for Gravity Forms ===
Contributors: ovann86
Donate link: http://www.itsupportguides.com/
Tags: gravity forms, forms, ajax, file, upload, wcag, accessibility
Requires at least: 4.7.0
Tested up to: 4.7.2
Stable tag: 2.7.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides two ajax file upload fields - a single field and the ability to make the a list field column an upload field.

== Description ==

> This plugin is an add-on for the <a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=299380" target="_blank">Gravity Forms</a> (affiliate link) plugin. If you don't yet own a license for Gravity Forms - <a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=299380" target="_blank">buy one now</a>! (affiliate link)

**What does this plugin do?**

* automatically uploads files before the form is submitted - making the final form submission load quicker
* provides two ways of uploading files - a single 'Ajax Upload' field and in the repeatable 'List' field
* display image thumbnails
* send attachments via email
* download attachments in bulk via the entry editor
* add images to WordPress Media Library
* automatic image size reducing and compression
* 'chunked' file uploads - making large files easier to upload to the server (some servers will timeout or don't support large file uploads)
* compatible with the <a href="https://wordpress.org/plugins/gravity-forms-pdf-extended/" target="_blank">Gravity PDF</a> plugin

Includes an **easy to use settings page** that allows you to configure:

* maximum **file size**, in megabytes (MB)
* allowed **file types**, by file extension
* configure the Gravity Forms **upload directory** path in an easy to use settings
* allows upload directory path to include form ID, hashed form ID, user ID, hashed user ID, year and month
* **image size** reduction, in px
* JPEG **image quality**
* add to WordPress Media Library (optional)
* add attachments to notification emails (zip file attachment)
* view a list of attachments and download in a ZIP from the backend entry editor
* and more !

> See a demo of this plugin at [demo.itsupportguides.com/ajax-upload-for-gravity-forms/](http://demo.itsupportguides.com/ajax-upload-for-gravity-forms/ "demo website")

**How to I use the plugin?**

Simply install and activate the plugin - no configuration required.

Open your Gravity Form, and either add the 'Ajax Upload' field to your form, or a 'List' field and use the tick boxes to make a column an upload field.

To configure the plugin, go to the Gravity Forms -> Settings -> Ajax Upload menu.

**Have a suggestion, comment or request?**

Please leave a detailed message on the support tab.

**Let me know what you think**

Please take the time to review the plugin. Your feedback is important and will help me understand the value of this plugin.

**Disclaimer**

*Gravity Forms is a trademark of Rocketgenius, Inc.*

*This plugins is provided “as is” without warranty of any kind, expressed or implied. The author shall not be liable for any damages, including but not limited to, direct, indirect, special, incidental or consequential damages or losses that occur out of the use or inability to use the plugin.*

== Installation ==

1. This plugin requires the Gravity Forms plugin, installed and activated
2. Install plugin from WordPress administration or upload folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in the WordPress administration
4. A new 'Ajax Upload' field will be available, as well as the ability to make a 'List' field column an upload field

== Frequently Asked Questions ==

**How do I configure the plugin?**

A range of options can be found under the Gravity Forms 'Ajax Upload' settings menu.

**How do I rename uploaded files**

Uploaded files can be automatically renamed using the 'itsg_gf_ajaxupload_filename' filter.

The example below shows how to use the filter to rename uploaded files to FileOfField{field_id}.originalExtension. For example WordDocument.doc would be renamed to FileOfField1.doc

`add_filter( 'itsg_gf_ajaxupload_filename', 'my_itsg_gf_ajaxupload_filename', 10, 7 );
function my_itsg_gf_ajaxupload_filename( $name, $file_path, $size, $type, $error, $index, $content_range ) {
	$field_id = isset( $_POST['field_id'] ) ? $_POST['field_id'] : null; // get the field_id out of the post
	$file_extension = pathinfo( $name, PATHINFO_EXTENSION );  // get the file type out of the URL
	$name = 'FileOfField' . $field_id . '.' . $file_extension; // put it together
	return $name; // now return the new name
}`

The example below shows how to use the filter to rename uploaded files using the field label.

`add_filter( 'itsg_gf_ajaxupload_filename', 'my_itsg_gf_ajaxupload_filename', 10, 7 );
function my_itsg_gf_ajaxupload_filename( $name, $file_path, $size, $type, $error, $index, $content_range ) {
    $form_id = isset( $_POST['form_id'] ) ? $_POST['form_id'] : null; // get the form_id out of the post
    $field_id = isset( $_POST['field_id'] ) ? $_POST['field_id'] : null; // get the field_id out of the post
    $file_extension = pathinfo( $name, PATHINFO_EXTENSION );  // get the file type out of the URL

	$form_meta = GFFormsModel::get_form_meta( $form_id ); // load the form meta
	$field = GFFormsModel::get_field( $form_meta, $field_id ); // get the field

    $field_label = GFFormsModel::get_label( $field ); // get the field label
	$field_label = preg_replace( '/[^A-Za-z0-9 ]/', '', $field_label ); // make sure the field label only includes supported characters -- restricting to A-Z 0-9

    $name = $field_label '.' . $file_extension; // put it together

    return $name; // now return the new name
}`

Note that the plugin will add a number to the end of the file if the file name already exists in the folder. So files will not be overwritten.

**How do I control allowed files for each form**

Allowed files can be controlled on a per-form basis using the 'itsg_gf_ajaxupload_options' filter.

The example below shows how to set form ID 3 and 9 to only allow images. Other forms will use the settings specified in the Ajax Upload menu.

`add_filter( 'itsg_gf_ajaxupload_options', 'my_itsg_gf_ajaxupload_options', 10, 2 );
function itsg_gf_ajaxupload_options( $options, $form_id ) {
	if ( 3 == $form_id || 9 == $form_id ) {
		$options['filetype'] = '/(\.|\/)(png|gif|jpg|jpeg)$/i';
	}
	return $options;
}`

**How to I customise the file URL**

The 'itsg_gf_ajaxupload_response' filter is available to modify the upload response before it is returned to the browser.

The example below shows how to use this filter to add ?attname= to the end of the file URL.

Note that this filter needs to be ran from a plugin - NOT a theme's functions.php. This is because an upload does not run a theme and therefore doesnt run the filter added to it. See <a href='https://www.itsupportguides.com/wordpress/how-to-create-a-wordpress-plugin-for-your-custom-functions/'>How to create a WordPress plugin for your custom functions</a> for how to create a custom plugin to hold the filter.

`add_filter( 'itsg_gf_ajaxupload_response', 'my_itsg_gf_ajaxupload_response', 10, 3 );

function my_itsg_gf_ajaxupload_response( $upload_file, $form_id, $field_id ) {
	if ( isset( $upload_file['files'][0]->url ) ) {
		$upload_file['files'][0]->url .= '?attname=';
	}
	return $upload_file;
}`

== Screenshots ==

1. Shows the 'Ajax Upload' field under the 'Standard Fields' list in the form editor.
2. Shows a 'List' field with the Ajax Upload options below. Placing a tick next to the column title will turn the column into an upload field.
3. Shows a sample form with two 'Ajax Upload' fields and a list with a ajax upload column.
4. Shows a sample form with files uploaded to the ajax upload fields. File names are links to the uploaded files. You can use the 'remove' button to remove the file and upload another, as well as the + and - buttons to add and remove more list rows with upload fields.
5. Shows uploaded files in the GravityWiz better pre-confirmation plugin.
6. Shows the submitted form in the entry page. Uploaded files can be accessed by clicking on the linked file names.
7. Shows the submitted form in the entry page, in edit mode. Files can be removed and added by an administrator from the backend.
8. Shows the settings page located in the Gravity Forms -> Settings -> Ajax Upload menu.

== Changelog ==

= 2.7.3 =
Fix: Update upload handler (blueimp file uploader) to version 9.17.0 to resolve upload issue in Chrome for Android and intermittent issue in Firefox.

= 2.7.2 =
* Fix: resolve conflict with <a href="https://wordpress.org/plugins/gravity-forms-list-field-select-drop-down/" target="_blank">Drop Down Options in List Fields for Gravity Forms</a> plugin
* Maintenance: tidy php (use object notation for $field)
* Maintenance: change how plugin detects that Gravity Forms is installed and enabled
* Maintenance: use full JavaScript (instead of minified) when 'Display script errors to users' option enabled (will provide more accurate JavaScript error messages)

= 2.7.1 =
* Maintenance: adjust how image file name is presented in Gravity PDF when 'Display file name below thumbnail' option is enabled. Resolves issue with text not always being linked.

= 2.7.0 =
* Feature: add ability to individually set notification ZIP file name. The ZIP file name in the Ajax Upload settings remains the default, with the 'ZIP file name' option under the notification allowing you to set a specific file name for the notification. Supports merge tags from form fields.

= 2.6.0 =
* Feature: add 'Display file name below thumbnail' option to add file name below image thumbnails when uploading images, viewing in entry editor, email notifications and creating Gravity PDF's. See 'wp-admin -> Forms -> Settings -> Ajax Upload' to enable.

= 2.5.1 =
* Fix: resolve conflict with <a href='https://wordpress.org/plugins/list-field-number-format-for-gravity-forms/'>List Field Number Format for Gravity Forms</a> plugin (PDF would not display correctly when a list field contained both an 'Ajax Upload' column and a 'number format' column.

= 2.5.0 =
* Feature: add support for multiple forms on a single page
* Fix: resolve issue with file dropzone not setting up when list row is added

= 2.4.5 =
* Feature: Add 'itsg_gf_ajaxupload_response' filter to allow customising uploaded file details (for example, add a query arg to the file URL). Details in FAQ.

= 2.4.4 =
* Maintenance: (JavaScript) trigger 'change' event handler when upload has completed and removed.

= 2.4.3 =
* Fix: Resolve issue with 'use server path' for thumbnails in Gravity PDF not working as expected.

= 2.4.2 =
* Fix: Resolve 'Object doesn’t support property or method ‘includes’' error when using Internet Explorer 11
* Fix: Resolve 'TypeError: undefined is not an object(evaluating ‘a.length’ )' error

= 2.4.1 =
* Fix: Resolve 'Undefined variable: content' error for single column ajax enabled list fields.

= 2.4.0 =
* Feature: Add ability to drag and drop files into upload field. (Note: this only supports one file at a time.)
* Feature: Improved error handling when server has mod_security enabled. When enabled, if an upload file has an apostrophe/quote in the file name a server-side error is triggered. An option has been added to the Forms -> Settings -> Ajax Upload page to present an error message instead of a server-side error message. (Note: for the standard Gravity Forms file upload field mod_security breaks uploads and likely results in lost form data)
* Maintenance: Improve handling when web server does not have the ability to create ZIP files.

= 2.3.2 =
* Fix: Add check to ensure field preview and remove button are not generated more than once.

= 2.3.1 =
* Fix: Add # character to list of excluded file name characters - the hash symbol is not always handled correctly in PDFs and is best to avoid all together when using links in PDFs

= 2.3.0 =
* Feature: Add 'user_login' keyword for upload path - uses current user's username or if not logged in '0'
* Fix: Resolve issue with form not loading in custom dashboard pages

= 2.2.2 =
* Fix: Resolve issue with "Delete files after notification sent" option not deleting files.

= 2.2.1 =
* Fix: Resolve "Can't use function return value in write context" error when using PHP Version 5.4
* Fix: Prefix ZIP file directory with field id, ensures fields with the same label have their own directory in generated ZIP files

= 2.2.0 =
* Feature: Enhancements to form editor - form meta box includes a list of uploaded files and a link to download all files in a ZIP file.
* Feature: Add display icon for single ajax upload field in the entry list table.
* Feature: Add post upload action for hooking into third-party scripts/systems (e.g. Dropbox) - itsg_gf_ajaxupload_post_upload
* Fix: Add support for older versions of Gravity Forms (repeating list field was not resetting when adding a new row)
* Fix: Fix error in setting up translatable strings
* Maintenance: Move wp-admin JavaScript to external file.
* Maintenance: Add minified JavaScript and CSS
* Maintenance: Confirm working with WordPress 4.6.0 RC1
* Maintenance: Update to improve support for Gravity Flow plugin

= 2.1.0 =
* Feature: Add option to enable/disable auto-rotate images. Disabled by default, previously enabled by default.
* Fix: Fix issue with {hashed_user_id} keyword not working for upload path setting.

= 2.0.1 =
* Feature: Add options to change the terms 'Cancel', 'Remove' and 'Uploading'. For example, change to different terms or another language.
* Feature: Add 'timestamp' keyword for ZIP file name.
* Maintenance: Improve support for when a list field has more than one file upload column.
* Maintenance: Improve support for Gravity PDF, version 3 and version 4.
* Maintenance: Improve accessibility of upload fields - added aria attributes to announce upload progress, added aria-describedby to get remove and cancel buttons context, and used .focus() to set the focus when upload has completed or been cancelled or removed.

= 2.0.0 =
* Feature: Add ability to include uploaded files in email notifications
* Feature: Add filter (itsg_gf_ajaxupload_options) to control allowed file types for individual forms. See FAQ's for more information.
* Feature: Add option to enable file chunking - this splits large uploads into multiple streams to mitigate issues with uploading large files.
* Feature: Add remove and cancel buttons for upload fields in list fields.
* Fix: Resolved bug where canceling an upload may cancel other uploads in progress
* Fix: Resolved bug where URLs in non-ajax upload fields would have formatting applied
* Fix: Resolved issue with loading language translations
* Maintenance: Move JavaScript to an external file
* Maintenance: Completely re-wrote JavaScript
* Maintenance: Improved client side error messages when an upload fails at the server-side
* Maintenance: Updates to ajax-upload field structure and CSS styling
* Maintenance: Tested against Gravity Forms 2.0 RC1
* Maintenance: Tested against Gravity PDF 4.0 RC4

= 1.8.4 =
* Maintenance: Add support for the 'WooCommerce - Gravity Forms Product Add-Ons' plugin

= 1.8.3 =
* Maintenance: Switch to using Gravity Forms rgar function.
* Fix: Fix bug where file preview would not display if a file was removed and immediately re-uploaded before the delete script had completed.

= 1.8.2 =
* Fix: Change short PHP open tag to full open tag in gravity-forms-ajax-upload-addon.php

= 1.8.1 =
* Fix: Attempt to fix parse error in gravity-forms-ajax-upload-addon.php file

= 1.8.0 =
* Feature: Automatically remove characters in file names that may break cross-system compatibility, this can be disabled using the 'Exclude special characters' option on the settings page.
* Fix: Improve multisite support.
* Maintenance: Set minimum PHP version at 5.3 and add warning message if host does not meet this requirement.
* Maintenance: Revise import to WordPress media library feature for better compatibility.

= 1.7.4 =
* Fix: Resolve issue with displaying submitted entry when field value is '0'.

= 1.7.3 =
* Fix: Attempt resolve issues some users were experiencing (unexpected '[' message)
* Maintenance: Switch to using Gravity forms rgar function

= 1.7.2 =
* Fix: Add missing full-stop to jQuery selector (gform_previous_button)

= 1.7.1 =
* Fix: Resolve error in import_media_library function
* Fix: Change form editor JavaScript so that you can see which columns are upload fields

= 1.7.0 =
* Feature: Add the ability to add uploaded images to the WordPress media library - this can be enabled in the Gravity Forms -> Settings -> Ajax Upload settings page.
* Fix: Change next/previous/submit button rename process so that it uses the original value instead of assuming Gravity Forms defaults are being used.
* Fix: redo progress bar HTML and CSS to better support themes that use bootstrap

= 1.6.0 =
* Feature: Add setting to enable/disable thumbnails in PDF's created in Gravity PDF.
* Feature: Add setting to use server path when rendering thumbnails in PDF's created in Gravity PDF.
* Fix: Resolve JavaScript error when removing list field rows.

= 1.5.1 =
* Fix: Improvements to 'Cancel' feature, extending it to ajax upload fields in list field columns.

= 1.5.0 =
* Feature: Add 'Cancel' button for single Ajax Upload field.
* Feature: Disable submit and page navigation while upload(s) are in progress.

= 1.4.0 =
* Feature: Add 'Settings' link to plugin on the WordPress installed plugins page.
* Improvement: Change JavaScript scripts and CSS to enqueue using the Gravity Forms add-on framework.
* Improvement: Group similar settings in Ajax Upload settings page.
* Maintenance: General tidy up of code, working towards WordPress standards.

= 1.3.0 =
* Feature: Improved error handling for when upload process remains stuck at 100%.
* Feature: Client side JavaScript error messages when script error occurs.
* Feature: Allow single column list fields to be Ajax Upload enabled.
* Feature: Add ability to override the Gravity Forms 'No-Conflict Mode' setting.
* Feature: Extend image thumbnail support to include the entry editor and PDF's created using Gravity PDF.
* Feature: Add filter to customise uploaded file names.
* Improvement: Added fail-safe in case image thumbnail does not exist, for example if image was uploaded before version 1.2.0 in a saved form. If thumbnail does not exist the full sized image will be displayed using the thumbnails width setting as the image width.
* Improvement: Added warning message to the entry editor if Gravity Forms 'No-Conflict Mode' is enabled.
* Fix: Resolve issue with the upload fields not working in entry editor.
* Maintenance: Tidy up of PHP code.
* Maintenance: Update Upload Handler script to version 9.12.1.
* Maintenance: Improve language translation support.

= 1.2.0 =
* FEATURE: Added thumbnail support. Can be enabled and managed from the Ajax Upload Settings page.
* FIX: Added check before loading UploadHandler class to avoid conflicts.

= 1.1.1 =
* FIX: Resolved PHP parse error caused by clickable_list_values() function where field is empty.
* MAINTENANCE: Added blank index.php file to plugin directory to ensure directory browsing does not occur. This is a security precaution.
* MAINTENANCE: Added ABSPATH check to plugin PHP files to ensure PHP files cannot be accessed directly. This is a security precaution.

= 1.1 =
* FIX: Resolve PHP parse errors.
* FEATURE: Added support for text translation - uses 'itsg_gf_ajax_upload' text domain.
* MAINTENANCE: General tidy up of PHP and JavaScript code.

= 1.0 =
* First public release.