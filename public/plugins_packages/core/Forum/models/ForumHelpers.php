<?php
/**
 * ForumHelpers.php - Some useful helpers for the forum
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

class ForumHelpers {

    /**
     * The page for the current script run, modified by a global page-handle
     * @var int
     */
    static $page = 1;

    /**
     * helper_function for highlight($text, $highlight)
     *
     * @param  string  $text
     * @param  array   $highlight
     * @return string
     */
    static function do_highlight($text, $highlight)
    {
        foreach ($highlight as $hl) {
            $text = str_ireplace(htmlReady($hl), '<span class="highlight">'. htmlReady($hl) .'</span>', $text);
        }
        return $text;
    }

    /**
     * This function highlights Text HTML-safe
     * (tags or words in tags are not highlighted, words between tags ARE highlighted)
     *
     * @param string $text the text where to words shall be highlighted, may contain tags
     * @param array $highlight an array of words to be highlighted
     * @return string the highlighted text
     */
    function highlight($text, $highlight)
    {
        if (empty($highlight)) return $text;

        $data = array();
        $treffer = array();

        // split text at every tag
        $pattern = '/<[^<]*>/U';
        preg_match_all($pattern, $text, $treffer, PREG_OFFSET_CAPTURE);

        if (sizeof($treffer[0]) == 0) {
            return self::do_highlight($text, $highlight);
        }

        // cycle trough the text between the tags and highlight all hits
        $last_pos = 0;
        foreach ($treffer[0] as $taginfo) {
            $size = strlen($taginfo[0]);
            if ($taginfo[1] != 0) {
                $data[] = self::do_highlight(substr($text, $last_pos, $taginfo[1] - $last_pos), $highlight);
            }

            $data[] = substr($text, $taginfo[1], $size);
            $last_pos = $taginfo[1] + $size;
        }

        // don't miss the last portion of a posting
        if ($last_pos < strlen($text)) {
            $data[] = self::do_highlight(substr($text, $last_pos, strlen($text) - $last_pos), $highlight);
        }

        return implode('', $data);
    }

    /**
     * Returns a human-readable version of the passed global Stud.IP permission.
     *
     * @param  string  $perm
     * @return string
     */
    static function translate_perm($perm)
    {
        switch ($perm) {
            case 'root':
                return _('Chef im Ring');
                break;

            case 'admin':
                return _('Administrator/-in');
                break;

            case 'dozent':
                return _('Lehrende/-r');
                break;

            case 'tutor':
                return _('Tutor/-in');
                break;

            case 'autor':
                return _('Autor/-in');
                break;

            case 'user':
                return _('Leser/-in');
                break;

            default:
                return '';
                break;
        }
    }

    /**
     * return the currently chosen page
     *
     * @return  int
     */
    static function getPage()
    {
        return self::$page;
    }

    /**
     * set the current page
     *
     * @param int $page_num the page
     */
    static function setPage($page_num) 
    {
        self::$page = $page_num;
    }
    
    /**
     * Return an info-text explaining the visit-status of the passed topic_di
     * which has the passed number of new entries.
     * 
     * @param string $num_entries  the number of new entries
     * @param string $topic_id     the id of the topic 
     * 
     * @return string  a human readable, localized text
     */
    static function getVisitText($num_entries, $topic_id)
    {
        if ($num_entries > 0) {
            $text = sprintf(_('Seit ihrem letzten Besuch gibt es %s neue Beitr�ge'), $num_entries);
        } else {
            $all_entries = max(ForumEntry::countEntries($topic_id) - 1, 0);
            if ($all_entries == 0) {
                $text = sprintf(_('Es gibt bisher keine Beitr�ge.'));
            } else if ($all_entries == 1) {
                $text = sprintf(_('Seit ihrem letzten Besuch gab es nichts neues.'
                      . ' Es ist ein alter Beitrag vorhanden.'));
            } else {
                $text = sprintf(_('Seit ihrem letzten Besuch gab es nichts neues.'
                      . ' Es sind %s alte Beitr�ge vorhanden.'), $all_entries);
            }
        }
        
        return $text;
    }

    /**
     * return the online status of the passed user, one of three possible
     * states is returned:
     *  - available
     *  - away
     *  - offline
     * 
     * @staticvar type $online_status
     * 
     * @param string $user_id
     * 
     * @return string
     */
    static function getOnlineStatus($user_id)
    {
        static $online_status;

        if ($GLOBALS['user']->id == $user_id) {
            return 'available';
        }

        if (!$online_status) {
            $online_users = get_users_online(15);
            foreach ($online_users as $username => $data) {
                if ($data['last_action'] >= 300) {
                    $online_status[$data['user_id']] = 'away';
                } else {
                    $online_status[$data['user_id']] = 'available';
                }
            }
        }
        
        return $online_status[$user_id] ?: 'offline';
    }

    /**
     * Create a pdf of all postings belonging to the passed seminar located
     * under the passed topic_id. The PDF is dispatched automatically.
     * 
     * BEWARE: This function never returns, it dies after the PDF has been 
     * (succesfully or not) dispatched.
     * 
     * @param string $seminar_id
     * @param string $parent_id
     */
    static function createPdf($seminar_id, $parent_id = null)
    {
        $seminar_name = get_object_name($seminar_id, 'sem');
        $data = ForumEntry::getList('dump', $parent_id ?: $seminar_id);
        $first_page = true;

        $document = new ExportPDF();
        $document->SetTitle(_('Forum'));
        $document->setHeaderTitle(sprintf(_("Forum \"%s\""), $seminar_name['name']));

        foreach ($data['list'] as $entry) {
            if ($entry['depth'] == 1) {
                $document->addContent($content);
                $document->addPage();
                $first_page = false;
                $content = '';

                $content .= '++++**'. _('Bereich') .': '. $entry['name_raw'] .'**++++' . "\n";
                $content .= $entry['content_raw'] ."\n\n";
            } else if ($entry['depth'] == 2) {
                $content .= '++**'. _('Thema') .': '. $entry['name_raw'] .'**++' . "\n";
                $content .= '%%' . sprintf(_('erstellt von %s am %s'), htmlReady($entry['author']), 
                    strftime('%A %d. %B %Y, %H:%M', (int)$entry['mkdate'])) . '%%' . "\n";
                $content .= $entry['content_raw'] ."\n\n";
            } else if ($entry['depth'] == 3) {
                $content .= '**'.$entry['name_raw'] .'**' . "\n";
                $content .= '%%' . sprintf(_('erstellt von %s am %s'), htmlReady($entry['author']), 
                    strftime('%A %d. %B %Y, %H:%M', (int)$entry['mkdate'])) . '%%' . "\n";
                $content .= $entry['content_raw'] ."\n--\n";
            }
        }

        if ($content) {
            if ($first_page) {
                $document->addPage();
            }
            $document->addContent($content);
        }

        $document->dispatch($seminar_name['name'] ." - Forum");
        die;
    }


    /**
     * Returns the id of the currently selected seminar or false, if no seminar
     * is selected
     * 
     * @return mixed  seminar_id or false
     */
    static function getSeminarId()
    {
        if (!Request::option('cid')) {
            if ($GLOBALS['SessionSeminar']) {
                URLHelper::bindLinkParam('cid', $GLOBALS['SessionSeminar']);
                return $GLOBALS['SessionSeminar'];
            }

            return false;
        }

        return Request::option('cid');
    }
}
