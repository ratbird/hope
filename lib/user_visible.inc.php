<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
user_visible.inc.php - Functions for determining a users visibility
Copyright (C) 2004 Till Glöggler <virtuos@snowysoft.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

require_once 'functions.php';
require_once 'lib/classes/UserDomain.php';

// Define constants for visibility states.
define("VISIBILITY_ME", 1);
define("VISIBILITY_BUDDIES", 2);
define("VISIBILITY_DOMAIN", 3);
define("VISIBILITY_STUDIP", 4);
define("VISIBILITY_EXTERN", 5);

/*
 * A function to determine a users visibility
 *
 * @param   $user_id    user-id
 * @returns boolean true: user is visible, false: user is not visible
 */
function get_visibility_by_id ($user_id) {
    global $perm;
    if ($perm->have_perm("root")) return true;

    $db = new DB_Seminar("SELECT visible FROM auth_user_md5 WHERE user_id = '$user_id'");
    $db->next_record();
    return get_visibility_by_state($db->f("visible"), $user_id);
}

/*
 * A function to determine a users visibility
 *
 * @param   $username   username
 * @returns boolean true: user is visible, false: user is not visible
 */
function get_visibility_by_username ($username) {
    global $perm;
    if ($perm->have_perm("root")) return true;

    $db = new DB_Seminar("SELECT visible, user_id FROM auth_user_md5 WHERE username = '$username'");
    $db->next_record();
    return get_visibility_by_state($db->f("visible"), $db->f("user_id"));
}

/*
 * A function to determine, whether a given state means 'visible' or 'invisible'
 *
 * @param   $stat   ['global', 'always', 'yes', 'unknown', 'no', 'never']
 * @param   $user_id        id of user that should be checked
 * @returns boolean true: state means 'visible', false: state means 'invisible'
 */
function get_visibility_by_state ($state, $user_id) {
    global $auth;

    $same_domain = true;
    $my_domains = UserDomain::getUserDomainsForUser($auth->auth['uid']);
    $user_domains = UserDomain::getUserDomainsForUser($user_id);

    if (count($my_domains) > 0 || count($user_domains) > 0) {
        $same_domain = count(array_intersect($user_domains, $my_domains)) > 0;
    }

    switch ($state) {
        case "global":
            return true;
            break;
        case "yes":
        case "always":
            return $same_domain;
            break;
        case "unknown":
            return $same_domain && get_config('USER_VISIBILITY_UNKNOWN');
            break;
        case "no":
        case "never":
            return false;
            break;

        default:
            return false;
            break;
    }
    return false;
}

/*
 * This function returns a query-snip for selecting with current visibility rights
 * @returns string  returns a query string
 */
function get_vis_query($table_alias = 'auth_user_md5', $context='') {
    global $auth, $perm;

    if ($perm->have_perm("root")) return "1";

    $my_domains = UserDomain::getUserDomainsForUser($auth->auth['uid']);
    $query = "$table_alias.visible = 'global'";

    /*
     *  Check if the user has set own visibilities or if the system default
     *  should be used.
     */
    if ($context) {
        $context_default = (int) get_config(strtoupper($context).'_VISIBILITY_DEFAULT');
        $contextQuery = " AND (IFNULL(user_visibility.$context, ".
            "$context_default) = 1 OR $table_alias.visible = 'always')";
    }

    // are users with visibility "unknown" treated as visible?
    $unknown = get_config('USER_VISIBILITY_UNKNOWN');

    foreach ($my_domains as $domain) {
        $my_domain_ids[] = $domain->getID();
    }

    if (count($my_domains) == 0) {
        $query .= " OR NOT EXISTS (SELECT * FROM user_userdomains WHERE user_id = $table_alias.user_id)";
    } else {
        $query .= " OR EXISTS (SELECT * FROM user_userdomains WHERE user_id = $table_alias.user_id
                    AND userdomain_id IN ('".implode("','", $my_domain_ids)."'))";
    }

    $query .= " AND ($table_alias.visible = 'always' OR $table_alias.visible = 'yes'";

    if ($unknown) {
        $query .= " OR $table_alias.visible = 'unknown'";
    }
    $query .= ")";

    return "($query) $contextQuery";
}

function get_ext_vis_query($table_alias = 'aum') {
    $query = "$table_alias.visible = 'global' OR $table_alias.visible = 'always' OR $table_alias.visible = 'yes'";

    if (get_config('USER_VISIBILITY_UNKNOWN')) {
        $query .= " OR $table_alias.visible = 'unknown'";
    }

    return "($query)";
}

