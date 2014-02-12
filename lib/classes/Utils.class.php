<?php namespace Studip;
/**
 * Utils.class.php - Various utility functions.
 *
 * These functions where originally implemented as part of the RichTextPlugin 
 * and are required by some parts of the WYSIWYG editor implementation.
 *
 * URL Utils
 * ---------
 * getUrl                   Return URL that was called by the web client.
 * getBasename              Return filename of currently executed PHP script.
 * getBaseUrl               Like getUrl but exclude base name and everything thereafter.
 *
 * Stud.IP Utils
 * -------------
 * getSeminarId             Return the selected seminar's identifier.
 *
 * Database Utils
 * --------------
 * executeQuery             Execute a database query and return it's results.
 *
 * Document Utils
 * --------------
 * getFolder                Return a Stud.IP folder's database entry.
 * folderIdExists           Return TRUE if a folder with the given ID exists.
 * folderExists             Return TRUE if a folder with the given ID exists.
 * getFolderId              Return a folder's identifier.
 * createFolder             Create a new Stud.IP folder or return an existing one.
 * getUploadedFiles         Return normalized $_FILES array.
 * uploadFile               Create a new Stud.IP document from an uploaded file.
 * verifyUpload             Throw exception if upload of given file is forbidden.
 * getStudipDocumentData    Return metadata for creating a new Stud.IP document.
 * getDownloadLink          Get download link for a file.
 * getFilename              Return file name of a Stud.IP document.
 *
 * String Utils
 * ------------
 * startsWith               Test if string starts with prefix.
 * endsWith                 Test if string ends with suffix.
 * removePrefix             Remove prefix from string.
 *
 * URL / Media Proxy Utils
 * -----------------------
 * getMediaUrl              Return proxied URL, if media proxy is active.
 * removeStudipDomain       Remove domain name from internal URLs.
 * tranformInternalIdnaLink Return a normalized, internal URL.
 * encodeMediaProxyUrl      Return media proxy URL for a given URL.
 * isStudipMediaUrl         Test if an URL points to internal Stud.IP media path.
 * getStudipRelativePath    Return URL path component relative to Stud.IP path.
 * decodeMediaProxyUrl      Extract the original URL from a media proxy URL.
 * getMediaProxyPath        Return just the path of Stud.IP's media proxy URL.
 * getMediaProxyUrl         Return Stud.IP's absolute media proxy URL.
 * isStudipUrl              Test if URL points to internal Stud.IP resource.
 * getParsedStudipUrl       Return associative array with Stud.IP URL elements.
 * isStudipMediaUrlPath     Test if path is valid for internal Stud.IP media URLs.
 *
 * Access Permission Utils
 * -----------------------
 * hasPermission            Test if current user has required access level.
 * verifyPermission         Throw exception if user hasn't required access level.
 * verifyPostRequest        Throw exception if HTTP request was not send as POST.
 *
 * HTTP Utils
 * ----------
 * utf8POST                 Decode a UTF-8 encoded POST variable.
 *
 * Stud.IP Utils
 * -------------
 * getConfigValue           Return a configuration value from the Stud.IP DB.
 *
 **
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category    Stud.IP
 * @copyright   (c) 2013 Stud.IP e.V.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @since       File available since Release 3.0
 * @author      Robert Costa <rcosta@uos.de>
 */

class Utils
{

