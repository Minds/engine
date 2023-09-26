<?php
/**
 * Bootstrapping and helper procedural code available for use in Elgg core and plugins.
 *
 * @package Elgg.Core
 * @todo These functions can't be subpackaged because they cover a wide mix of
 * purposes and subsystems.  Many of them should be moved to more relevant files.
 */


/**
 * Forward to $location.
 *
 * Sends a 'Location: $location' header and exists.  If headers have
 * already been sent, returns FALSE.
 *
 * @param string $location URL to forward to browser to. Can be path relative to the network's URL.
 * @param string $reason   Short explanation for why we're forwarding
 *
 * @return false False if headers have been sent. Terminates execution if forwarding.
 * @throws SecurityException
 */
function forward($location = "", $reason = 'system') {
	if (!headers_sent()) {
		if ($location === REFERER) {
			$location = $_SERVER['HTTP_REFERER'];
		}

		if ($location) {
			header("Location: {$location}");
			exit;
		} else if ($location === '') {
			exit;
		}
	} else {exit;
		throw new SecurityException('SecurityException:ForwardFailedToRedirect');
	}
}

/**
 * Register a callback as an Elgg event handler.
 *
 * Events are emitted by Elgg when certain actions occur.  Plugins
 * can respond to these events or halt them completely by registering a handler
 * as a callback to an event.  Multiple handlers can be registered for
 * the same event and will be executed in order of $priority.  Any handler
 * returning false will halt the execution chain.
 *
 * This function is called with the event name, event type, and handler callback name.
 * Setting the optional $priority allows plugin authors to specify when the
 * callback should be run.  Priorities for plugins should be 1-1000.
 *
 * The callback is passed 3 arguments when called: $event, $type, and optional $params.
 *
 * $event is the name of event being emitted.
 * $type is the type of event or object concerned.
 * $params is an optional parameter passed that can include a related object.  See
 * specific event documentation for details on which events pass what parameteres.
 *
 * @tip If a priority isn't specified it is determined by the order the handler was
 * registered relative to the event and type.  For plugins, this generally means
 * the earlier the plugin is in the load order, the earlier the priorities are for
 * any event handlers.
 *
 * @tip $event and $object_type can use the special keyword 'all'.  Handler callbacks registered
 * with $event = all will be called for all events of type $object_type.  Similarly,
 * callbacks registered with $object_type = all will be called for all events of type
 * $event, regardless of $object_type.  If $event and $object_type both are 'all', the
 * handler callback will be called for all events.
 *
 * @tip Event handler callbacks are considered in the follow order:
 *  - Specific registration where 'all' isn't used.
 *  - Registration where 'all' is used for $event only.
 *  - Registration where 'all' is used for $type only.
 *  - Registration where 'all' is used for both.
 *
 * @warning If you use the 'all' keyword, you must have logic in the handler callback to
 * test the passed parameters before taking an action.
 *
 * @tip When referring to events, the preferred syntax is "event, type".
 *
 * @internal Events are stored in $CONFIG->events as:
 * <code>
 * $CONFIG->events[$event][$type][$priority] = $callback;
 * </code>
 *
 * @param string $event       The event type
 * @param string $object_type The object type
 * @param string $callback    The handler callback
 * @param int    $priority    The priority - 0 is default, negative before, positive after
 *
 * @return bool
 * @link http://docs.elgg.org/Tutorials/Plugins/Events
 * @example events/basic.php    Basic example of registering an event handler callback.
 * @example events/advanced.php Advanced example of registering an event handler
 *                              callback and halting execution.
 * @example events/all.php      Example of how to use the 'all' keyword.
 * @deprecated Use Minds\Core\Events
 */
function elgg_register_event_handler($event, $object_type, $callback, $priority = 500) {
	return \Minds\Core\Events\Dispatcher::register($event, "elgg/event/$object_type", $callback, $priority); // Register event with new system, but prefix it in the oldstyle namespace
}


/**
 * Trigger an Elgg Event and run all handler callbacks registered to that event, type.
 *
 * This function runs all handlers registered to $event, $object_type or
 * the special keyword 'all' for either or both.
 *
 * $event is usually a verb: create, update, delete, annotation.
 *
 * $object_type is usually a noun: object, group, user, annotation, relationship, metadata.
 *
 * $object is usually an Elgg* object assciated with the event.
 *
 * @warning Elgg events should only be triggered by core.  Plugin authors should use
 * {@link trigger_elgg_plugin_hook()} instead.
 *
 * @tip When referring to events, the preferred syntax is "event, type".
 *
 * @internal Only rarely should events be changed, added, or removed in core.
 * When making changes to events, be sure to first create a ticket in trac.
 *
 * @internal @tip Think of $object_type as the primary namespace element, and
 * $event as the secondary namespace.
 *
 * @param string $event       The event type
 * @param string $object_type The object type
 * @param string $object      The object involved in the event
 *
 * @return bool The result of running all handler callbacks.
 * @link http://docs.elgg.org/Tutorials/Core/Events
 * @internal @example events/emit.php Basic emitting of an Elgg event.
 * @deprecated Use Minds\Core\Events
 */
