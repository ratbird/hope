<?php

require 'application.php';

class IndexController extends ApplicationController {

    private $_isDozent = false;

    function before_filter($action, $args) {
        parent::before_filter($action, $args);
        Navigation::activateItem('/course/' . $this->plugin->me);
        $this->_isDozent = $GLOBALS['perm']->have_studip_perm('dozent', $this->plugin->seminar_id);
        $this->isDozent = $this->_isDozent;
    }

    function index_action() {
        $db = DBManager::get();
        $sql = "SELECT * FROM media_links WHERE course_id = ?";
        $sth = $db->prepare($sql);
        $sth->execute(array($this->plugin->seminar_id));
        $this->links = $sth->fetchAll(PDO::FETCH_OBJ);
    }

    function add_action() {
        if (!$this->_isDozent)
            throw new AccessDeniedException("Sie besitzen nicht die nötigen Rechte");
        if (isset($_POST['save'])) {
            if (!empty($_POST['url'])) {
                $db = DBManager::get();
                $sql = "INSERT INTO media_links (name,url,course_id,description) VALUES(?,?,?,?)";
                $sth = $db->prepare($sql);
                $sth->execute(array(
                    $_POST['name'],
                    $_POST['url'],
                    $this->plugin->seminar_id,
                    $_POST['description']
                ));
                $this->flash_now('info', 'Link wurde erstellt');
            } else {
                $this->flash_now('error', 'Link wurde nicht eingegeben');
            }
        }
    }

    function edit_action() {
        if (!$this->_isDozent)
            throw new AccessDeniedException("Sie besitzen nicht die nötigen Rechte");
        $db = DBManager::get();
        if (isset($_GET['id'])) {
            $sql = "SELECT * FROM media_links WHERE course_id = ? AND id = ?";
            $sth = $db->prepare($sql);
            $sth->execute(array($this->plugin->seminar_id, $_GET['id']));
            $this->link = $sth->fetch(PDO::FETCH_OBJ);
        } else {
            $this->flash_now('error', 'ID wurde nicht eingegeben');
        }

        if (isset($_POST['edit'])) {
            if (!empty($_POST['url']) && !empty($_POST['id'])) {

                $sql = "UPDATE media_links SET name= ?,url = ?,description = ? WHERE course_id = ? AND id = ?";
                $sth = $db->prepare($sql);
                $sth->execute(array(
                    $_POST['name'],
                    $_POST['url'],
                    $_POST['description'],
                    $this->plugin->seminar_id,
                    $_POST['id']
                ));
                $sql = "SELECT * FROM media_links WHERE course_id = ? AND id = ?";
                $sth = $db->prepare($sql);
                $sth->execute(array($this->plugin->seminar_id, $_POST['id']));
                $this->link = $sth->fetch(PDO::FETCH_OBJ);
                $this->flash_now('info', 'Link wurde bearbeitet');
            } else {
                $this->flash_now('error', 'Link wurde nicht eingegeben');
            }
        }
    }

    function delete_action() {
        if (!$this->_isDozent)
            throw new AccessDeniedException("Sie besitzen nicht die nötigen Rechte");
        $db = DBManager::get();
        if (isset($_GET['id'])) {
            $sql = "DELETE FROM media_links WHERE course_id = ? AND id = ?";
            $sth = $db->prepare($sql);
            $sth->execute(array($this->plugin->seminar_id, $_GET['id']));

            $this->flash_now('info', 'Link wurde gelöscht');
        } else {
            $this->flash_now('error', 'ID wurde nicht eingegeben');
        }
        $sql = "SELECT * FROM media_links WHERE course_id = ?";
        $sth = $db->prepare($sql);
        $sth->execute(array($this->plugin->seminar_id));
        $this->links = $sth->fetchAll(PDO::FETCH_OBJ);
    }

}

