<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
DbCalendarYear.class.php - 0.8.20020628
Personal calendar for Stud.IP.
Copyright (C) 2001 Peter Thienel <pthien@gmx.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

//****************************************************************************

require_once("config.inc.php");
require_once($RELATIVE_PATH_CALENDAR . "/lib/CalendarYear.class.php");
require_once($RELATIVE_PATH_CALENDAR . "/lib/driver/$CALENDAR_DRIVER/year_driver.inc.php");

class DbCalendarYear extends CalendarYear {

    var $appdays;          // timestamps der Tage, die Termine enthalten (int[])
    var $user_id;         // User-ID aus PhpLib (String)

  // Konstruktor
    function DbCalendarYear ($tmstamp) {
        global $user;
        $this->user_id = $user->id;
        CalendarYear::CalendarYear($tmstamp);
        $this->restore();
    }
    
    // public
    function restore () {
        year_restore($this);
    }
    
    function bindSeminarEvents () {
        // zeigt alle abonnierten Seminare an
        if(func_num_args() == 0)
            $query = sprintf("SELECT t.*, seminar_user.status FROM termine t LEFT JOIN seminar_user ON Seminar_id=range_id WHERE "
                   . "user_id = '%s' AND date BETWEEN %s AND %s"
                         , $this->user_id, $this->getStart(), $this->getEnd());
        else if(func_num_args() == 1 && $seminar_ids = func_get_arg(0)){
            if(is_array($seminar_ids))
                $seminar_ids = implode("','", $seminar_ids);
            $query = sprintf("SELECT t.*, seminar_user.status FROM termine t LEFT JOIN seminar_user ON Seminar_id=range_id WHERE "
                   . "user_id = '%s' AND Seminar_id IN ('%s')"
                         . " AND date BETWEEN %s AND %s"
                         , $this->user_id, $seminar_ids, $this->getStart(), $this->getEnd());
        }
        else
            return FALSE;
        
        $db = new DB_Seminar;
        $db->query($query);
        
        if($db->num_rows() > 0){
            while($db->next_record()){
                if ($db->f('status') === 'dozent') {
                    //wenn ich Dozent bin, zeige den Termin nur, wenn ich durchführender Dozent bin:
                    $termin = new SingleDate($db->f('termin_id'));
                    if (!in_array($this->user_id, $termin->getRelatedPersons())) {
                        continue;
                    }
                }
                $adate = mktime(12,0,0,date("n",$db->f("date")),date("j",$db->f("date")),$this->year,0);
                $this->appdays["$adate"]++;
            }
            return TRUE;
        }
        return FALSE;
    }
    
    // public
    function existEvent ($tmstamp) {
        $adate = mktime(12, 0, 0, date("n", $tmstamp), date("j", $tmstamp), date("Y", $tmstamp),0);
        if(empty($this->appdays["$adate"]))
            return FALSE;
        return TRUE;
    }
    
    // Anzahl von Terminen an einem bestimmten Tag
    // public
    function numberOfEvents ($tmstamp) {
        $adate = mktime(12, 0, 0, date("n", $tmstamp), date("j", $tmstamp), date("Y", $tmstamp),0);
        return $this->appdays[$adate];
    }
    
    // public
    function serialisiere () {
        return serialize($this);
    }
    
} // class DBCalendarYear

?>
