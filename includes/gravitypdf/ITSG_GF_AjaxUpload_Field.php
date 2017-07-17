<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Abstract_Fields;

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls the display and output of the textarea field
 *
 * @since 4.0
 */
class ITSG_GF_AjaxUpload_Field extends Helper_Abstract_Fields {

	/**
	 * Check the appropriate variables are parsed in send to the parent construct
	 *
	 * @param object               $field The GF_Field_* Object
	 * @param array                $entry The Gravity Forms Entry
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Form $form
	 * @param \GFPDF\Helper\Helper_Misc          $misc
	 *
	 */
	public function __construct( $field, $entry, Helper_Abstract_Form $form, Helper_Misc $misc ) {
		/* call our parent method */
		parent::__construct( $field, $entry, $form, $misc );
	}

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {
		/* exit early if list field is empty */
		if ( $this->is_empty() ) {
			return parent::html( '' );
		}

		/* get out field value */
		$value   = $this->value();
		$columns = is_array( $value[0] );

		/* Start buffer and generate a list table */
		ob_start();
		?>

		<table autosize="1" class="gfield_list">

			<!-- Loop through the column names and output in a header (if using the advanced list) -->
			<?php if ( $columns ) : $columns = array_keys( $value[0] ); ?>
				<tbody class="head">
					<tr>
						<?php foreach ( $columns as $column ) : ?>
							<th>
								<?php echo strip_tags( $column, '<div><strong><a><img>'); ?>
							</th>
						<?php endforeach; ?>
					</tr>
				</tbody>
			<?php endif; ?>

			<!-- Loop through each row -->
			<tbody class="contents">
			<?php foreach ( $value as $item ) : ?>
				<tr>
					<!-- handle the basic list -->
					<?php if ( ! $columns ) : ?>
						<td><?php echo strip_tags( $item, '<div><strong><a><img>') ?></td>
					<?php else : ?><!-- handle the advanced list -->
						<?php foreach ( $columns as $column ) : ?>
							<td>
								<?php echo strip_tags( rgar( $item, $column ), '<div><strong><a><img>' ); ?>
							</td>
						<?php endforeach; ?>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>

		</table>

		<?php
		/* get buffer and return HTML */

		return parent::html( ob_get_clean() );
	}

	/**
	 * Get the standard GF value of this field
	 *
	 * @return string|array
	 *
	 * @since 4.0
	 */
	public function value() {

		if ( $this->has_cache() ) {
			return $this->cache();
		}

		$value = maybe_unserialize( $this->get_value() );

		/* make sure value is an array */
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		/* Remove empty rows */
		$value = $this->remove_empty_list_rows( $value );

		$this->cache( $value );

		return $this->cache();
	}
	
	/**
	 * Remove empty list rows
	 *
	 * @param  array $list The current list array
	 *
	 * @return array       The filtered list array
	 *
	 * @since 4.0
	 */
	private function remove_empty_list_rows( $list ) {

		/* if list field empty return early */
		if ( ! is_array( $list ) || sizeof( $list ) === 0 ) {
			return $list;
		}

		/* If single list field */
		if ( ! is_array( $list[0] ) ) {
			$list = array_filter( $list );
			//$list = array_map( 'esc_html', $list );
		} else {

			/* Loop through the multi-column list */
			foreach ( $list as $id => &$row ) {

				$empty = true;

				foreach ( $row as &$col ) {

					/* Check if there is data and if so break the loop */
					if ( strlen( trim( $col ) ) > 0 ) {
						$col = strip_tags( $col, '<div><strong><a><img>');
						$empty = false;
					}
				}

				/* Remove row from list */
				if ( $empty ) {
					unset( $list[ $id ] );
				}
			}
		}

		return $list;
	}
}