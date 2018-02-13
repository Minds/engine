<?php
/**
 * Elgg language module
 * Functions to manage language and translations.
 *
 * @package Elgg.Core
 * @subpackage Languages
 */

/**
 * Given a message key, returns an appropriately translated full-text string
 *
 * @param string $message_key The short message code
 * @param array  $args        An array of arguments to pass through vsprintf().
 * @param string $language    Optionally, the standard language code
 *                            (defaults to site/user default, then English)
 *
 * @return string Either the translated string, the English string,
 * or the original language string.
 */
function elgg_echo($message_key, $args = array(), $language = "") {
	return $message_key;
}

/**
 * Add a translation.
 *
 * Translations are arrays in the Zend Translation array format, eg:
 *
 *	$english = array('message1' => 'message1', 'message2' => 'message2');
 *  $german = array('message1' => 'Nachricht1','message2' => 'Nachricht2');
 *
 * @param string $country_code   Standard country code (eg 'en', 'nl', 'es')
 * @param array  $language_array Formatted array of strings
 *
 * @return bool Depending on success
 */
function add_translation($country_code, $language_array) {
	return false;
}

/**
 * Detect the current language being used by the current site or logged in user.
 *
 * @return string The language code for the site/user or "en" if not set
 */
function get_current_language() {
	$language = get_language();

	if (!$language) {
		$language = 'en';
	}

	return $language;
}

/**
 * Gets the current language in use by the system or user.
 *
 * @return string The language code (eg "en") or false if not set
 */
function get_language() {
	return false;
}

function _elgg_load_translations() {

}

/**
 * When given a full path, finds translation files and loads them
 *
 * @param string $path     Full path
 * @param bool   $load_all If true all languages are loaded, if
 *                         false only the current language + en are loaded
 *
 * @return bool success
 */
function register_translations($path, $load_all = false) {
	return false;
}

/**
 * Reload all translations from all registered paths.
 *
 * This is only called by functions which need to know all possible translations.
 *
 * @todo Better on demand loading based on language_paths array
 *
 * @return void
 */
function reload_all_translations() {
}

/**
 * Return an array of installed translations as an associative
 * array "two letter code" => "native language name".
 *
 * @return array
 */
function get_installed_translations() {
	return [];
}

/**
 * Return the level of completeness for a given language code (compared to english)
 *
 * @param string $language Language
 *
 * @return int
 */
function get_language_completeness($language) {
	return 0;
}

/**
 * Return the translation keys missing from a given language,
 * or those that are identical to the english version.
 *
 * @param string $language The language
 *
 * @return mixed
 */
function get_missing_language_keys($language) {
	return false;
}
