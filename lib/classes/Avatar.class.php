<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * Copyright (C) 2007 - Andr� Kla�en (aklassen@uos.de)
 * Copyright (C) 2008 - Marcus Lunzenauer (mlunzena@uos)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * TODO
 *
 * @package        studip
 * @subpackage lib
 *
 * @author        Andr� Kla�en (aklassen@uos)
 * @author        Marcus Lunzenauer (mlunzena@uos)
 * @copyright (c) Authors
 * @since         1.7
 */
class Avatar {

    /**
     * This constant stands for the maximal size of a user picture.
     */
    const ORIGINAL = 'original';

    /**
     * This constant stands for the maximal size of a user picture.
     */
    const NORMAL = 'normal';

    /**
     * This constant stands for a medium size of a user picture.
     */
    const MEDIUM = 'medium';

    /**
     * This constant stands for an icon size of a user picture.
     */
    const SMALL    = 'small';


    /**
     * This constant represents the maximal size of a user picture in bytes.
     */
    const MAX_FILE_SIZE = 716800;

    /**
     * This constant holds the username and ID of the "nobody" avatar.
     */
    const NOBODY = 'nobody';

    /**
     * Holds the user's id
     *
     * @access protected
     * @var string
     */
    protected $user_id;


    /**
     * Holds the user's username
     *
     * @access protected
     * @var string
     */
    protected $username;


    /**
     * Returns an avatar object of the appropriate class.
     *
     * @param    string    the user's id
     * @param    string    the user's username (optional)
     *
     * @return mixed     the user's avatar.
     */
    static function getAvatar($user_id, $username = NULL) {
        return new Avatar($user_id, $username);
    }

    /**
     * Returns an avatar object for "nobody".
     *
     * @return mixed     the user's avatar.
     */
    static function getNobody() {
        return new Avatar(Avatar::NOBODY, Avatar::NOBODY);
    }


    function getAvatarDirectoryUrl() {
        return $GLOBALS['DYNAMIC_CONTENT_URL'] . "/user";
    }


    function getAvatarDirectoryPath() {
        return $GLOBALS['DYNAMIC_CONTENT_PATH'] . "/user";
    }


    function getCustomAvatarUrl($size, $ext = 'png') {
        $retina = $GLOBALS['auth']->auth['devicePixelRatio'] > 1.2;
        $size = $retina && file_exists($this->getCustomAvatarPath($size, 'png', true))
            ? $size."@2x"
            : $size;
        return sprintf(
            '%s/%s_%s.%s?d=%s',
            $this->getAvatarDirectoryUrl(),
            $this->user_id,
            $size,
            $ext,
            @filemtime($this->getCustomAvatarPath($size)) ?: "0"
        );
    }


    function getCustomAvatarPath($size, $ext = 'png', $retina = false) {
        return sprintf('%s/%s_%s.%s',
                                     $this->getAvatarDirectoryPath(),
                                     $this->user_id,
                                     $retina ? $size."@2x" : $size,
                                     $ext);
    }


    /**
     * Constructs a new Avatar object belonging to a user with the given id.
     *
     * @param    string    the user's id
     * @param    string    the user's username (optional)
     *
     * @return void
     */
    protected function __construct($user_id, $username = NULL) {
        $this->user_id = $user_id;
        $this->username = $username;
    }


    /**
     * Returns the file name of a user's avatar.
     *
     * @param    string    one of the constants Avatar::(NORMAL|MEDIUM|SMALL)
     * @param    string    an optional extension of the avatar
     *
     * @return string    the absolute file path to the avatar
     */
    function getFilename($size, $ext = 'png') {
        $this->checkAvatarVisibility();
        return $this->is_customized()
            ? $this->getCustomAvatarPath($size, $ext)
            : $this->getNobody()->getCustomAvatarPath($size, $ext);
    }


    /**
     * Returns the URL of a user's picture.
     *
     * @param    string    one of the constants Avatar::(NORMAL|MEDIUM|SMALL)
     * @param    string    an optional extension of the user's picture
     *
     * @return string    the URL to the user's picture
     */
    # TODO (mlunzena) in Url umbenennen
    function getURL($size, $ext = 'png') {
        $this->checkAvatarVisibility();
        return $this->is_customized()
            ? $this->getCustomAvatarUrl($size, $ext)
            : $this->getNobody()->getCustomAvatarUrl($size, $ext);
    }


    /**
     * Returns whether a user has uploaded a custom picture.
     *
     * @return boolean    returns TRUE if the user customized her picture, FALSE
     *                                    otherwise.
     */
    function is_customized() {
        return $this->user_id !== Avatar::NOBODY &&
                     file_exists($this->getCustomAvatarPath(Avatar::MEDIUM));
    }


