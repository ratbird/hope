<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
 * guestbook.class.php - Guestbook for personal homepages
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Ralf Stockmann <rstockm@gwdg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     dates
 */

use Studip\Button, Studip\LinkButton;

class Guestbook
{
    var $active;    // user has activated the guestbook
    var $number;    // number of entrys in the guestbook
    var $rights;    // do i have admin-rights for the guestbook
    var $user_id;   // user_id of the guestbook
    var $username;  // username
    var $msg_guest; // Output Message
    var $anchor;    // html anchor
    var $openclose; // open/close status
    var $perpage;   // count of entrys per guestbook-site
    var $guestpage; // page of guestbook currently displayed
    var $pages_total; // count of guestbook pages of the user

    /**
     * Konstruktor
     *
     * @param $user_id
     * @param $guestpage
     */
    function Guestbook($user_id, $guestpage)
    {
        $this->user_id = $user_id;
        $this->username = get_username($user_id);
        $this->checkGuestbook();
        $this->numGuestbook();
        $this->rights = $GLOBALS['perm']->have_profile_perm('user', $user_id);
        $this->msg_guest = "";
        $this->anchor = FALSE;
        $this->openclose = "close";
        $this->perpage = 10;
        $this->guestpage = $guestpage;
        $this->pages_total = ceil($this->number / $this->perpage);
    }

    function checkGuestbook()
    {
        $query = "SELECT 1 FROM user_info WHERE user_id = ? AND guestbook = '1'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_id));
        $this->active = (bool)$statement->fetchColumn();
    }

