<?php
/**
* Text-functions for use with WML.
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.12    16.09.2003  20:06:02
* @access       public
* @modulegroup  wap_modules
* @module       wap_txt.inc.php
* @package      WAP
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// wap_txt.inc.php
// Text-functions
// Copyright (c) 2003 Florian Hansen <f1701h@gmx.net>
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
    * Length of a display-line
    * @const WAP_TXT_LINE_LENGTH
    */
     define ("WAP_TXT_LINE_LENGTH", 16);

    /**
    * Length of a display-line when text is used as a hyperlink
    * @const WAP_TXT_LINK_LENGTH
    */
     define ("WAP_TXT_LINK_LENGTH", 14);

    /**
    * Encodes a string for use as wml-source-code.
    *
    * Special characters are convertet into wml-entities.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @param    string  String that will be encoded
    * @return   string  The encoded string
    */
    function wap_txt_encode_to_wml($string_to_encode)
    {
        $trans_table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
        foreach ($trans_table as $key => $value)
        {
            $trans_table[$key] = "&#" . ord($key) . ";";
        }
        $trans_table["$"]  = "$$";
        return strtr($string_to_encode, $trans_table);
    }

    /**
    * Shortens a text.
    *
    * By abbreviation, the text is shortened to the desired length.
    * Two types of abbreviation are supportet.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.12    16.09.2003  20:05:55
    * @access           public
    * @param    string  Text that will be shortened
    * @param    int     The length the text is shortened to
    * @param    string  cut_middle | cut_end;
    *                       The way the text is shortened
    * @return   string  The shortened text
    */
    function wap_txt_shorten_text($text, $short_length, $type = "cut_middle")
    {
        define ("MIN_SHORT_LENGTH", 8);
        $text_length  = strlen($text);

        if ($short_length >= $text_length)
            return $text;

        if ($short_length < MIN_SHORT_LENGTH)
            return $text;

        if ($type == "cut_middle")
        {
            $mid_string    = "..";
            $post_length   = 2;
            $short_length -= strlen($mid_string);
            $pre_length    = $short_length - $post_length;
            $pre_string    = substr($text, 0, $pre_length);
            $post_string   = substr($text, $text_length - $post_length, $post_length);
            $short_text    = $pre_string . $mid_string . $post_string;
        }

        elseif ($type == "cut_end")
        {
            $post_string   = " [...]";
            $short_length -= strlen($post_string);
            $short_text    = substr($text, 0, $short_length) . $post_string;
        }

        return $short_text;
    }

    /**
    * Devides a text into multiple parts.
    *
    * Devides the given text into the proper number of of text pages
    * with the defined amount of characters per page.
    *
    * @author           Florian Hansen <f1701h@gmx.net>
    * @version          0.1
    * @access           public
    * @param    string  Text to devide
    * @param    int     Requested page number
    * @param    var     The total number of created parts is stored
    *                       into this variable for use in the calling document
    * @return   string  The requested message part
    */
    function wap_txt_devide_text($text, $page, &$num_pages)
    {
        define ("CHARS_PER_PAGE", 150);

        $text_length = strlen($text);
        if ($text_length <= CHARS_PER_PAGE)
        {
            $num_pages            = 0;
            $page_end_position[0] = $text_length - 1;
        }
        else
        {
            $page_number   = 0;
            $str_position  = 0;
            $page_position = 0;
            $num_pages     = 0;

            while ($str_position < ($text_length - 1))
            {
                $str_position  ++;
                $page_position ++;
                if ($page_position == (CHARS_PER_PAGE - 1))
                {
                    $page_position = 0;
                    while ($text{$str_position} != " " && $str_position < ($text_length - 1))
                    {
                        $str_position ++;
                    }
                    $page_end_position[$page_number] = $str_position;

                    if ($str_position < ($text_length - 1))
                    {
                        $page_number ++;
                        $num_pages   ++;
                    }
                }
            }
            $page_end_position[$page_number] = $str_position;
        }

        if ($page == 0)
        {
            $str_start_position = 0;
        }
        else
        {
            $str_start_position = $page_end_position[$page - 1] + 1;
        }

        $str_end_position = $page_end_position[$page];
        $string_length    = $str_end_position - $str_start_position + 1;
        $message_part     = substr($text, $str_start_position, $string_length);

        return $message_part;
    }
?>