    /**
     * Get the current URL as called by the web client.
     *
     * @return string  The current URL.
     *
     * Originally posted on http://stackoverflow.com/a/2820771 by user macek.
     */
    static function getUrl() {
        // TODO move condition to function "httpsActive()"
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Get the file name of the currently executed PHP script.
     *
     * @return string  Filename of currently executed PHP script.
     */
    static function getBasename() {
        return basename($_SERVER['PHP_SELF']);
    }
    
    /**
     * Like getUrl but exclude base name and everything thereafter.
     *
     * Get the base URL including the directory path, excluding file name, 
     * query string, etc.
     *
     * return string  Base URL of client request.
     */
    static function getBaseUrl() {
        $url = Utils::getUrl();
        $pos = \strpos($url, Utils::getBasename());
        // remove current script name, query, etc.
        // only keep host URL and directory part of path
        return \substr($url, 0, $pos);
    }
    
    /**
     * Return the selected seminar's identifier.
     *
     * @return mixed  Seminar identifier (string) or FALSE (boolean) if no
     *                seminar is selected.
     */
    static function getSeminarId() {
        if (\Request::option('cid')) {
            return \Request::option('cid');
        }
        if ($GLOBALS['SessionSeminar']) {
            \URLHelper::bindLinkParam('cid', $GLOBALS['SessionSeminar']);
            return $GLOBALS['SessionSeminar'];
        }
        return false;
    }
    
    /**
     * Execute a database query and return it's results.
     *
     * Do not use this function to fetch large result sets!
     * Result format is as defined by PDO::ATTR_DEFAULT_FETCH_MODE.
     *
     * @param string  $query       SQL query to execute.
     * @param array   $parameters  Parameters for the SQL query.
     * @param boolean $fetch       If set to FALSE fetchAll() is not executed.
     * @return mixed               Array of result set rows (empty for zero
     *                             results), or PDOStatement if $fetch is FALSE.
     *                             Returns FALSE on failure.
     */
    static function executeQuery($query, $parameters, $fetch=TRUE) {
        $statement = \DBManager::get()->prepare($query);
        if (!$statement->execute($parameters)) {
            return FALSE;
        }
        return $fetch ? $statement->fetchAll() : $statement;
    }
    
    /**
     * Return a Stud.IP folder's database entry.
     *
     * The returned array uses the DB table's column names as keys: name,
     * folder_id, description, range_id, seminar_id, user_id, permission.
     *
     * @param string $id  Folder identifier.
     * @returns array     Folder data. NULL if folder doesn't exist or
     *                    something went wrong.
     */
    static function getFolderById($id) {
        $result = Utils::executeQuery('SELECT * FROM folder WHERE folder_id=:id',
                               Array(':id' => $id));
        return $result ? $result[0] : NULL;
    }
    
    /**
     * Return database entries of Stud.IP folders with a specific name.
     *
     * The returned array uses the DB table's column names as keys: name,
     * folder_id, description, range_id, seminar_id, user_id, permission.
     *
     * Only folders of the current seminar are returned.
     *
     * @param string $name  Folder name.
     * @returns array       Folder data. NULL if folder doesn't exist or
     *                      something went wrong.
     */
    static function getFolderByName($name) {
        return Utils::executeQuery(
            'SELECT * FROM folder WHERE name=:name AND seminar_id=:seminar_id',
            Array(':name' => $id, ':seminar_id' => Utils::getSeminarId()));
    }
    
    /**
     * Return TRUE if a folder with the given ID exists.
     */
    static function folderIdExists($id) {
        return (bool) Utils::getFolderById($id);
    }
    
    /**
     * Return TRUE if a folder name is already used in the current seminar.
     */
    static function folderNameExists($name) {
        return (bool) Utils::getFolderByName($name);
    }
    
    /**
     * Return a random folder name that isn't used already.
     *
     * @params string $prefix   Prefix of the folder name (optional).
     * @params int    $retries  Maximum number of retries, should created
     *                          names already exist.
     * @returns string          Unused, random folder name.
     *                          NULL if no unused name was found.
     */
    static function randomFolderName($prefix='', $retries=99) {
        while ($retries >= 0) {
            $retries--;
            $name = uniqid($prefix);
            if (!Utils::folderNameExists($name)) {
                return $name;
            }
        }
        return NULL;
    }
    
    /**
     * Return a folder's identifier.
     *
     * @params string $name       Folder name.
     * @params string $parent_id  Parent folder's ID, NULL for top-level
     *                            folders.
     * @return string             Folder ID if folder exists, NULL if not.
     */
    static function getFolderId($name, $parent_id=NULL) {
        $result = Utils::executeQuery(
            'SELECT folder_id FROM folder WHERE name=:name AND range_id=:range_id',
            Array(':name' => $name, ':range_id' => $parent_id ?: Utils::getSeminarId()));
        return $result ? $result[0]['folder_id'] : NULL;
    }
    
    /**
     * Create a new Stud.IP folder or return an existing one.
     *
     * @param string $name        Folder name.
     * @param string $description Folder description. Only used if folder
     *                            doesn't already exist.
     * @param string $parent_id   Parent folder's ID, NULL for top-level
     *                            folders.
     * @param int    $permission  Folder access permissions.
     * @return string             Folder ID, NULL if something went wrong.
     */
    static function createFolder($name, $description=NULL, $parent_id=NULL, $permission=7) {
        $id = Utils::getFolderId($name, $parent_id);
        if ($id) {
            return $id;  // folder already exists
        }
    
        $seminar_id = Utils::getSeminarId();
        $parent_id = $parent_id ?: $seminar_id;
        $id = md5($seminar_id . $parent_id . $name);
    
        $data = Array(':name'        => $name,
                      ':folder_id'   => $id,
                      ':description' => $description,
                      ':range_id'    => $parent_id,
                      ':seminar_id'  => $seminar_id,
                      ':user_id'     => $GLOBALS['user']->id,
                      ':permission'  => $permission);
    
        $keys = array_keys($data);
        $column_names = implode(',', array_map(function($key) {
            return substr($key, 1);
        }, $keys));
    
        $query = 'INSERT INTO folder (' . $column_names
            . ', mkdate, chdate) VALUES (' . implode(',', $keys)
            . ', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())';

        if (Utils::executeQuery($query, $data, FALSE)) {
            return $id;  // folder successfully created
        }
        throw new \AccessDeniedException(
            _('Stud.IP-Ordner konnte nicht erstellt werden.'));
    }

    /**
     * Transpose an array of arrays.
     *
     * The input array must be of the form:
     *
     * [0 => [0 => value11, 1 => value12, 2 => value13, ...],
     *  1 => [0 => value21, 1 => value22, ...],
     *  ...]
     *
     * The output array will then have the form:
     *
     * [0 => [0 => value11, 1 => value21, ...],
     *  1 => [0 => value12, 1 => value22, ...],
     *  2 => [0 => value13, ...],
     *  ...]
     *
     * Outer array keys pointing to empty arrays will be removed. For
     * example: Transposing ['a' => []] results in [[]].
     *
     * Note that PHP automatically assigns keys starting at 0 if none are
     * set explicitely. Therefore ['a' => [], [], []] equals
     * ['a' => [], 0 => [], 1 => []].
     *
     * @param array $a  Input, an array of arrays.
     * @returns array   Transposed form of input.
     *                  NULL if input is not an array of arrays.
     */
    static function transposeArray($a) {
        echo '';
        if (!is_array($a)) {
            return NULL;
        }
        $b = array();
        foreach($a as $rowKey => $row){
            if (!is_array($row)) {
                return NULL;
            }
            if (empty($row)) {
                $b[] = array();
                continue;
            }
            foreach($row as $columnKey => $value){
                $b[$columnKey][$rowKey] = $value;
            }
        }
        return $b;
    }
    
    /**
     * Normalize $_FILES for HTML array upload of multiple files.
     *
     * $_FILES must have the following structure (HTML array upload):
     *
     * ['files' => ['name'     => [name1, name2, ...],
     *              'tmp_name' => [tmp1, tmp2, ...],
     *              'type'     => [type1, type2, ...],
     *              'size'     => [size1, size2, ...],
     *              'error'    => [error1, error2, ...],
     *              ...]
     *
     * The return value will have the structure:
     *
     * [['name'     => name1,
     *   'tmp_name' => tmp1,
     *   'type'     => type1,
     *   'size'     => size1,
     *   'error'    => error1,
     *   ...],
     *  ['name'     => name2,
     *   'tmp_name' => tmp2,
     *   'type'     => type2,
     *   'size'     => size2,
     *   'error'    => error2,
     *   ...],
     *  ...]
     * 
     * @return array  Each entry is an associative array for a single file.
     */
    static function getUploadedFiles(){
        // TODO improve description
        // TODO make it work with any kind of file upload, not only HTML array
        $files = Utils::transposeArray($_FILES['files']) ?: array();
        return $files == array(array()) ? array() : $files;
    }
    
    /**
     * Create a new Stud.IP document from an uploaded file.
     *
     * @param array  $file       Metadata of uploaded file.
     * @param string $folder_id  ID of Stud.IP folder to which file is uploaded.
     * @return StudipDocument    Stud.IP document.
     * @throws AccessDeniedException if file is forbidden or upload failed.
     */
    static function uploadFile($file, $folder_id) {
        Utils::verifyUpload($file);  // throw exception if file is forbidden
    
        $newfile = \StudipDocument::createWithFile(
            $file['tmp_name'],
            Utils::getStudipDocumentData($folder_id, $file));
    
        if (!$newfile) { // file creation failed
            throw new \AccessDeniedException(
                _('Stud.IP-Dokument konnte nicht erstellt werden.'));
        }
        return $newfile;
    }
    
    /**
     * Throw exception if upload of given file is forbidden.
     *
     * @param Array $file  PHP file info array of uploaded file.
     * @throws AccessDeniedException if file is forbidden by Stud.IP settings.
     */
    static function verifyUpload($file) {
        $GLOBALS['msg'] = ''; // validate_upload will store messages here
        if (!\validate_upload($file)) { // upload is forbidden
            // remove error pattern from message
            $error_pattern = '/error§(.+)§/';
            $message = \preg_replace($error_pattern, '$1', $GLOBALS['msg']);
    
            // clear global messages and throw exception
            $GLOBALS['msg'] = '';
            throw new \AccessDeniedException(\studip_utf8encode(\decodeHTML($message)));
        }
    }
    
    /**
     * Initialize Stud.IP metadata array for creating a new Stud.IP document.
     *
     * @param string $folder_id  ID of folder in which the document is created.
     * @param array  $file       Metadata of uploaded file.
     *
     * @return array             Stud.IP document metadata
     */
    static function getStudipDocumentData($folder_id, $file) {
        $filename = \studip_utf8decode($file['name']);
        $document['name'] = $document['filename'] = $filename;
        $document['user_id'] = $GLOBALS['user']->id;
        $document['author_name'] = \get_fullname();
        $document['seminar_id'] = Utils::getSeminarId();
        $document['range_id'] = $folder_id;
        $document['filesize'] = $file['size'];
        return $document;
    }
    
    /**
     * Get download link for a file.
     *
     * @params string $id  File identifier in database table 'dokumente'.
     * @returns string     Download link, NULL if file doesn't exist.
     */
    static function getDownloadLink($id) {
        $filename = Utils::getFilename($id);
        return $filename ? \GetDownloadLink($id, $filename) : NULL;
    }
    
    /**
     * Return file name of a Stud.IP document.
     *
     * @params string $id  Stud.IP document identifier.
     * @return string      Document's file name, NULL if it doesn't exist.
     */
    static function getFilename($id) {
       $result = Utils::executeQuery(
            'SELECT filename FROM dokumente WHERE dokument_id=:id',
            Array(':id' => $id));
        return $result ? $result[0]['filename'] : NULL;
    }
    
    /**
     * Test if string starts with prefix.
     *
     * @param string $string  Tested string.
     * @param string $prefix  Prefix of tested string.
     *
     * @return boolean  TRUE if string starts with prefix.
     */
    static function startsWith($string, $prefix) {
        return \substr($string, 0, \strlen($prefix)) === $prefix;
    }
    
    /**
     * Test if string ends with suffix.
     *
     * @param string $string  Tested string.
     * @param string $suffix  Suffix of tested string.
     *
     * @return boolean  TRUE if string ends with suffix.
     */
    static function endsWith($string, $suffix) {
        return \substr($string, \strlen($string) - \strlen($suffix)) === $suffix;
    }
    
    /**
     * Remove prefix from string.
     *
     * Does not change the string if it has a different prefix.
     *
     * @param string $string The string that must start with the prefix.
     * @param string $prefix The prefix of the string.
     *
     * @return string String without prefix.
     */
    static function removePrefix($string, $prefix) {
        if (Utils::startsWith($string, $prefix)) {
            return \substr($string, \strlen($prefix));
        }
        return $string;
    }
    
    /**
     * Check if media proxy should be used and if so return the respective URL.
     *
     * @param string $url   URL to media file.
     * @return mixed        URL string to media file (possibly 'proxied')
     *                      or NULL if URL is invalid.
     */
    static function getMediaUrl($url) {
    
        // handle internal media links
        $url = Utils::decodeMediaProxyUrl($url);
        if (Utils::isStudipMediaUrl($url)) {
            return Utils::removeStudipDomain($url);
        }
        if (Utils::isStudipUrl($url)) {
            $GLOBALS['msg'][] = 'Invalid internal link removed: ' . \htmlentities($url);
            return NULL; // invalid internal link ==> remove <img src> attribute
        }
    
        // handle external media links
        $external_media = \Config::GetInstance()->getValue('LOAD_EXTERNAL_MEDIA');
        if ($external_media === 'proxy' && \Seminar_Session::is_current_session_authenticated()) {
            // NOTE will fail if media proxy has external link
            return Utils::removeStudipDomain(Utils::encodeMediaProxyUrl($url));
        }
        if ($external_media === 'allow') {
            return $url;
        }
        $GLOBALS['msg'][] = 'External media denied: ' . \htmlentities($url);
        return NULL; // deny external media ==> remove <img src> attribute
    }
    
    /**
     * Remove domain name from internal URLs.
     *
     * Remove scheme, domain and authentication information from internal
     * Stud.IP URLs. Leave external URLs untouched.
     * 
     * @param string $url   URL from which to remove internal domain.
     * @returns string      URL without internal domain or the exact same
     *                      value as $url for external URLs.
     */
    static function removeStudipDomain($url) {
        if (!Utils::isStudipUrl($url)) {
            return $url;
        }
        $parsed_url = \parse_url(Utils::tranformInternalIdnaLink($url));
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return $path . $query . $fragment;
    }
    
    /**
     * Return a normalized, internal URL.
     *
     * @params string $url  An internal URL.
     * @returns string      Normalized internal URL.
     */
    static function tranformInternalIdnaLink($url) {
        return \idna_link(\TransformInternalLinks($url));
    }
    
    /**
     * Return media proxy URL for a given URL.
     *
     * @params string $url  The unproxied URL for accessing a resource.
     * @return string       The media proxy URL for accessing the same resource.
     */
    static function encodeMediaProxyUrl($url) {
        $base_url = $GLOBALS['ABSOLUTE_URI_STUDIP'];
        $media_proxy = $base_url . 'dispatch.php/media_proxy?url=';
        return Utils::tranformInternalIdnaLink(
            $media_proxy . \urlencode(\idna_link($url)));
    }
    
    /**
     * Test if an URL points to a valid internal Stud.IP media path.
     *
     * @param string $url Internal Stud.IP URL.
     * @returns boolean TRUE for internal media link URLs.
     *                  FALSE otherwise.
     */
    static function isStudipMediaUrl($url) {
        if (!Utils::isStudipUrl($url)) {
            return FALSE; # external link
        }
        return Utils::isStudipMediaUrlPath(Utils::getStudipRelativePath($url));
    }
    
    /**
     * Return a URL's path component with the absolute Stud.IP path removed.
     *
     * NOTE: If the URL is not an internal Stud.IP URL, the path component will
     * nevertheless be returned without issuing an error message.
     *
     * Example:
     * >>> getStudipRelativePath('http://localhost:8080'
     *      . '/studip/sendfile.php?type=0&file_id=ABC123&file_name=nice.jpg')
     * 'sendfile.php'
     *
     * @param string $url   The URL from which to return the Stud.IP-relative 
     *                      path component.
     * returns string Stud.IP-relative path component of $url.
     */
    static function getStudipRelativePath($url) {
        $parsed_url = \parse_url(Utils::tranformInternalIdnaLink($url));
        $parsed_studip_url = Utils::getParsedStudipUrl();
        return Utils::removePrefix($parsed_url['path'], $parsed_studip_url['path']);
    }
    
    /**
     * Extract the original URL from a media proxy URL.
     *
     * @param string $url The media proxy URL.
     * return string The original URL. If $url does not point to the media 
     *               proxy then this is the exact same value given by $url.
     */
    static function decodeMediaProxyUrl($url) {
        # TODO make it work for 'url=' at any position in query
        $proxypath = Utils::getMediaProxyPath() . '?url=';
        $urlpath = Utils::removeStudipDomain($url);
        if (Utils::startsWith($urlpath, $proxypath)) {
            return \urldecode(Utils::removePrefix($urlpath, $proxypath));
        }
        return $url;
    }
    
    /**
     * Return just the path of Stud.IP's media proxy URL.
     */
    static function getMediaProxyPath() {
        return Utils::removeStudipDomain(Utils::getMediaProxyUrl());
    }
    
    /**
     * Return Stud.IP's absolute media proxy URL.
     */
    static function getMediaProxyUrl() {
        return $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'dispatch.php/media_proxy';
    }
    
    /**
     * Test if given URL points to an internal Stud.IP resource.
     *
     * @param string $url  URL that is tested.
     * @return boolean     TRUE if URL points to internal Stud.IP resource.
     */
    static function isStudipUrl($url) {
        $studip_url = Utils::getParsedStudipUrl();
        \assert(\is_array($studip_url)); // otherwise something's wrong with studip
    
        $parsed_url = \parse_url(Utils::tranformInternalIdnaLink($url));
        if ($parsed_url === FALSE) {
            return FALSE; // url is seriously malformed
        }
    
        $studip_schemes = array($studip_url['scheme'], 'http', 'https', \NULL);
        $studip_hosts = array($studip_url['host'], \NULL);
        $studip_ports = array($studip_url['port'], \NULL);
    
        $is_scheme = \in_array($parsed_url['scheme'], $studip_schemes);
        $is_host = \in_array($parsed_url['host'], $studip_hosts);
        $is_port = \in_array($parsed_url['port'], $studip_ports);
        $is_path = Utils::startsWith($parsed_url['path'], $studip_url['path']);
        return $is_scheme && $is_host && $is_port && $is_path;
    }
    
    /**
     * Return an associative array containing the Stud.IP URL elements.
     *
     * see also: http://php.net/manual/en/function.parse-url.php
     *
     * @returns mixed  Same values that PHP's parse_url() returns.
     */
    static function getParsedStudipUrl() {
        return \parse_url($GLOBALS['ABSOLUTE_URI_STUDIP']);
    }
    
    /**
     * Test if path is valid for internal Stud.IP media URLs.
     *
     * @params string $path The path component of an URL.
     * return boolean       TRUE for valid media paths, FALSE otherwise.
     */
    static function isStudipMediaUrlPath($path) {
        list($path_head) = \explode('/', $path);
        $valid_paths = array('sendfile.php', 'download', 'assets', 'pictures');
        return \strpos(\urldecode($path), '../') === false && \in_array($path_head, $valid_paths);
    }
    
    /**
     * Test if current user has required access level. 
     * 
     * @params string $permission  Minimum require access level. 
     * @returns boolean            TRUE if user has required access level. 
     */ 
    static function hasPermission($permission) { 
        return $GLOBALS['perm']->have_studip_perm($permission, Utils::getSeminarId()); 
    } 
     
    /** 
     * Throw exception if current user hasn't required access level. 
     * 
     * @param string $permission  Minimum required access level. 
     * @throws AccessDeniedException if user does not have permission. 
     */ 
    static function verifyPermission($permission) { 
        if (!Utils::hasPermission($permission)) { 
            throw new \AccessDeniedException(
                \_("Es werden mindestens $permission-Zugriffsrechte benötigt.")); 
        } 
    }
     
    /**
     * Throw exception if HTTP request was not send as POST.
     * @throws AccessDeniedException if request was not send as HTTP POST.
     */
    static function verifyPostRequest() {
        if (!\Request::isPost()) {
            throw new \AccessDeniedException(
                _('Die Anfrage muss als HTTP POST gestellt werden.'));
        }
    }
    
    /**
     * Decode a UTF-8 encoded POST variable.
     *
     * @params string  variable    POST variable's name.
     * @params boolean must_exist  Throw an exception if variable not posted.
     *
     * @return mixed value  The variable's decoded value as string or NULL if the 
     *                      variable has not been posted and must_exist is FALSE.
     * @throws Exception if must_exist is TRUE and variable is not set.
     */
    static function utf8POST($variable, $must_exist=FALSE) {
        // TODO shouldn't this be isset($_POST[$variable])??
        if (isset($variable)) {
            return studip_utf8decode($_POST[$variable]);
        }
        if ($must_exist) {
            // TODO why doesn't this have any effect???
            throw new Exception("POST variable $variable not set.");
        }
        return NULL;
    }
    
    /**
     * Return a configuration value from the Stud.IP DB.
     *
     * @param string $name  Identifier of the configuration entry.
     * @returns string      Value of the configuration entry.
     */
    static function getConfigValue($name) {
        return \Config::GetInstance()->getValue($name);
    } 
} // class Utils