/*
 * A function to create a chooser for a users visibility
 *
 * @param   $vis    visibility-state
 * @returns string  gives back a string with the chooser
 */
function vis_chooser($vis, $new = false) {
    if ($vis == '') $vis = 'unknown';
    $txt = array();
    $txt[] = '<select name="visible">';
    if (!$new) $txt[] = '<option value="'.$vis.'">'._("keine &Auml;nderung").'</option>';
    $txt[] = '<option value="always">'._("immer").'</option>';
    /* $txt[] = '<option value="yes">'._("ja").'</option>'; */
    $txt[] = '<option value="unknown"'.(($new)? ' selected="selected"':'').'>'._("unbekannt").'</option>';
    /* $txt[] = '<option value="no">'._("nein").'</option>'; */
    $txt[] = '<option value="never">'._("niemals").'</option>';
    $txt[] = '</select>';
    return implode("\n", $txt);
}

// Ask user with unknown visibility state directly after login
// whether they want to be visible or invisible
//
// ATTENTION: NOT USED IN STANDARD DISTRIBUTION.
// see header.php for further info on enabling this feature.
//       
// DON'T USE UNMODIFIED TEXTS!
//
function first_decision($userid) {
    global $PHP_SELF, $vis_cmd, $vis_state, $auth;

    $user_language=getUserLanguagePath($userid);
    if ($vis_cmd == "apply" && ($vis_state == "global" || $vis_state == "yes" || $vis_state == "no")) {
        $db = new DB_Seminar("UPDATE auth_user_md5 SET visible = '$vis_state' WHERE user_id = '$userid'");
        return;
    }

    $db = new DB_Seminar("SELECT auth_user_md5.visible, user_info.preferred_language as pl FROM auth_user_md5, user_info WHERE auth_user_md5.user_id = '$userid' AND auth_user_md5.user_id = user_info.user_id");
    $db->next_record();
    if ($db->f("visible") != "unknown") return;
    ?>
    <table width="80%" align="center" border=0 cellpadding=0 cellspacing=0>
    <tr>
        <td class="topic" colspan="3" valign="top">
            <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/white/door-enter.png" border="0"><b>&nbsp;<?=_("Bitte wählen Sie Ihren Sichtbarkeitsstatus aus!")?></b>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan="3">&nbsp;</td>
    </tr>
    <tr>
        <td class="blank" width="1%"></td>
        <td class="blank">
            <center>
            <?
        include("locale/$user_language/LC_HELP/visibility_decision.php");
            ?>
            </center>
        </td>
    </tr>
    </table>
    <?
    page_close();
    die;
}

/**
 * Gets the global visibility for the given user ID.
 * 
 * @param string $user_id user ID to check
 * @return string User visibility.
 */
function get_global_visibility_by_id($user_id) {
    $stmt = DBManager::get()->query("SELECT visible FROM auth_user_md5 WHERE user_id='".$user_id."'");
    $data = $stmt->fetch();
    return $data['visible'];
}

/**
 * Gets the global visibility for a given username. This is just a wrapper
 * function for <code>get_global_visibility_by_id</code>, operating with a
 * usename instead of an ID.
 * 
 * @param string $username username to check
 * @return string User visibility as returned by 
 * <code>get_global_visibility_by_id</code>.
 */
function get_global_visibility_by_username($username) {
    return get_global_visibility_by_id(get_userid($username));
}

/**
 * Gets a user's visibility settings for special context. Valid contexts are 
 * at the moment:
 * <ul>
 * <li><b>online</b>: Visibility in "Who is online" list</li>
 * <li><b>chat</b>: Visibility of private chatroom in active chats list</li>
 * <li><b>search</b>: Can the user be found via person search?</li>
 * <li><b>email</b>: Is user's email address shown?</li>
 * <li><b>homepage</b>: Visibility of all user homepage elements, stored as 
 * JSON-serialized array</li>
 * </ul>
 * 
 * @param string $user_id user ID to check
 * @param string $context local visibility in which context?
 * @param boolean $return_user_perm return not only visibility, but also 
 * the user's global permission level
 * @return mixed Visibility flag or array with visibility and user permission 
 * level.
 */