function elgg_trigger_event($event, $object_type, $object = null) {
    return \Minds\Core\Events\Dispatcher::trigger($event, "elgg/event/$object_type", $object, true);
}


/**
 * Intercepts, logs, and displays uncaught exceptions.
 *
 * @warning This function should never be called directly.
 *
 * @see http://www.php.net/set-exception-handler
 *
 * @param Exception $exception The exception being handled
 *
 * @return void
 * @access private
 */
function _elgg_php_exception_handler($exception) {
	try {
		\Minds\Helpers\Log::critical($exception, [ 'exception' => $exception ]);
	} catch (Exception $loggerException) {
		$timestamp = time();
		error_log("Exception #$timestamp: $exception");

		Sentry\captureException($exception);
	}

	// Wipe any existing output buffer
	ob_end_clean();

	header('X-Minds: Something is wrong', true, 500);
	// make sure the error isn't cached
	header("Cache-Control: no-cache, must-revalidate", true);
	header('Expires: Fri, 05 Feb 1982 00:00:00 -0500', true);
    // @note Do not send a 500 header because it is not a server error
}

/**
 * Intercepts catchable PHP errors.
 *
 * @warning This function should never be called directly.
 *
 * @internal
 * For catchable fatal errors, throws an Exception with the error.
 *
 * For non-fatal errors, depending upon the debug settings, either
 * log the error or ignore it.
 *
 * @see http://www.php.net/set-error-handler
 *
 * @param int    $errno    The level of the error raised
 * @param string $errmsg   The error message
 * @param string $filename The filename the error was raised in
 * @param int    $linenum  The line number the error was raised at
 * @param array  $vars     An array that points to the active symbol table where error occurred
 *
 * @return true
 * @throws Exception
 * @access private
 * @todo Replace error_log calls with elgg_log calls.
 */
function _elgg_php_error_handler($errno, $errmsg, $filename, $linenum, $vars = array()) {
	$error = date("Y-m-d H:i:s (T)") . ": \"$errmsg\" in file $filename (line $linenum)";

	switch ($errno) {
		case E_USER_ERROR:
			error_log("PHP ERROR: $error");

			// Since this is a fatal error, we want to stop any further execution but do so gracefully.
			throw new Exception($error);
			break;

		case E_WARNING :
		case E_USER_WARNING :
		case E_RECOVERABLE_ERROR: // (e.g. type hint violation)

			// check if the error wasn't suppressed by the error control operator (@)
			if (error_reporting()) {
				error_log("PHP WARNING: $error");
			}
			break;

		default:
			global $CONFIG;
			if (isset($CONFIG->debug) && $CONFIG->debug === 'NOTICE') {
				error_log("PHP NOTICE: $error");
			}
	}

	return true;
}

/**
 * Catch fatal errors
 */
register_shutdown_function('fatalErrorShutdownHandler');

function fatalErrorShutdownHandler(){

	$last_error = error_get_last();

	if($last_error && $last_error['type'] == E_ERROR && php_sapi_name() != "cli"){
		error_log('Fatal error: '.nl2br(htmlentities(print_r($last_error, true), ENT_QUOTES, 'UTF-8')));
		// Wipe any existing output buffer
		ob_end_clean();
		_elgg_php_error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
		// Wipe any existing output buffer
		ob_end_clean();
		header('Fatal error', true, 500);

		echo file_get_contents(dirname(dirname(dirname(__FILE__))) . '/errors/500.html');
    }

    \Sentry\captureLastError();
}

/**
 * Display or log a message.
 *
 * If $level is >= to the debug setting in {@link $CONFIG->debug}, the
 * message will be sent to {@link elgg_dump()}.  Messages with lower
 * priority than {@link $CONFIG->debug} are ignored.
 *
 * {@link elgg_dump()} outputs all levels but NOTICE to screen by default.
 *
 * @note No messages will be displayed unless debugging has been enabled.
 *
 * @param string $message User message
 * @param string $level   NOTICE | WARNING | ERROR | DEBUG
 *
 * @return bool
 * @since 1.7.0
 * @todo This is complicated and confusing.  Using int constants for debug levels will
 * make things easier.
 */
