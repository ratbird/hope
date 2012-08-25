<?php
/*
* ContentElement.php - ContentElement
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License as
* published by the Free Software Foundation; either version 2 of
* the License, or (at your option) any later version.
*
* @author Till Glöggler <till.gloeggler@elan-ev.de>
* @copyright 2012 ELAN e.V. <http://www.elan-ev.de>
* @license http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
* @category Stud.IP
*/

class ContentElement
{
    private $title;
    private $summary;
    private $content;
    private $creator;
    private $creatorid;
    private $url;
    private $date;
    
    /**
     * create a new ContentElement
     *
     * @param string $title title of the element
     * @param string $summary a summary of the elements content
     * @param string $content content of the element
     * @param string $creator_id user_id of the creator of the element
     * @param string $creator user-name of the creator of the element
     * @param string $url url to goto if element is selected
     * @param int $date timestamp, when the element has been created
     */
    function __construct($title, $summary, $content, $creatorid, $creator, $url, $date)
    {
        $this->title     = $title;
        $this->summary   = $summary;
        $this->content   = $content;
        $this->creator   = $creator;
        $this->creatorid = $creatorid;
        $this->url       = $url;
        $this->date      = (int)$date;
    }
    
    /**
     * implements getters for all object-variables
    */
    public function __call($method, $args)
    {
        if (substr($method, 0, 3) == 'get') {
            $variable = strtolower(substr($method, 3, strlen($method) -3));
            if (isset($this->$variable)) {
                return $this->$variable;
            } else {
                throw new Exception(__CLASS__ ."::$method() does not exist!");
            }
        }
    }

    /*
     * return a JSON-representation of this object
     */
    public function toJSON()
    {
        $json_array = array();
        
        foreach (get_object_vars($this) as $name => $value) {
            $json_array[$name] = studip_utf8encode($value);
        }
        return json_encode($json_array);
    }
}