function get_local_visibility_by_id($user_id, $context, $return_user_perm=false) {
    global $NOT_HIDEABLE_FIELDS;
    $stmt = DBManager::get()->query("SELECT a.`perms`, u.`".$context.
        "` FROM `auth_user_md5` a LEFT JOIN `user_visibility` u ON ".
        "(u.`user_id`=a.`user_id`) WHERE a.`user_id`='".$user_id."'");
    $data = $stmt->fetch();
    if ($data[$context] === null) {
        $user_perm = $data['perm'];
        $data['perms'] = $user_perm;
        if (get_config(strtoupper($context).'_VISIBILITY_DEFAULT')) {
            $data[$context] = true;
        } else {
            $data[$context] = false;
        }
    }
    // Valid context given.
    if ($data[$context]) {
        // Context may not be hidden per global config setting.
        if ($NOT_HIDEABLE_FIELDS[$data['perms']][$context]) {
            $result = true;
        } else {
            // Give also user's permission level.
            if ($return_user_perm) {
                $result = array(
                    'perms' => $data['perms'],
                    $context => $data[$context]
                );
            } else {
                $result = $data[$context];
            }
        }
    } else {
        $result = false;
    }
    return $result;
}

/**
 * Wrapper function for checking a user's local visibility by username 
 * instead of user ID.
 * 
 * @param string $username username to check
 * @param string $context local visibility in which context? 
 * @see get_local_visibility_by_id
 * @param boolean $return_user_perm return not only visibility, but also 
 * the user's global permission level
 * @return mixed Visibility flag or array with visibility and user permission 
 * level.
 */
function get_local_visibility_by_username($username, $context, $return_user_perm=false) {
    return get_local_visibility_by_id(get_userid($username), $context, $return_user_perm);
}

/**
 * Checks whether an element of a user homepage is visible for another user.
 * We do not give an element name and look up its visibility setting in the 
 * database, because that would generate many database requests for a single 
 * user homepage. Instead, the homepage itself loads all element visibilities
 * and we only need to check if the given element visibility allows showing it 
 * to the visiting user. We need not check for not hideable fields here, 
 * because that is already done when loading the element visibilities.
 * 
 * @param string $user_id ID of the user who wants to see the element
 * @param string $owner_id ID of the homepage owner
 * @param int $element_visibility visibility level of the element, one of 
 * the constants VISIBILITY_ME, VISIBILITY_BUDDIES, VISIBILITY_DOMAIN, 
 * VISIBILITY_STUDIP, VISIBILITY_EXTERN
 * @return boolean Is the element visible?
 */
function is_element_visible_for_user($user_id, $owner_id, $element_visibility) {
    $is_visible = false;
    if ($user_id == $owner_id) {
        $is_visible = true;
    // Deputies with homepage editing rights see the same as the owner
    } else if (get_config('DEPUTIES_ENABLE') && get_config('DEPUTIES_DEFAULTENTRY_ENABLE') && get_config('DEPUTIES_EDIT_ABOUT_ENABLE') && isDeputy($user_id, $owner_id, true)) {
        $is_visible = true;
    } else {
        // No element visibility given (user has not configured this element yet)
        // Set default visibility as element visibility
        if (!$element_visibility) {
            $element_visibility = get_default_homepage_visibility($owner_id);
        }
        // Check if the given element is visible according to its visibility.
        switch ($element_visibility) {
            case VISIBILITY_EXTERN:
                $is_visible = true;
                break;
            case VISIBILITY_STUDIP:
                if ($user_id != "nobody") {
                    $is_visible = true;
                }
                break;
            case VISIBILITY_DOMAIN:
                $user_domains = UserDomain::getUserDomainsForUser($user_id);
                $owner_domains = UserDomain::getUserDomainsForUser($owner_id);
                if (array_intersect($user_domains, $owner_domains)) {
                    $is_visible = true;
                }
                break;
            case VISIBILITY_BUDDIES:
                if (CheckBuddy(get_username($user_id), $owner_id) || $owner_id == $user_id) {
                    $is_visible = true;
                }
                break;
            case VISIBILITY_ME:
                if ($owner_id == $user_id) {
                    $is_visible = true;
                }
                break;
        }
    }
    return $is_visible;
}

/**
 * Checks whether a homepage element is visible on external pages. 
 * We do not give an element name and look up its visibility setting in the 
 * database, because that would generate many database requests for a single 
 * user homepage. Instead, the homepage itself loads all element visibilities
 * and we only need to check if the given element visibility allows showing it.
 * 
 * @param string $owner_id user ID of the homepage owner
 * @param string $owner_perm permission level of the homepage owner, needed 
 * because every permission level can have its own not hideable fields.
 * @param string $field_name Name of the homepage field to check, needed for 
 * checking if the element is not hideable
 * @param int $element_visibility visibility level of the element, one of 
 * the constants VISIBILITY_ME, VISIBILITY_BUDDIES, VISIBILITY_DOMAIN, 
 * VISIBILITY_STUDIP, VISIBILITY_EXTERN
 * @return boolean May the element be shown on external pages?
 */