function elgg_log($message, $level = 'NOTICE') {
	global $CONFIG;

	// only log when debugging is enabled
	if (isset($CONFIG->debug)) {
		// debug to screen or log?
		$to_screen = !($CONFIG->debug == 'NOTICE');

		switch ($level) {
			case 'ERROR':
				// always report
				elgg_dump("$level: $message", $to_screen, $level);
				break;
			case 'WARNING':
			case 'DEBUG':
				// report except if user wants only errors
				if ($CONFIG->debug != 'ERROR') {
					elgg_dump("$level: $message", $to_screen, $level);
				}
				break;
			case 'NOTICE':
			default:
				// only report when lowest level is desired
				if ($CONFIG->debug == 'NOTICE') {
					elgg_dump("$level: $message", FALSE, $level);
				}
				break;
		}

		return TRUE;
	}

	return FALSE;
}

/**
 * Logs or displays $value.
 *
 * If $to_screen is true, $value is displayed to screen.  Else,
 * it is handled by PHP's {@link error_log()} function.
 *
 * A {@elgg_plugin_hook debug log} is called.  If a handler returns
 * false, it will stop the default logging method.
 *
 * @param mixed  $value     The value
 * @param bool   $to_screen Display to screen?
 * @param string $level     The debug level
 *
 * @return void
 * @since 1.7.0
 */
function elgg_dump($value, $to_screen = TRUE, $level = 'NOTICE') {
	global $CONFIG;

	// plugin can return false to stop the default logging method
	$params = array(
		'level' => $level,
		'msg' => $value,
		'to_screen' => $to_screen,
	);

	// Do not want to write to screen before page creation has started.
	// This is not fool-proof but probably fixes 95% of the cases when logging
	// results in data sent to the browser before the page is begun.
	if (!isset($CONFIG->pagesetupdone)) {
		$to_screen = FALSE;
	}

	if ($to_screen == TRUE) {
		echo '<pre>';
		print_r($value);
		echo '</pre>';
	} else {
		error_log(print_r($value, TRUE));
	}
}

/**
 * Sends a notice about deprecated use of a function, view, etc.
 *
 * This function either displays or logs the deprecation message,
 * depending upon the deprecation policies in {@link CODING.txt}.
 * Logged messages are sent with the level of 'WARNING'. Only admins
 * get visual deprecation notices. When non-admins are logged in, the
 * notices are sent to PHP's log through elgg_dump().
 *
 * A user-visual message will be displayed if $dep_version is greater
 * than 1 minor releases lower than the current Elgg version, or at all
 * lower than the current Elgg major version.
 *
 * @note This will always at least log a warning.  Don't use to pre-deprecate things.
 * This assumes we are releasing in order and deprecating according to policy.
 *
 * @see CODING.txt
 *
 * @param string $msg             Message to log / display.
 * @param string $dep_version     Human-readable *release* version: 1.7, 1.8, ...
 * @param int    $backtrace_level How many levels back to display the backtrace.
 *                                Useful if calling from functions that are called
 *                                from other places (like elgg_view()). Set to -1
 *                                for a full backtrace.
 *
 * @return bool
 * @since 1.7.0
 */
function elgg_deprecated_notice($msg, $dep_version, $backtrace_level = 1) {
	// if it's a major release behind, visual and logged
	// if it's a 1 minor release behind, visual and logged
	// if it's for current minor release, logged.
	// bugfixes don't matter because we are not deprecating between them

	if (!$dep_version) {
		return false;
	}

	$elgg_version = Minds\Core\Minds::getVersion();
	$elgg_version_arr = explode('.', $elgg_version);
	$elgg_major_version = (int)$elgg_version_arr[0];
	$elgg_minor_version = (int)$elgg_version_arr[1];

	$dep_major_version = (int)$dep_version;
	$dep_minor_version = 10 * ($dep_version - $dep_major_version);

	$visual = false;

	if (($dep_major_version < $elgg_major_version) ||
		($dep_minor_version < $elgg_minor_version)) {
		$visual = true;
	}

	$msg = "Deprecated in $dep_major_version.$dep_minor_version: $msg";

	// Get a file and line number for the log. Never show this in the UI.
	// Skip over the function that sent this notice and see who called the deprecated
	// function itself.
	$msg .= " Called from ";
	$stack = array();

	return false;
$backtrace = @debug_backtrace(false, 3);
	if(!$backtrace)
		return false; //something is odd
	// never show this call.
	array_shift($backtrace);
	$i = count($backtrace);

	foreach ($backtrace as $trace) {
		$stack[] = "[#$i] {$trace['file']}:{$trace['line']}";
		$i--;

		if ($backtrace_level > 0) {
			if ($backtrace_level <= 1) {
				break;
			}
			$backtrace_level--;
		}
	}

	$msg .= implode("<br /> -> ", $stack);

	elgg_log($msg, 'WARNING');

	return true;
}


