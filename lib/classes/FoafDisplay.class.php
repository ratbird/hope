<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// FoafDisplay.class.php
//
// Copyright (c) 2005 Tobias Thelen <tthelen@uni-osnabrueck.de>
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

require_once('lib/visual.inc.php');
require_once('lib/classes/Avatar.class.php');

/**
* Calculate and display "Friend of a friend lists"
*
* for a given user the current user can see how many "steps"
* (in terms of buddy list entry hops) are neccessary to connect himself with
* the user at hand (whose homepage is currently viewed).
*
* @author       Tobias Thelen <tthelen@uni-osnabrueck.de>
* @author       Michael Riehemann <michael.riehemann@uni-oldenburg.de>
*/
class FoafDisplay {
    var $db; // Database connection
    var $user_id; // start of connecting chain
    var $target_id; // end of connecting chain
    var $foaf_list; // steps of connection
    var $target_username; // used for open/close link on target user's hp
    var $depth = 4; //max number of hops, 5 is max
    var $dont_show_anonymous = true;

    /**
    * Initialise FoafDisplay object and calculate list.
    *
    * @param    user_id Watching user
    * @param    user_id Watched user
    * @param    string  Watched user's username (performance saver)
    */
    function FoafDisplay($user_id, $target_id, $target_username) {
        $this->db=new DB_Seminar();
        $this->user_id=$user_id;
        $this->target_id=$target_id;
        $this->target_username=$target_username;
        $this->foaf_list=array();
        $this->calculate();
    }

    /**
    * Calculate foaf list.
    *
    * Uses smart DB joins to find connections. Thanks to
    * Manuel Wortmann (post@manuel-wortmann.de) for the code!
    *
    * @access   private
    */
    function calculate() {

        $sql="SELECT count(*) FROM contact WHERE owner_id='".$this->user_id."' AND buddy=1 AND user_id='".$this->target_id."'";
        $this->db->query($sql);
        $this->db->next_record();
        $this->foaf_list=array();

        // check for direct connection
        if ($this->db->f(0)) {
            $this->foaf_list=array($this->user_id,$this->target_id);
            return;
        }

        for($i = 2; $i <= $this->depth; ++$i){
            if ($ret = $this->doCalculate($i)){
                $this->foaf_list = $ret;
                return;
            }
        }
        return;
    }

    function doCalculate($depth = 0){
        $ret = null;
        if ($depth){
            $values = "t1.user_id as c1";
            $from = "contact as t1";
            for ($i = 2; $i <= $depth; ++$i){
                if($i > 2 ) $values .= ",t".($i-1).".user_id as c".($i-1)." ";
                $from .= " INNER JOIN contact as t{$i} ON(t".($i-1).".user_id=t{$i}.owner_id AND t{$i}.buddy=1 "
                        . ($i == $depth ? " AND t{$i}.user_id='".$this->target_id."'" : "") . ") ";
                if ($this->dont_show_anonymous){
                    $from .= " INNER JOIN user_config uc{$i} ON(t{$i}.owner_id = uc{$i}.user_id AND uc{$i}.field='FOAF_SHOW_IDENTITY' AND uc{$i}.value='1') ";
                }
            }
            $sql = "SELECT $values FROM $from WHERE t1.owner_id='".$this->user_id."'
                    AND t1.buddy=1 LIMIT 1";
            $this->db->query($sql);
            if ($this->db->next_record()) {
                $ret[] = $this->user_id;
                for ($i = 1; $i < $depth; ++$i){
                    $ret[] = $this->db->f("c{$i}");
                }
                $ret[] = $this->target_id;
            }
        }
        return $ret;
    }

