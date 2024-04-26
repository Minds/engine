<?php
/**
 * Elgg users
 * Functions to manage multiple or single users in an Elgg install
 *
 * @package Elgg.Core
 * @subpackage DataModel.User
 */

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Entities\Repositories\EntitiesRepositoryInterface;
use Minds\Core\Entities\Services\EntitiesRepositoryService;

/// Map a username to a cached GUID
global $USERNAME_TO_GUID_MAP_CACHE;
$USERNAME_TO_GUID_MAP_CACHE = [];

/// Map a user code to a cached GUID
global $CODE_TO_GUID_MAP_CACHE;
$CODE_TO_GUID_MAP_CACHE = [];


/**
 * GET INDEX TO GUID
 */
function get_user_index_to_guid($index)
{
    $isMultiTenant = !!Di::_()->get(Config::class)->get('tenant_id');

    if ($isMultiTenant) {
        /** @var EntitiesRepositoryInterface */
        $entitiesRepository = Di::_()->get(EntitiesRepositoryInterface::class);
        return !!$entitiesRepository->loadFromIndex('username', $index);
    } else {
        try {
            $db = new Minds\Core\Data\Call('user_index_to_guid');
            $row = $db->getRow($index);
            if (!$row || !is_array($row)) {
                return false;
            }
            foreach ($row as $k=>$v) {
                return $k;
            }
        } catch (Exception $e) {
            return false;
        }
    }
}


/**
 * Checks if it exists an entry in user_index_to_guid for a given username
 * @param $username
 * @return bool
 */
function check_user_index_to_guid($username)
{
    global $USERNAME_TO_GUID_MAP_CACHE;
    $guid = isset($USERNAME_TO_GUID_MAP_CACHE[$username]) ? $USERNAME_TO_GUID_MAP_CACHE[$username] : null;

    if (!$guid) {
        $guid = get_user_index_to_guid($username);
    }

    return !!$guid;
}

/**
 * Simple function which ensures that a username contains only valid characters.
 *
 * This should only permit chars that are valid on the file system as well.
 *
 * @param string $username Username
 *
 * @return bool
 * @throws RegistrationException on invalid
 */
function validate_username($username, bool $isActivityPub = false)
{
    global $CONFIG;

    // Basic, check length
    if (!isset($CONFIG->minusername)) {
        $CONFIG->minusername = 4;
    }

    if (strlen($username) < $CONFIG->minusername) {
        $msg = 'registration:usernametooshort';
        throw new RegistrationException($msg);
    }

    // username in the database has a limit of 128 characters
    if (strlen($username) > 128) {
        $msg = 'registration:usernametoolong';
        throw new RegistrationException($msg);
    }

    // Blacklist non-alpha chars
    if (preg_match('/[^a-zA-Z0-9_\-@.]+/', $username)) {
        throw new RegistrationException('Invalid username! Alphanumerics only please.');
    }

    if (strpos($username, '@') !== FALSE && !$isActivityPub) {
        throw new RegistrationException('Invalid username! You can not include the @ character in your username.');
    }

    // Blacklist for bad characters (partially nicked from mediawiki)
    $blacklist = '/[' .
        '\x{0080}-\x{009f}' . // iso-8859-1 control chars
        '\x{00a0}' .          // non-breaking space
        '\x{2000}-\x{200f}' . // various whitespace
        '\x{2028}-\x{202f}' . // breaks and control chars
        '\x{3000}' .          // ideographic space
        '\x{e000}-\x{f8ff}' . // private use
        ']/u';

    if (
        preg_match($blacklist, $username)
    ) {
        // @todo error message needs work
        throw new RegistrationException('registration:invalidchars');
    }

    // Belts and braces
    // @todo Tidy into main unicode
    $blacklist2 = '\'/\\"*& ?#%^(){}[]~?<>;|¬`+=';

    for ($n = 0; $n < strlen($blacklist2); $n++) {
        if (strpos($username, $blacklist2[$n]) !== false) {
            $msg = 'registration:invalidchars';
            throw new RegistrationException($msg);
        }
    }

    $result = true;
    return $result;
}

