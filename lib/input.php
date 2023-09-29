<?php
/**
 * Parameter input functions.
 * This file contains functions for getting input from get/post variables.
 *
 * @package Elgg.Core
 * @subpackage Input
 */

/**
 * Get some input from variables passed submitted through GET or POST.
 *
 * If using any data obtained from get_input() in a web page, please be aware that
 * it is a possible vector for a reflected XSS attack. If you are expecting an
 * integer, cast it to an int. If it is a string, escape quotes.
 *
 * Note: this function does not handle nested arrays (ex: form input of param[m][n])
 * because of the filtering done in htmlawed from the filter_tags call.
 * @todo Is this ^ still true?
 *
 * @param string $variable      The variable name we want.
 * @param mixed  $default       A default value for the variable if it is not found.
 * @param bool   $filter_result If true, then the result is filtered for bad tags.
 *
 * @return mixed
 */
function get_input($variable, $default = NULL, $filter_result = TRUE) {

	global $CONFIG;

	$result = $default;

	if (isset($CONFIG->input[$variable])) {
		$result = $CONFIG->input[$variable];

		if ($filter_result) {
			$result = filter_tags($result);
		}
	} elseif (isset($_REQUEST[$variable])) {
		if (is_array($_REQUEST[$variable])) {
			$result = $_REQUEST[$variable];
		} else {
			$result = trim($_REQUEST[$variable]);
		}

		if ($filter_result) {
			$result = filter_tags($result);
		}
	}

	return $result;
}

/**
 * Sets an input value that may later be retrieved by get_input
 *
 * Note: this function does not handle nested arrays (ex: form input of param[m][n])
 *
 * @param string $variable The name of the variable
 * @param string $value    The value of the variable
 *
 * @return void
 */
function set_input($variable, $value) {
	global $CONFIG;
	if (!isset($CONFIG->input)) {
		$CONFIG->input = array();
	}

	if (is_array($value)) {
		array_walk_recursive($value, create_function('&$v, $k', '$v = trim($v);'));
		$CONFIG->input[trim($variable)] = $value;
	} else {
		$CONFIG->input[trim($variable)] = trim($value);
	}
}

/**
 * Filter tags from a given string based on registered hooks.
 *
 * @param mixed $var Anything that does not include an object (strings, ints, arrays)
 *					 This includes multi-dimensional arrays.
 *
 * @return mixed The filtered result - everything will be strings
 */
function filter_tags($var) {
	return $var;
}

/**
 * Validates an email address.
 *
 * @param string $address Email address.
 *
 * @return bool
 */
function is_email_address($address) {
	return filter_var($address, FILTER_VALIDATE_EMAIL) === $address;
}