    /**
     * Returns the CSS class to use for this avatar image.
     *
     * @param string    one of the constants Avatar::(NORMAL|MEDIUM|SMALL)
     *
     * @return string CSS class to use for the avatar
     */
    protected function getCssClass($size) {
        if (!isset($this->username)) {
            $this->username = htmlReady(get_username($this->user_id));
        }

        return sprintf('avatar-%s user-%s'.($this->is_customized() ? '' : ' recolor'), $size, $this->username);
    }


    /**
     * Constructs a desired HTML image tag for an Avatar. Additional
     * html attributes may also be specified using the $opt parameter.
     *
     * @param string    one of the constants Avatar::(NORMAL|MEDIUM|SMALL)
     * @param array     array of attributes to add to the HTML image tag
     *
     * @return string returns the HTML image tag
     */
    function getImageTag($size = Avatar::MEDIUM, $opt = array()) {

        $opt['src'] = $this->getURL($size);

        if (isset($opt['class'])) {
            $opt['class'] = $this->getCssClass($size) . ' ' . $opt['class'];
        } else {
            $opt['class'] = $this->getCssClass($size);
        }

        if (!isset($opt['title'])) {
            $opt['title'] = htmlReady($this->getDefaultTitle());
        }

        if (!isset($opt['alt'])) {
            $opt['alt'] = $opt['title'];
        }

        $result = '';

        foreach ($opt as $key => $value) {
            $result .= sprintf('%s="%s" ', $key, $value);
        }

        return '<img ' . $result . '>';
    }


    /**
     * Creates all the different sized thumbnails for an uploaded file.
     *
     * @param    string    the key of the uploaded file,
     *                                 see documentation about $_FILES
     *
     * @return void
     *
     * @throws several Exceptions if the uploaded file does not satisfy the
     *                 requirements
     */
    public function createFromUpload($userfile) {

        try {

            // Bilddatei ist zu gro�
            if ($_FILES[$userfile]['size'] > self::MAX_FILE_SIZE) {
                throw new Exception(sprintf(_("Die hochgeladene Bilddatei ist %s KB gro�. Die maximale Dateigr��e betr�gt %s KB!"),
                                                                        round($_FILES[$userfile]['size'] / 1024),
                                                                        self::MAX_FILE_SIZE / 1024));
            }

            // keine Datei ausgew�hlt!
            if (!$_FILES[$userfile]['name']) {
                throw new Exception(_("Sie haben keine Datei zum Hochladen ausgew�hlt!"));
            }

            // get extension
            $pathinfo = pathinfo($_FILES[$userfile]['name']);
            $ext = strtolower($pathinfo['extension']);

            // passende Endung ?
            if (!in_array($ext, words('jpg jpeg gif png'))) {
                throw new Exception(sprintf(_("Der Dateityp der Bilddatei ist falsch (%s). Es sind nur die Dateiendungen .gif, .png, .jpeg und .jpg erlaubt!"), htmlReady($ext)));
            }

            // na dann kopieren wir mal...
            $filename = sprintf('%s/%s.%s',
                                                    $this->getAvatarDirectoryPath(),
                                                    $this->user_id, $ext);

            if (!@move_uploaded_file($_FILES[$userfile]['tmp_name'], $filename)) {
                throw new Exception(_("Es ist ein Fehler beim Kopieren der Datei aufgetreten. Das Bild wurde nicht hochgeladen!"));
            }

            // set permissions for uploaded file
            @chmod($filename, 0666 & ~umask());

            $this->createFrom($filename);

            @unlink($filename);

        // eigentlich braucht man hier "finally"
        } catch (Exception $e) {
            @unlink($filename);
            throw $e;
        }
    }


    /**
     * Creates thumbnails from an image.
     *
     * @param string    filename of the image to create thumbnails from
     *
     * @return void
     */
    public function createFrom($filename) {

        if (!extension_loaded('gd')) {
            throw new Exception(_("Es ist ein Fehler beim Bearbeiten des Bildes aufgetreten."));
        }

        set_error_handler(array(__CLASS__, 'error_handler'));

        copy($filename, $this->getCustomAvatarPath(Avatar::ORIGINAL));
        $this->resize(Avatar::NORMAL, $filename);
        $this->resize(Avatar::NORMAL, $filename, true);
        $this->resize(Avatar::MEDIUM, $filename);
        $this->resize(Avatar::MEDIUM, $filename, true);
        $this->resize(Avatar::SMALL,  $filename);
        $this->resize(Avatar::SMALL,  $filename, true);
        
        restore_error_handler();
    }

