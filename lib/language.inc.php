<?
# Lifter002: DONE - not applicable
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: DONE - not applicable
/**
* language functions
*
* helper functions for handling I18N system
*
*
* @author       Stefan Suchi <suchi@data-quest.de>
* @access       public
* @modulegroup  library
* @module       language.inc
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// language.inc.php
// helper functions for handling I18N system
// Copyright (c) 2003 Stefan Suchi <suchi@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+


/**
* This function tries to find the preferred language
*
* This function tries to find the preferred language.
* It returns the first accepted language from browser settings, which is installed.
*
* @access   public
* @return       string  preferred user language, given in "en_GB"-style
*
*/
function get_accepted_languages() {
    global $INSTALLED_LANGUAGES, $DEFAULT_LANGUAGE;

    $_language = $DEFAULT_LANGUAGE;
    $accepted_languages = explode(",", getenv("HTTP_ACCEPT_LANGUAGE"));
    if (is_array($accepted_languages) && count($accepted_languages)) {
        foreach ($accepted_languages as $temp_accepted_language) {
            foreach ($INSTALLED_LANGUAGES as $temp_language => $temp_language_settings) {
                if (substr(trim($temp_accepted_language), 0, 2) == substr($temp_language, 0, 2)) {
                    $_language = $temp_language;
                    break 2;
                }
            }
        }
    }
    return $_language;
}


/**
* This function starts output via i18n system in the given language
*
* This function starts output via i18n system in the given language.
* It returns the path to the choosen language.
*
* @access   public
* @param        string  the language to use for output, given in "en_GB"-style
* @return       string  the path to the language file, given in "en"-style
*
*/
function init_i18n($_language) {
    global $_language_domain, $INSTALLED_LANGUAGES;

    if (isset($_language_domain) && isset($_language)) {
        $_language_path = $INSTALLED_LANGUAGES[$_language]["path"];
        setLocaleEnv($_language, $_language_domain);
    }
    return $_language_path;
}


/**
* create the img tag for graphic buttons
*
* This function creates the html text for a button.
* Decides, which button (folder)
* is used for international buttons.
*
* @deprecated   2.3  - 2012/02/10
* @access       public
* @param        string  the (german) button name
* @param        string  if mode = img, the functions return the full tag, if mode = src, it return only the src-part , if mode = input returns full input tag
* @param        string  tooltip text, if tooltip should be included in tag
* @param        string  if mode=input this param defines the name attribut
* @return       string  html output of the button
*/
function makeButton($name, $mode = "img", $tooltip = false, $inputname = false) {

    $url = localeButtonUrl($name . '-button.png');
    $tooltext = ($tooltip ? tooltip($tooltip) : '');

    switch ($mode) {

        case 'img':
            $tag = "\n" . sprintf('<img class="button" src="%s" %s >',
                                  $url, $tooltext);
            break;
        
        case 'input':
            $inputname || $inputname = $name;
            $tag = "\n" . sprintf('<input class="button" type="image" src="%s" %s '.
                                  'name="%s" >',
                                $url, $tooltext, $inputname);
            break;


        default:
            $tag = sprintf('class="button" src="%s"', $url);

    }

    return $tag;
}


/**
 * retrieves preferred language of user from database, falls back to default
 * language
 *
 * @access   public
 * @param    string  the user_id of the user in question
 * @return   string  the preferred language of the user or the default language
 */
function getUserLanguage($uid)
{
    global $DEFAULT_LANGUAGE;

    // try to get preferred language from user, fallback to default
    $query = "SELECT preferred_language FROM user_info WHERE user_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($uid));
    $language = $statement->fetchColumn() ?: $DEFAULT_LANGUAGE;

    return $language;
}

/**
* retrieves path to preferred language of user from database
*
* Can be used for sending language specific mails to other users.
*
* @access   public
* @param        string  the user_id of the recipient (function will try to get preferred language from database)
* @return       string  the path to the language files, given in "en"-style
*/
function getUserLanguagePath($uid)
{
    global $INSTALLED_LANGUAGES;

    $language = getUserLanguage($uid);

    return $INSTALLED_LANGUAGES[$language]['path'];
}

/**
* switch i18n to different language
*
* This function switches i18n system to a different language.
* Should be called before writing strings to other users into database.
* Use restoreLanguage() to switch back.
*
* @access   public
* @param        string  the user_id of the recipient (function will try to get preferred language from database)
* @param        string  explicit temporary language (set $uid to FALSE to switch to this language)
*/
function setTempLanguage ($uid = FALSE, $temp_language = "") {
    global $_language_domain, $DEFAULT_LANGUAGE;

    if ($uid) {
        $temp_language = getUserLanguage($uid);
    }

    if ($temp_language == "") {
        // we got no arguments, best we can do is to set system default
        $temp_language = $DEFAULT_LANGUAGE;
    }

    setLocaleEnv($temp_language, $_language_domain);
}


/**
* switch i18n back to original language
*
* This function switches i18n system back to the original language.
* Should be called after writing strings to other users via setTempLanguage().
*
* @access   public
*/
function restoreLanguage() {
    global $_language_domain, $_language;
    setLocaleEnv($_language, $_language_domain);
}

/**
* set locale to a given language and select translation domain
*
* This function tries to set the appropriate environment variables and
* locale settings for the given language and also (optionally) sets the
* translation domain.
* Note: To support non-POSIX compliant systems (SuSE 9.x, OpenSolaris?),
* the environment variables LANG and LC_ALL are also set to $language.
*
* @access   public
*/
function setLocaleEnv($language, $language_domain = ''){
    putenv("LANG=$language");
    putenv("LANGUAGE=$language");
    putenv("LC_ALL=$language");
    $ret = setlocale(LC_ALL, '');
    setlocale(LC_NUMERIC, 'C');
    if($language_domain){
        bindtextdomain($language_domain, $GLOBALS['STUDIP_BASE_PATH'] . "/locale");
        textdomain($language_domain);
    }
    return $ret;
}

function localeButtonUrl($filename) {
  return localeUrl($filename, 'LC_BUTTONS');
}

function localePictureUrl($filename) {
  return localeUrl($filename, 'LC_PICTURES');
}

function localeUrl($filename, $category) {
  return sprintf('%simages/locale/%s/%s/%s',
                 $GLOBALS['ASSETS_URL'],
                 $GLOBALS['_language_path'],
                 $category,
                 $filename);
}

?>
