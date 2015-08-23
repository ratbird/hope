<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * HTML-class for the Stud.IP-project.
 * Based on scripts from "http://tut.php-q.net/".
 *
 * @author  Alexander Willner <mail@AlexanderWillner.de>
 *
 * @copyright   2004 Stud.IP-Project
 * @access      public
 * @package     evaluation
 * @modulegroup evaluation_modules
 *
 */

// +--------------------------------------------------------------------------+
// This file is part of Stud.IP
// Copyright (C) 2001-2004 Stud.IP
// +--------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +--------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +--------------------------------------------------------------------------+
require_once("HTMLempty.class.php");

class HTML extends HTMLempty {

# Define all required variables ============================================= #
  /**
   * Holds the content.
   * @access   private
   * @var      object   $_content
   */
  var $_content;

  /**
   */
  var $has_textarea = false;
# ============================================================ end: variables #


# Define constructor and destructor ========================================= #

# =========================================== end: constructor and destructor #


# Define public functions =================================================== #
  /**
   *
   */
  function addHTMLContent ($_content) {
    if (is_object($_content)) {
      $classname = strtolower(get_class($_content));
      $valid_classes = array ('htmlempty', 'html', 'htm', 'htmpty', 'studip\button', 'studip\linkbutton');
      if (in_array ($classname, $valid_classes)) {
    $this->_content[] = $_content;
      } else {
    trigger_error('Ung�ltiges Objekt: "'.$classname.'"', E_USER_ERROR);
      }
    } elseif (is_scalar ($_content)) {
      $this->_content[] = (string)$_content;
    } else {
#trigger_error('Parameter muss ein Objekt oder Scalar sein',E_USER_ERROR);
echo "Fehler in HTML.class.php: Es fehlt ein addHTMLContent-Element f�r ein Element des Typs \"&lt;".$this->getName ()."&gt;\"<br>";
    }
  }

  /**
   *
   */
  function addContent ($_content) {
    if (is_object($_content))
      $this->addHTMLContent ($_content);
    elseif (is_scalar ($_content))
      $this->addHTMLContent (htmlReady (((string)$_content)));
#     $this->addHTMLContent (htmlspecialchars (((string)$_content)));
    else
      $this->addHTMLContent ("");
    
#      trigger_error("Parameter muss ein Scalar sein (Inhalt = ".
#           ($_content === NULL ? "NULL": $_content)
#           .", Typ = &lt;".$this->_name."&gt;)", E_USER_ERROR);
  }

  /**
   *
   */
  function getContent () {
    return $this->_content;
  }

  /**
   * avoid indentation of <textarea>...
   */
  function setTextareaCheck () {
      $this->has_textarea = true;
  }
  
  /**
   *
   */
  function printContent ($indent = 0) {
    echo $this->createContent ($indent);
  }

  /**
   *
   */
  function createContent ($indent = 0) {
    $output = "";

    $str_indent = str_repeat (' ', $indent);
    
    $_content = $this->getContent ();
    $output .= ($str_indent."<".$this->getName ());
    
    $attribute = $this->getAttr ();
    foreach ($attribute as $name => $value) {
      $output .= (' '.$name.'="'.$value.'"');
    }

    $output .= $this->_string;
    $output .= (">\n");
    if (!is_array($_content)) {
    $attributes = "";
    foreach ($attribute as $name => $value) {
        $attributes .= ($name.'=&gt;"'.$value.'"; ');
    }
    print "Fehler in HTML.class.php: Es fehlt ein Content-Element f�r ein Element des Typs \"&lt;".$this->getName ()."&gt;\" (Attribute: $attributes).";
    return; 
    }
    
    foreach ($_content as $content) {
    if (is_object ($content)) {
        // der aktuelle Content ist ein Object
        // also ein HTML-Element. Also geben
        // wir es aus
        $classname = strtolower(get_class($content));
        $valid_classes = array ('studip\button', 'studip\linkbutton');
        if(in_array($classname, $valid_classes)) {
            $output .= $content;
        } else {
            $output .= $content->createContent ($indent + 4);
        }
        // Rekursion l�sst gr��en ...                
    } else {
        // Content ist ein String. Jeden Zeile
        // geben wir getrennt aus
        $zeilen = explode ("\n", $content);
        $echo = "";
        
        if ($this->has_textarea) {

        // look for textarea in content
        $text_area = false;
        foreach ($zeilen as $zeile) {
        
            if (strstr($zeile, "<textarea"))
            $text_area = true;
        
            if ($text_area)
            $echo .= $zeile."\n";
            else
            $echo .= $str_indent."    ".$zeile."\n";
            
            if (strstr($zeile, "</textarea"))
            $text_area = false;
        }
        } else {

        // standard
        foreach ($zeilen as $zeile) {
            $echo .= $str_indent."    ".$zeile."\n";
        }
        }
#   $output .= (nl2br ($echo)); // Alex: Muss das wirklich sein??
        $output .= $echo;
    }
    }
    $output .= ($str_indent."</".$this->getName ().">\n");

    return $output;
  }

# ===================================================== end: public functions #


# Define private functions ================================================== #

# ==================================================== end: private functions #
}

include_once( "LazyHTML.class.php" );

?>
