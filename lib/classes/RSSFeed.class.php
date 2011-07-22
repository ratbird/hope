<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * RSSFeed.class.php - RSSFeed main-class
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan Kulmann <jankul@tzi.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

define('MAGPIE_CACHE_DIR', $TMP_PATH.'/magpie_cache');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

require_once('vendor/magpierss/rss_fetch.inc');
require_once('lib/visual.inc.php');

class RSSFeed
{
    var $ausgabe = array();
    var $domain = "";
    var $max_items = 15;
    var $class_id = "";

    /**
     * Konstruktor
     *
     * @param string $rssfeed_url
     * @param int $max_items
     */
    function RSSFeed($rssfeed_url, $max_items = 15)
    {
        if ($rssfeed_url=="") die(_("Bitte eine gültige URL angeben!"));
        $rssfeed_url = TransformInternalLinks($rssfeed_url);
        $parsed_url = parse_url($rssfeed_url);
        $this->domain = $parsed_url["host"];
        $this->internal_feed = (($parsed_url['host'] == $_SERVER['HTTP_HOST'] || $parsed_url['host'].':'.$parsed_url['port'] == $_SERVER['HTTP_HOST']) && strpos($parsed_url['path'], $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP']) === 0);
        $this->max_items = $max_items;
        $this->class_id = md5($rssfeed_url);
        $this->ausgabe = @fetch_rss($rssfeed_url);
    }

    /**
     * Erzeugt eine HTML-Tabelle mit den einzelnen News-Eintraegen.
     */
    function get_feed_anzeige()
    {
        global $PHP_SELF, $more;

        $i = 1;
        if ($more == $this->class_id) echo "<a name=\"news_anchor\"></a>\n";
        echo "<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
        foreach ($this->ausgabe->items as $v) {
            if (strlen(trim($v["title"]))>0) {
                $desc = strip_tags(html_entity_decode(studip_utf8decode($v["description"] ? $v["description"] : $v['summary'])));
                if (strlen($desc) > 150) $desc = substr($desc, 0, 150) . "...";
                if ($i > $this->max_items && $more != $this->class_id) {
                    echo "<tr><td align=\"left\" valign=\"TOP\" colspan=\"2\"><a href=\"$PHP_SELF?more=".$this->class_id."#news_anchor\"><font size=\"-1\"><I>mehr...</I></font></a></td></tr>\n";
                    break;
                }
                echo "<tr>
                <td width=\"20\" align=\"center\"> ".Assets::img(!$this->internal_feed ? 'icons/16/grey/link-extern.png' : 'icons/16/grey/link-intern.png')
                ." </td>
                <td>
                <a href=\"".TransformInternalLinks($v["link"])."\" ".(!$this->internal_feed  ? "TARGET=\"_blank\"" : "") . " TITLE=\"".htmlReady($desc)."\">
                ".htmlReady(studip_utf8decode($v["title"]))."</a>\n";
                if ($desc ) {
                    echo "<br><font size=\"-2\">".htmlReady($desc)."</font>\n";
                }
                echo "</td></tr>\n";

                if ($v['enclosure_url']) {
                    echo "<tr><td></td>
                    <td><a href=\"{$v['enclosure_url']}\" TARGET=\"_blank\">". Assets::img('icons/16/grey/rss.png', array('class' => 'text-top')) . "</a>
                    <font size=\"-2\">".htmlReady('('.$v['enclosure_type'] . ' - ' . floor($v['enclosure_length']/1024) . ' kb)')."</font>
                    </td></tr>\n";
                }
                $i++;
            }
        }
        echo "</table>\n";
    }

    /**
     *
     */
    function rssfeed_start()
    {
        if (!$this->ausgabe) {
            echo _("Zeitüberschreitung beim Laden von ").$this->domain."...";
        } else {
            $this->get_feed_anzeige();
            echo "<br>Copyright &copy; ".$this->domain
            . ($this->ausgabe->channel['link'] ? '<br>'.formatReady($this->ausgabe->channel['link'],1,1) : '');
        }
    }
}