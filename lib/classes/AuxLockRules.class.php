<?
# Lifter002: DONE
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
* ZusatzLockRules.class.php - Sichtbarkeits-Administration fuer Zusatzangaben bei Teilnehmerlisten
*
* Copyright (C) 2006 Till Gl�ggler <tgloeggl@inspace.de>
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

class AuxLockRules
{

    static function _toArray($data) 
    {
        return array(
            'lock_id' => $data['lock_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'attributes' => unserialize($data['attributes']),
            'order' => unserialize($data['sorting'])
        );
    }

    static function getAllLockRules() 
    {
        $ret = array();
        $db = DBManager::get()->query("SELECT * FROM aux_lock_rules");
        while ($data = $db->fetch(PDO::FETCH_ASSOC)) {
            $ret[$data['lock_id']] = AuxLockRules::_toArray($data);
        }

        return $ret;
    }

    static function getLockRuleById($id) 
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM aux_lock_rules WHERE lock_id = ?");
        $stmt->execute(array($id));
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return AuxLockRules::_toArray($data);
    }

    static function getLockRuleBySemId($sem_id) 
    {
        $stmt = DBManager::get()->prepare("SELECT aux_lock_rule FROM seminare WHERE Seminar_id = ?");
        $stmt->execute(array($sem_id));
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return AuxLockRules::getLockRuleById($data['aux_lock_rule']);
        }
        return NULL;
    }

    static function createLockRule($name, $description, $fields, $order) 
    {
        $id = md5(uniqid(rand()));
        $attributes = serialize($fields);
        $sorting = serialize($order);
        $stmt = DBManager::get()->prepare('INSERT INTO aux_lock_rules '
                    . '(lock_id, name, description, attributes, sorting) '
                . 'VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(array($id, $name, $description, $attributes, $sorting));
        return $id;
    }

    static function updateLockRule($id, $name, $description, $fields, $order) 
    {
        $attributes = serialize($fields);
        $sorting = serialize($order);
        $stmt = DBManager::get()->prepare('UPDATE aux_lock_rules '
                    . 'SET name = ?, description = ?, attributes = ?, sorting = ? '
                    . 'WHERE lock_id = ?');
        return $stmt->execute(array($name, $description, $attributes, $sorting, $id));
    }

    static function deleteLockRule($id) 
    {
        $stmt = DBManager::get()->prepare('SELECT COUNT(*) as c FROM seminare WHERE aux_lock_rule = ?');
        $stmt->execute(array($id));
        if ($stmt->fetchColumn() > 0) return false;
    
        $stmt = DBManager::get()->prepare('DELETE FROM aux_lock_rules WHERE lock_id = ?');
        return $stmt->execute(array($id));
    }

    static function getSemFields() 
    {
        return array(
            'vasemester' => 'Semester',
            'vanr' => 'Veranstaltungsnummer',
            'vatitle' => 'Veranstaltungstitel',
            'vadozent' => 'Dozent'
        );
    }

    static function checkLockRule($fields)
    {
        $entries = DataField::getDataFields('usersemdata');
        foreach ($entries as $id => $entry) {
            if ($fields[$id] == 1) return true;
        }

        return false;
    }
}