    /**
     * Removes all uploaded pictures of a user.
     *
     * @return void
     */
    function reset() {
        if ($this->is_customized()) {
            @unlink($this->getCustomAvatarPath(Avatar::ORIGINAL));
            @unlink($this->getCustomAvatarPath(Avatar::NORMAL));
            @unlink($this->getCustomAvatarPath(Avatar::SMALL));
            @unlink($this->getCustomAvatarPath(Avatar::MEDIUM));
            @unlink($this->getCustomAvatarPath(Avatar::NORMAL, 'png', true));
            @unlink($this->getCustomAvatarPath(Avatar::SMALL, 'png', true));
            @unlink($this->getCustomAvatarPath(Avatar::MEDIUM, 'png', true));
        }
    }


    /**
     * Return the dimension of a size
     *
     * @param    string         the dimension of a size
     *
     * @return array            a tupel of integers [width, height]
     */
    function getDimension($size) {
        $dimensions = array(
            Avatar::NORMAL => array(250, 250),
            Avatar::MEDIUM => array(100, 100),
            Avatar::SMALL  => array(25, 25)
        );
        return $dimensions[$size];
    }


    /**
     * Create from an image thumbnails of a specified size.
     *
     * @param string    the size of the thumbnail to create
     * @param string    the filename of the image to make thumbnail of
     *
     * @return void
     */
    private function resize($size, $filename, $retina = false) {

        list($thumb_width, $thumb_height) = $this->getDimension($size);
        $thumb_width = $retina ? $thumb_width * 2 : $thumb_width;
        $thumb_height = $retina ? $thumb_height * 2 : $thumb_height;

        list($width, $height, $type) = getimagesize($filename);

        # create image resource from filename
        $lookup = array(
            IMAGETYPE_GIF    => "imagecreatefromgif",
            IMAGETYPE_JPEG => "imagecreatefromjpeg",
            IMAGETYPE_PNG    => "imagecreatefrompng");
        if (!isset($lookup[$type])) {
            throw new Exception(_("Der Typ des Bilds wird nicht unterst�tzt."));
        }
        $image = $lookup[$type]($filename);

        imagealphablending($image, FALSE);
        imagesavealpha($image, TRUE);

        # resize image if needed
        if ($height > $thumb_height || $width > $thumb_width) {
            $factor = max($thumb_width / $width, $thumb_height / $height);
            $resized_width = round($width * $factor);
            $resized_height = round($height * $factor);
        } else {
            $resized_width    = $width;
            $resized_height = $height;
        }

        $image = self::imageresize($image, $width, $height, $resized_width, $resized_height);

        $dst = imagecreatetruecolor($thumb_width, $thumb_height);
        imagealphablending($dst, FALSE);
        imagesavealpha($dst, TRUE);

        $trans_colour = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $trans_colour);

        // center the new image
        $ypos = intval($thumb_height - $resized_height) >> 1;
        $xpos = intval($thumb_width - $resized_width) >> 1;

        imagecopy($dst, $image, $xpos, $ypos, 0, 0,
                            $resized_width, $resized_height);

        imagepng($dst, $this->getCustomAvatarPath($size, 'png', $retina));
    }


    private function imageresize($image, $current_width, $current_height, $width, $height) {
        $image_resized = imagecreatetruecolor($width, $height);

        imagealphablending($image_resized, FALSE);
        imagesavealpha($image_resized, TRUE);
        imagecopyresampled($image_resized, $image, 0, 0, 0, 0,
                                             $width, $height, $current_width, $current_height);

        return $image_resized;
    }


    public static function error_handler($errno, $errstr, $errfile, $errline) {

        if (defined("E_RECOVERABLE_ERROR") &&
                $errno == constant("E_RECOVERABLE_ERROR")) {
            $message = sprintf('Recoverable error "%s" occured in file %s line %s.',
                                                 $errstr, $errfile, $errline);
            throw new Exception($message);
        }

        # execute PHP internal error handler
        return false;
    }

    /**
     * Return the default title of the avatar.
     * @return string the default title
     */
    function getDefaultTitle()
    {
        if ($this->user_id === Avatar::NOBODY) {
            return Avatar::NOBODY;
        }

        require_once 'lib/functions.php';
        return get_fullname($this->user_id);
    }
    
    /**
     * Return if avatar is visible to the current user.
     * Also set the user_id of avatar to nobody if not visible to current user.
     * @return boolean: true if visible
     */
    protected function checkAvatarVisibility() {
        $visible = Visibility::verify('picture', $this->user_id);
        if (!$visible) {
            $this->user_id = 'nobody';
        }
        return $visible;
    }
}