/**
 * Normalise the singular keys in an options array to plural keys.
 *
 * Used in elgg_get_entities*() functions to support shortcutting plural
 * names by singular names.
 *
 * @param array $options   The options array. $options['keys'] = 'values';
 * @param array $singulars A list of singular words to pluralize by adding 's'.
 *
 * @return array
 * @since 1.7.0
 * @access private
 */
function elgg_normalise_plural_options_array($options, $singulars) {
	foreach ($singulars as $singular) {
		$plural = $singular . 's';

		if (array_key_exists($singular, $options)) {
			if ($options[$singular] === ELGG_ENTITIES_ANY_VALUE) {
				$options[$plural] = $options[$singular];
			} else {
				// Test for array refs #2641
				if (!is_array($options[$singular])) {
					$options[$plural] = array($options[$singular]);
				} else {
					$options[$plural] = $options[$singular];
				}
			}
		}

		unset($options[$singular]);
	}

	return $options;
}

/**
 * Emits a shutdown:system event upon PHP shutdown, but before database connections are dropped.
 *
 * @tip Register for the shutdown:system event to perform functions at the end of page loads.
 *
 * @warning Using this event to perform long-running functions is not very
 * useful.  Servers will hold pages until processing is done before sending
 * them out to the browser.
 *
 * @see http://www.php.net/register-shutdown-function
 *
 * @return void
 * @see register_shutdown_hook()
 * @access private
 */
function _elgg_shutdown_hook() {

	try {
		elgg_trigger_event('shutdown', 'system');
	} catch (Exception $e) {
		$message = 'Error: ' . get_class($e) . ' thrown within the shutdown handler. ';
		$message .= "Message: '{$e->getMessage()}' in file {$e->getFile()} (line {$e->getLine()})";
		error_log($message);
		error_log("Exception trace stack: {$e->getTraceAsString()}");
	}
}


/**
 * Boots the engine
 *
 * 1. sets error handlers
 * 2. connects to database
 * 3. verifies the installation suceeded
 * 4. loads application configuration
 * 5. loads i18n data
 * 6. loads site configuration
 *
 * @access private
 */
function _elgg_engine_boot() {
	// Register the error handlers
	set_error_handler('_elgg_php_error_handler');
	set_exception_handler('_elgg_php_exception_handler');
}

/**
 * Elgg's main init.
 *
 * Handles core actions for comments, the JS pagehandler, and the shutdown function.
 *
 * @elgg_event_handler init system
 * @return void
 * @access private
 */
function elgg_init() {
	// Trigger the shutdown:system event upon PHP shutdown.
	register_shutdown_function('_elgg_shutdown_hook');
}

/**#@+
 * Controls access levels on ElggEntity entities, metadata, and annotations.
 *
 * @var int
 */
//define('ACCESS_DEFAULT', -1);
define('ACCESS_DEFAULT', -1);
define('ACCESS_SECRET', -3);
define('ACCESS_PRIVATE', 0);
define('ACCESS_LOGGED_IN', 1);
define('ACCESS_PUBLIC', 2);
define('ACCESS_FRIENDS', -2);
/**#@-*/

/**
 * Constant to request the value of a parameter be ignored in elgg_get_*() functions
 *
 * @see elgg_get_entities()
 * @var NULL
 * @since 1.7
 */
define('ELGG_ENTITIES_ANY_VALUE', NULL);

/**
 * Constant to request the value of a parameter be nothing in elgg_get_*() functions.
 *
 * @see elgg_get_entities()
 * @var int 0
 * @since 1.7
 */
define('ELGG_ENTITIES_NO_VALUE', 0);

/**
 * Used in calls to forward() to specify the browser should be redirected to the
 * referring page.
 *
 * @see forward
 * @var int -1
 */
define('REFERRER', -1);

/**
 * Alternate spelling for REFERRER.  Included because of some bad documentation
 * in the original HTTP spec.
 *
 * @see forward()
 * @link http://en.wikipedia.org/wiki/HTTP_referrer#Origin_of_the_term_referer
 * @var int -1
 */
define('REFERER', -1);

elgg_register_event_handler('init', 'system', 'elgg_init');
elgg_register_event_handler('boot', 'system', '_elgg_engine_boot', 1);