function is_element_visible_externally($owner_id, $owner_perm, $field_name, $element_visibility) {
    global $NOT_HIDEABLE_FIELDS;
    $is_visible = false;
    if (!isset($element_visibility)) {
        $element_visibility = get_default_homepage_visibility($owner_id);
    }
    if ($element_visibility == VISIBILITY_EXTERN || $NOT_HIDEABLE_FIELDS[$owner_perm][$field_name])
        $is_visible = true;
    return $is_visible;
}

/**
 * Retrieves the standard visibility level for a homepage element if the user 
 * hasn't specified anything explicitly. This default can be set via the global 
 * configuration (variable "HOMEPAGE_VISIBILITY_DEFAULT").
 * 
 * @return int Default visibility level.
 */
function get_default_homepage_visibility($user_id) {
    $result = DBManager::get()->query("SELECT `default_homepage_visibility` ".
        "FROM `user_visibility` WHERE `user_id`='".$user_id."' LIMIT 1");
    $data = $result->fetch();
    if (intval($data['default_homepage_visibility']) != 0) {
        $result = $data['default_homepage_visibility'];
    } else {
        $result = @constant(Config::getInstance()->getValue('HOMEPAGE_VISIBILITY_DEFAULT'));
        if (!$result) {
            $result = VISIBILITY_STUDIP;
        }
    }
    return $result;
}

/**
 * Gets a user's email address. If the address should not be shown according 
 * to the user's privacy settings, we try to get the email address of the 
 * default institute (this can be one of the institutes the user is assigned 
 * to). If no default institute is found, the email address of the first found
 * institute is given. If the user isn't assigned to any institute, an empty 
 * string is returned.
 * 
 * @param string $user_id which user's email address is required?
 * @return string User email address or email address of the user's default 
 * institute or empty string.
 */
function get_visible_email($user_id) {
    $result = '';
    // Email address is visible -> just show user's address.
    if (get_local_visibility_by_id($user_id, 'email')) {
        $data = DBManager::get()->query("SELECT Email FROM auth_user_md5 WHERE user_id='".$user_id."'");
        if ($current = $data->fetch()) {
            $result = $current['Email'];
        }
    // User's email is not visible -> iterate through institute assignments
    } else {
        $data = DBManager::get()->query("SELECT i.email, u.externdefault 
            FROM user_inst u JOIN Institute i USING (Institut_id) 
            WHERE u.user_id='".$user_id."' AND u.inst_perms != 'user' 
            ORDER BY u.priority");
        while ($current = $data->fetch()) {
            if (!$result || $current['externdefault']) {
                $result = $current['email'];
            }
        }
    }
    return $result;
}

/**
 * Gets the visibility setting of a special element on a user's homepage.
 * The resulting value is one of the constants VISIBILITY_ME, 
 * VISIBILITY_BUDDIES, VISIBILITY_DOMAIN, VISIBILITY_STUDIP and 
 * VISIBILITY_EXTERN.
 * 
 * @param string $user_id which user is required?
 * @param string $element_name unique name of the homepage element.
 * @return int Visibility of the given element or default system visibility 
 * if the user hasn't set anything special.
 */
function get_homepage_element_visibility($user_id, $element_name) {
    $visibilities = get_local_visibility_by_id($user_id, 'homepage');
    $visibilities = json_decode($visibilities, true);
    if (isset($visibilities[$element_name])) {
        return $visibilities[$element_name];
    } else {
        return get_default_homepage_visibility($user_id);
    }
}

/**
 * Sets the visibility of a homepage element to the given value.
 * 
 * @param string $user_id whose homepage is it?
 * @param string $element_name unique name of the homepage element to change
 * @param int $visibility new value for element visibility
 * @return int Number of affected database rows.
 */
function set_homepage_element_visibility($user_id, $element_name, $visibility) {
    $visibilities = get_local_visibility_by_id($user_id, 'homepage');
    $visibilities = json_decode($visibilities, true);
    $visibilities[$element_name] = $visibility;
    return DBManager::get()->exec("UPDATE user_visibility SET homepage='".
        json_encode($visibilities)."' WHERE user_id='".$user_id."'");
}

?>
