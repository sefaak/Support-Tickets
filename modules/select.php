<?php
/**
** A base module for [select] and [select*]
**/

/* Shortcode handler */

suptic_add_shortcode( 'select', 'suptic_select_shortcode_handler', true );
suptic_add_shortcode( 'select*', 'suptic_select_shortcode_handler', true );

function suptic_select_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];
	$labels = (array) $tag['labels'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';

	$defaults = array();

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '/^default:([0-9_]+)$/', $option, $matches ) ) {
			$defaults = explode( '_', $matches[1] );
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$multiple = (bool) preg_grep( '%^multiple$%', $options );
	$include_blank = (bool) preg_grep( '%^include_blank$%', $options );

	$empty_select = empty( $values );
	if ( $empty_select || $include_blank ) {
		array_unshift( $values, '---' );
		array_unshift( $labels, '---' );
	}

	$html = '';

	foreach ( $values as $key => $value ) {
		$selected = false;

		if ( ! $empty_select && in_array( $key + 1, (array) $defaults ) )
			$selected = true;

		if ( $multiple && in_array( esc_sql( $value ), (array) $_POST[$name] ) )
			$selected = true;
		if ( ! $multiple && $_POST[$name] == esc_sql( $value ) )
			$selected = true;

		$selected = $selected ? ' selected="selected"' : '';

		if ( isset( $labels[$key] ) )
			$label = $labels[$key];
		else
			$label = $value;

		$html .= '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
	}

	if ( $multiple )
		$atts .= ' multiple="multiple"';

	$html = '<select name="' . $name . ( $multiple ? '[]' : '' ) . '"' . $atts . '>' . $html . '</select>';

	if ( $validation_error = $_POST['_suptic_validation_errors']['messages'][$name] ) {
		$validation_error = '<span class="suptic-not-valid-tip-no-ajax">'
			. esc_html( $validation_error ) . '</span>';
	} else {
		$validation_error = '';
	}

	$html = '<span class="suptic-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Validation filter */

add_filter( 'suptic_validate_select', 'suptic_select_validation_filter', 10, 2 );
add_filter( 'suptic_validate_select*', 'suptic_select_validation_filter', 10, 2 );

function suptic_select_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];
	$values = $tag['values'];

	if ( is_array( $_POST[$name] ) ) {
		foreach ( $_POST[$name] as $key => $value ) {
			$value = stripslashes( $value );
			if ( ! in_array( $value, (array) $values ) ) // Not in given choices.
				unset( $_POST[$name][$key] );
		}
	} else {
		$value = stripslashes( $_POST[$name] );
		if ( ! in_array( $value, (array) $values ) ) //  Not in given choices.
			$_POST[$name] = '';
	}

	if ( 'select*' == $type ) {
		if ( empty( $_POST[$name] ) ||
			! is_array( $_POST[$name] ) && '---' == $_POST[$name] ||
			is_array( $_POST[$name] ) && 1 == count( $_POST[$name] ) && '---' == $_POST[$name][0] ) {
			$result['valid'] = false;
			$result['reason'][$name] = __( "Please fill the required field.", 'suptic' );
		}
	}

	return $result;
}

?>