/**
 * Simple validation of a password.
 *
 * @param string $password Clear text password
 *
 * @return bool
 * @throws RegistrationException on invalid
 */
function validate_password($password)
{
    global $CONFIG;

    if (!isset($CONFIG->min_password_length)) {
        $CONFIG->min_password_length = 6;
    }

    //Check for a uppercase character, numeric character,special character
    if (strlen($password) < $CONFIG->min_password_length
        || !preg_match('/[A-Z]/', $password)
        || !preg_match('/\d/', $password)
        || !preg_match('/[^a-zA-Z\d]/', $password)
        || preg_match("/\\s/", $password)
    ) {
        $msg = "Password must have 8 characters or more. Including uppercase, numbers, special characters (ie. !,#,@), and cannot have spaces.";
        throw new RegistrationException($msg);
    }

    $result = true;
    return $result;
}

/**
 * Simple validation of a email.
 *
 * @param string $address Email address
 *
 * @throws RegistrationException on invalid
 * @return bool
 */
function validate_email_address($address)
{
    if (!is_email_address($address)) {
        throw new RegistrationException('registration:notemail');
    }

    // Got here, so lets try a hook (defaulting to ok)
    $result = true;
    return $result;
}

/**
 * Registers a user, returning false if the username already exists
 *
 * @param string $username              The username of the new user
 * @param string $password              The password
 * @param string $name                  The user's display name
 * @param string $email                 Their email address
 * @param bool   $allow_multiple_emails Allow the same email address to be
 *                                      registered multiple times?
 * @param int    $friend_guid           GUID of a user to friend once fully registered
 * @param string $invitecode            An invite code from a friend
 *
 * @return false|Minds\Entities\User
 * @throws RegistrationException
 */
function register_user(
    $username,
    $password,
    $name,
    $email,
    $allow_multiple_emails = false,
    $friend_guid = 0,
    $invitecode = '',
    $validatePassword = true,
    $isActivityPub = false,
) {

    // no need to trim password.
    $username = strtolower(trim($username));
    $name = trim(strip_tags($name));
    $email = trim($email);

    // A little sanity checking
    if (empty($username)
    || empty($password)
    || empty($name)
    || empty($email)) {
        return false;
    }

    if (!validate_email_address($email)) {
        throw new RegistrationException("Invalid email");
    }

    if ($validatePassword && !validate_password($password)) {
        throw new RegistrationException("Invalid password");
    }

    if (!validate_username($username, $isActivityPub)) {
        throw new RegistrationException("Invalid username");
    }

    if (check_user_index_to_guid($username)) {
        throw new RegistrationException("Username already in use");
    }

    // Create user
    $user = new Minds\Entities\User();
    $user->username = $username;
    $user->setEmail($email);
    $user->name = $name;
    $user->access_id = ACCESS_PUBLIC;
    //$user->salt = generate_random_cleartext_password(); // Note salt generated before password!
    $user->password = Minds\Core\Security\Password::generate($user, $password);
    $user->owner_guid = 0; // Users aren't owned by anyone, even if they are admin created.
    $user->container_guid = 0; // Users aren't contained by anyone, even if they are admin created.
    $user->language = 'en';

    $user->enabled = 'yes';

    $save = new Save();
    $save->setEntity($user)->save();

    return $user;
}

/**
 * Sets the last logon time of the given user to right now.
 *
 * @param User $User
 *
 * @return void
 */
function set_last_login($user)
{
    $time = time();

    $user->last_login = $time;
    $user->ip = $_SERVER['REMOTE_ADDR'];
    
    $save = new Save();
    
    $save->setEntity($user)
        ->withMutatedAttributes([
            'last_login',
            'ip',
        ])
        ->save();
}