    function numGuestbook()
    {
        $query = "SELECT COUNT(*) FROM guestbook WHERE range_id = '$this->user_id'";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($this->user_id));
        return 0 + $statement->fetchColumn();
    }

    function showGuestbook()
    {
        global $perm, $PHP_SELF;

        if ($this->rights == TRUE)
            if ($this->active==TRUE)
                $active = " ("._("aktiviert").")";
            else
                $active = " ("._("deaktiviert").")";
        if ($this->openclose == "close")
            $link = $PHP_SELF."?guestbook=open&username=$this->username#guest";
        else
            $link = $PHP_SELF."?guestbook=close&username=$this->username#guest";

        // set Anchor
        if ($this->anchor == TRUE)
            echo "<a name=\"guest\"></a>";

        echo "\n<table class=\"index_box\" style=\"width: 100%;\">";
        echo "\n<tr valign=\"baseline\"><td class=\"topic\"><img src=\"".Assets::image_path('icons/16/white/guestbook.png')."\"> <b>";
        echo _("Gästebuch").$active;
                print("</b></td></tr>");

        echo "\n<tr><td class=\"blank\" colspan=$colspan>";
        echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" align=\"center\"><tr><td class=\"blank\">";

        // Info Messages
        if ($this->msg_guest != "") {
            echo "<table width=\"100%%\" class=\"blank\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
            my_msg($this->msg_guest);
            echo "</table>";
        }
        //
        $titel = "<a href=\"$link\" class=\"tree\" >".$this->number."&nbsp;"._(" Einträge")."</a>";
        echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr>";
        if ($this->active == TRUE && $this->pages_total>1)
            $zusatz .= $this->guest_navi();
        printhead ("100%","0",$link,$this->openclose,$new,"<img class=\"middle\" src=\"".Assets::image_path('icons/16/grey/comment.png')."\">",$titel,$zusatz,$forumposting["chdate"],"TRUE",$index,$forum["indikator"]);

        echo "</tr></table>";
        if ($this->openclose == "open") {
            echo "<table width=\"100%\" border=0 cellpadding=0 cellspacing=0 align=center><tr><td>";
            if ($this->active==TRUE) {
                $content = $this->showPostsGuestbook();
                if ($perm->have_perm("autor"))
                    $content .= $this->formGuestbook();
            }

            printcontent ("100%",$formposting,$content,$buttons,TRUE,"");
            echo "</td></tr></table>";
            echo "<table width=\"100%\" border=0 cellpadding=3 cellspacing=0 align=center><tr><td class=\"steel2\">";
            if ($this->rights == TRUE)
                $buttons = $this->buttonsGuestbook();
            else
                $buttons = "";
            echo "$buttons</td><td class= \"steel2\" align=\"right\">$zusatz&nbsp;</td></tr></table>";

        }
        echo "</td></tr></table></td></tr></table>";
    }

    /**
     * Berechnung und Ausgabe der Blätternavigation
     *
     * @return string $navi contains the HTML of the navigation
     */
    function guest_navi()
    {
        global $PHP_SELF;

        $i = 1;
        $maxpages = $this->pages_total;
        $ipage = ($this->guestpage / $this->perpage)+1;
        if ($ipage != 1)
            $navi .= "<a href=\"$PHP_SELF?guestpage=".($ipage-2)*$this->perpage."&guestbook=open&username=$this->username#guest\"><font size=-1>" . _("zurück") . "</a> | </font>";
        else
            $navi .= "<font size=\"-1\">Seite: </font>";
        while ($i <= $maxpages) {
            if ($i == 1 || $i+2 == $ipage || $i+1 == $ipage || $i == $ipage || $i-1 == $ipage || $i-2 == $ipage || $i == $maxpages) {
                if ($space == 1) {
                    $navi .= "<font size=-1>... | </font>";
                    $space = 0;
                }
                if ($i != $ipage)
                    $navi .= "<a href=\"$PHP_SELF?guestpage=".($i-1)*$this->perpage."&guestbook=open&username=$this->username#guest\"><font size=-1>".$i."</a></font>";
                else
                    $navi .= "<font size=\"-1\"><b>".$i."</b></font>";
                if ($maxpages != 1)
                    $navi .= "<font size=\"-1\"> | </font>";
            } else {
                $space = 1;
            }
            $i++;
        }
        if ($ipage != $maxpages)
            $navi .= "<a href=\"$PHP_SELF?guestpage=".($ipage)*$this->perpage."&guestbook=open&username=$this->username#guest\"><font size=-1> " . _("weiter") . "</a></font>";
        return $navi;
    }

    function showPostsGuestbook()
    {
        global $PHP_SELF;

        $i = 0;
        $output = "<table class=\"blank\" width=\"98%%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">";

        $query = "SELECT user_id, mkdate, content, post_id "
               . "FROM guestbook "
               . "WHERE range_id = ? "
               . "ORDER BY mkdate DESC "
               . "LIMIT ?, ?";
        $statement = DBManager::get()->prepare($query);
        $statement->bindParam(2, $this->guestpage, StudipPDO::PARAM_COLUMN);
        $statement->bindParam(3, $this->perpage, StudipPDO::PARAM_COLUMN);
        $statement->execute(array($this->user_id));
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $position = $this->number - ($this->guestpage+$i);
            $output .= "<tr><td class=\"steel2\"><b><font size=\"-1\">#$position - <a href=\"$PHP_SELF?username=".get_username($row['user_id'])."\">";
            $output .= sprintf(_('%s hat am %s geschrieben:'), get_fullname($row['user_id'], 'full', true)."</a>", date('d.m.Y - H:i', $row['mkdate']));
            $output .= "</font></b></td></tr>"
                . "<tr><td class=\"steelgraulight\"><font size=\"-1\">".formatready($row['content'])."</font><p align=\"right\">";
            if ($this->rights == TRUE)
                $addon = LinkButton::create(_('Löschen'), URLHelper::getURL("?guestbook=delete&guestpage=". $this->guestpage . "&deletepost=" . $row['post_id'] . "&username="
                            . $this->username . "&studipticket=" . get_ticket() . "#guest"));
            else
                $addon = "&nbsp;";

            $output .= $addon
                ."</p></td></tr>"
                . "<tr><td class=\"steel1\">&nbsp;</td></tr>";
            $i++;
        }
        $output .= "</table>";
        return $output;
    }

    function formGuestbook()
    {
        global $auth, $PHP_SELF;
        if ($auth->auth["jscript"]) {
            $max_col = round($auth->auth["xres"] / 12 );
        } else
            $max_col =  64 ; //default für 640x480
        $cols = round($max_col*0.45);
        if ($cols < 28) $cols = 28;

        $help_url = format_help_url("Basis.VerschiedenesFormat");
        $text = "<p align=\"center\"><label for=\"post\">"._("Geben Sie hier Ihren Gästebuchbeitrag ein!")."</label></p>";

            $form = "<form name=\"guestbook\" method=\"post\" action=\"".$PHP_SELF."?studipticket=".get_ticket()."#guest\">"
            . CSRFProtection::tokenTag()
            ."<input type=hidden name=guestbook value='$this->user_id'>"
            ."<input type=hidden name=username value='$this->username'>"
            .$text
            ."<div align=\"center\"><textarea name=\"post\" id=\"post\" style=\"width:70%\" cols=\"". $cols."\"  rows=8 wrap=virtual>"
            ."</textarea>"
            ."<br><br>" . Button::createAccept(_('Abschicken')) . "&nbsp;"
            ."&nbsp;&nbsp;<a href=\"" . URLHelper::getLink('dispatch.php/smileys') . "\" target=\"_blank\"><font size=\"-1\">"._("Smileys")."</a>&nbsp;&nbsp;"."<a href=\"".$help_url."\" target=\"_blank\"><font size=\"-1\">"._("Formatierungshilfen")."</a><br>";
        return $form;
    }

    function buttonsGuestbook()
    {
        $buttons = "";
        if ($this->active == TRUE) {
            $buttons .= "&nbsp;&nbsp;" . LinkButton::create(_('Deaktivieren'), URLHelper::getURL('?guestbook=switch&username=' . $this->username . '&studipticket=' .get_ticket()
                        . '#guest'));
        } else {
            $buttons .= LinkButton::create(_('Aktivieren'), URLHelper::getURL('?guestbook=switch&username=' . $this->username. '&studipticket=' . get_ticket() .'#guest'));
        }
        $buttons .= "&nbsp;&nbsp;" . LinkButton::create(_('Alle löschen'), URLHelper::getURL('?guestbook=erase&username=' . $this->username . '&studipticket=' . get_ticket() . '#guest'));
        return $buttons;
    }

    /**
     *
     * @param $guestbook
     * @param $post
     * @param $deletepost
     * @param $studipticket
     */
    function actionsGuestbook($guestbook, $post="", $deletepost="", $studipticket)
    {
        if (check_ticket($studipticket)){
            if ($this->rights == TRUE) {
                if ($guestbook=="switch")
                    $this->msg_guest = $this->switchGuestbook();
                if ($guestbook=="erase")
                    $this->msg_guest = $this->eraseGuestbook();
                if ($guestbook=="delete")
                    $this->msg_guest = $this->deleteGuestbook($deletepost);
            }

            if ($post) {
                $msg = $this->addPostGuestbook($this->user_id,$post);
            }
        }
        if ($guestbook != "close")
            $this->openclose = "open";
        $this->checkGuestbook();
        $this->numGuestbook();
        $this->anchor = TRUE;
    }

    function switchGuestbook()
    {
        DBManager::get()
            ->prepare("UPDATE user_info SET guestbook = ? WHERE user_id = ?")
            ->execute(array((int)!$this->active, $this->user_id));

        return $this->active
            ? _('Sie haben das Gästebuch deaktiviert. Es ist nun nicht mehr sichtbar.')
            : _('Sie haben das Gästebuch aktiviert: Besucher können nun schreiben!');
    }

    function eraseGuestbook()
    {
        DBManager::get()
            ->prepare("DELETE FROM guestbook WHERE range_id = ?")
            ->execute(array($this->user_id));

        return _('Sie haben alle Beiträge des Gästebuchs gelöscht!');
    }

    function deleteGuestbook($deletepost)
    {
        if ($this->getRangeGuestbook($deletepost)==TRUE) {
            DBManager::get()
                ->prepare("DELETE FROM guestbook WHERE post_id = ?")
                ->execute(array($deletepost));
            $tmp = _("Sie haben einen Beitrag im Gästebuch gelöscht!");
        } else {
            $tmp = _("Netter Versuch!");
        }
        return $tmp;
    }

    function getRangeGuestbook($post_id)
    {
        $query = "SELECT range_id FROM guestbook WHERE post_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($post_id));
        return ($range_id = $statement->fetchColumn()) and $range_id = $this->user_id;
    }

    /**
     * baut eine ID die es noch nicht gibt
     */
    function makeuniqueGuestbook()
    {
        $query = "SELECT 1 FROM guestbook WHERE post_id = '$tmp_id'";
        $statement = DBManager::get()->prepare($query);

        do { // Loop until id is unique
            $tmp_id = md5(uniqid('kershfshsshdfgz'));
            $statement->execute(array($tmp_id));
        } while ($statement->fetchColumn());

        return $tmp_id;
    }

    function addPostGuestbook($range_id, $content)
    {
        $post_id = $this->makeuniqueGuestbook();

        $query = "INSERT INTO guestbook "
               . "(post_id, range_id, user_id, mkdate, content) "
               . "VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)";
        DBManager::get()
            ->prepare($query)
            ->execute(array($post_id, $range_id, $GLOBALS['user']->id, $content));

        return $post_id;
    }
}