    /**
    * Show Topic bar and header/content for foaf display.
    *
    * Prints a topic bar and a printhead line (with active link for
    * opening/closing content) and content, if opened.
    *
    * @access   public
    * @param    string  open/close indication (passed by about.php)
    */
    function show($open="close")
    {
        if (!$open) {
            $open="close";
        }

        // berechnung, werte festlegen etc.
        if ($this->foaf_list && $open=="open") {
            $msg="<table align=\"center\" style=\"margin-top:8px;\"><tr>";
            $print_arrow=0;
            foreach ($this->foaf_list as $uid) {
                if ($print_arrow) {
                    $msg.='<td valign="middle" align="center">&nbsp;>&nbsp;</td>';
                } else {
                    $print_arrow=1;
                }
                $info=$this->user_info($uid,($uid==$this->user_id||$uid==$this->target_id));
                $msg.="<td align=\"center\">";
                $msg.=$info["pic"];
                $msg.="<br>";
                $msg.=$info["link"];
                $msg.="</td>";
            }
            $msg.="</tr></table>";
        }
        if ($open=="open") {
            $msg.=$this->info_text();
        }
        if (!$this->foaf_list) {
            $titel=_("Es besteht keine Verbindung.");
        } elseif (count($this->foaf_list)<=2) {
            $titel=_("Es besteht eine direkte Verbindung.");
        } else {
            $titel=sprintf(_("Es besteht eine Verbindung über %d andere NutzerInnen."),count($this->foaf_list)-2);
        }
        $link="about.php?username=".$this->target_username."& =".($open=="open" ? "close":"open")."#foaf";
        $titel="<a href=\"$link\" class=\"tree\">$titel</a>";

        // AB HIER AUSGABE
        //TODO: in template umwandeln, was ist mit $GLOBALS['ASSETS_URL'], gibt
        // es da nicht ein Step, dass anstelle von Globals eine Assets-Klasse benutzt werden soll?

        // kopfzeile
        echo '<a name="foaf"></a>';
        echo "\n<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" width=\"100%\" align=\"center\">";
        echo "\n<tr>\n<td class=\"topic\"><img src=\"".Assets::image_path('icons/16/white/guestbook.png')."\" align=\"texttop\"><b>";
        echo sprintf(_("Verbindung zu %s"),htmlReady(get_fullname($this->target_id)));
        echo "</b></td>\n</tr>";

        // inhaltbox
        echo "\n<tr>\n<td class=\"blank\">";
        echo "\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" align=\"center\">\n<tr>";
        printhead("100%","0",$link,$open,0, Assets::img('icons/16/blue/guestbook.png', array('class' => 'text-top')),$titel,"");
        if ($open=="open") {
            echo "</tr>\n<tr>\n<td colspan=\"4\" align=\"center\">";
            echo $msg;
            echo '</td>';
        }
        echo "</tr>\n</table>\n</td>\n</tr>\n</table>\n<br>\n";
    }

    /**
    * Gather and format info on user.
    *
    * @param    user_id A user's id
    * @param    bool    Should user data be created even if user doesn't want to appear in foaf lists? (true if head or tail of list)
    * @return   array   "uname"=>username, "fullname"=>Full name,
    *           "link"=>(clickable) Name, "pic"=>HTMl code for picture
    */
    function user_info($user_id, $ignore_ok) {
        global $_fullname_sql;
        $ret="";
        if ($ignore_ok || UserConfig::get($user_id)->FOAF_SHOW_IDENTITY) {
            $sql="SELECT username, $_fullname_sql[full] AS fullname FROM auth_user_md5 LEFT JOIN user_info USING (user_id) WHERE auth_user_md5.user_id='$user_id'";
            $this->db->query($sql);
            $this->db->next_record();
            $ret["uname"]=$this->db->f("username");
            $ret["name"]=$this->db->f("fullname");
            $ret["pic"] ="<a href=\"about.php?username=".$ret['uname']."\">";
            $ret["pic"].= Avatar::getAvatar($user_id)->getImageTag(Avatar::MEDIUM);
            $ret["pic"].= "</a>";


            $ret["link"]="<font size=\"-1\"><a href=\"about.php?username=".$ret['uname']."\">".htmlReady($ret['name'])."</a></font>";
        } else {
            $ret["pic"]="<img border=\"1\" src=\"".Avatar::getNobody()->getUrl(Avatar::MEDIUM)."\" " .tooltip(_("anonyme NutzerIn")).">";
            $ret["link"]=_("<font size=\"-1\">anonyme NutzerIn</font>");
        }
        return $ret;
    }

    /**
    * Return info text for foaf-feature
    *
    */
    function info_text() {
        $vis=UserConfig::get($this->user_id)->FOAF_SHOW_IDENTITY;
        $msg="<table width=\"95%\" align=\"center\">\n<tr>\n<td>";
        $msg.="<font size=\"-1\">";
        $msg.=sprintf(_("Die Verbindungskette (Friend-of-a-Friend-Liste) wertet Buddy-Listen-Einträge aus, um festzustellen, über wieviele Stufen (maximal %s) sich zwei BenutzerInnen direkt oder indirekt \"kennen\"."), $this->depth);
        $msg.=" ".sprintf(_("Die Zwischenglieder werden nur nach Zustimmung mit Namen und Bild ausgegeben. Sie selbst erscheinen derzeit in solchen Ketten %s. Klicken Sie %shier%s, um die Einstellung zu ändern."), "<b>".($vis ? _("nicht anonym") : ($this->dont_show_anonymous ? _("überhaupt nicht") :  _("anonym")))."</b>", "<a href=\"edit_about.php?view=privacy\">","</a>");
        $msg.="</font></td></tr></table>";
        return $msg;
    }

}
?>
