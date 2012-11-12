<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// integrity.view.php
// Integrity checks for the Stud.IP database
// This file contains only SQL Queries
// Copyright (c) 2002 André Noack <noack@data-quest.de> 
// Suchi & Berg GmbH <info@data-quest.de>
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
// User
$_views["USER_USERINFO"]= array("pk"=>"user_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT a.user_id FROM user_info a LEFT JOIN auth_user_md5 b USING(user_id) WHERE ISNULL(b.user_id)");
$_views["USER_SEMUSER"]= array("pk"=>"user_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.user_id FROM seminar_user a LEFT JOIN auth_user_md5 b USING(user_id) WHERE ISNULL(b.user_id)");
$_views["USER_INSTUSER"]= array("pk"=>"user_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.user_id FROM user_inst a LEFT JOIN auth_user_md5 b USING(user_id) WHERE ISNULL(b.user_id)");
$_views["USER_CONTACT"]= array("pk"=>"contact_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.contact_id FROM contact a LEFT JOIN auth_user_md5 b ON(a.user_id=b.user_id) LEFT JOIN auth_user_md5 c ON(a.owner_id=c.user_id) WHERE ISNULL(b.user_id) OR ISNULL(c.user_id)");
$_views["USER_STUDUSER"]= array("pk"=>"user_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.user_id FROM user_studiengang a LEFT JOIN auth_user_md5 b USING(user_id) WHERE ISNULL(b.user_id)");
$_views["USER_ARCHIVUSER"]= array("pk"=>"user_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.user_id FROM archiv_user a LEFT JOIN auth_user_md5 b USING(user_id) WHERE ISNULL(b.user_id)");
$_views["USER_ADMISSIONUSER"]= array("pk"=>"user_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.user_id FROM admission_seminar_user a LEFT JOIN auth_user_md5 b USING(user_id) WHERE ISNULL(b.user_id)");
$_views["USER_STATUSGRUPPEUSER"]= array("pk"=>"user_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.user_id FROM statusgruppe_user a LEFT JOIN auth_user_md5 b USING(user_id) WHERE ISNULL(b.user_id)");
$_views["USER_OBJECT_USER_VISIT"]= array("pk"=>"user_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT a.user_id FROM `object_user_visits` a LEFT JOIN auth_user_md5 b USING(user_id)
                            WHERE ISNULL(b.user_id)");
//Seminar
$_views["SEM_SEMUSER"]= array("pk"=>"Seminar_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.Seminar_id FROM seminar_user a LEFT JOIN seminare b USING(Seminar_id) WHERE ISNULL(b.Seminar_id)");
$_views["SEM_ADMISSIONSTUD"]= array("pk"=>"Seminar_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.Seminar_id FROM admission_seminar_studiengang a LEFT JOIN seminare b USING(Seminar_id) WHERE ISNULL(b.Seminar_id)");
$_views["SEM_ADMISSIONUSER"]= array("pk"=>"Seminar_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.Seminar_id FROM admission_seminar_user a LEFT JOIN seminare b USING(Seminar_id) WHERE ISNULL(b.Seminar_id)");
$_views["SEM_SEMINST"]= array("pk"=>"Seminar_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.Seminar_id FROM seminar_inst a LEFT JOIN seminare b USING(Seminar_id) WHERE ISNULL(b.Seminar_id)");
$_views["SEM_TERMINE"]= array("pk"=>"range_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT range_id FROM termine a LEFT JOIN  seminare b ON (range_id=Seminar_id) WHERE ISNULL(b.Seminar_id)");
$_views["SEM_SEM_TREE"]= array("pk"=>"seminar_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT a.seminar_id FROM seminar_sem_tree a LEFT JOIN  seminare b USING(Seminar_id) WHERE ISNULL(b.Seminar_id)");
$_views["SEM_OBJECT_USER_VISIT"]= array("pk"=>"object_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT object_id FROM `object_user_visits` LEFT JOIN seminare b ON(object_id=Seminar_id ) 
                            WHERE b.Seminar_id is null AND type='sem' ");
//Institut
$_views["INST_USER"]= array("pk"=>"Institut_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.Institut_id FROM user_inst a LEFT JOIN Institute b USING(Institut_id) WHERE ISNULL(b.Institut_id)");
$_views["INST_SEM"]= array("pk"=>"Institut_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.Institut_id FROM seminar_inst a LEFT JOIN Institute b USING(Institut_id) WHERE ISNULL(b.Institut_id)");
$_views["INST_OBJECT_USER_VISIT"]= array("pk"=>"object_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT object_id FROM `object_user_visits` a LEFT JOIN Institute b ON(object_id=Institut_id ) 
                            WHERE b.Institut_id is null AND a.type='inst' ");


//Archiv
$_views["ARCHIV_USER"]= array("pk"=>"Seminar_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.Seminar_id FROM archiv_user a LEFT JOIN archiv b USING(Seminar_id) WHERE ISNULL(b.Seminar_id)");

//Studiengang
$_views["STUD_ADMISSONSEM"]= array("pk"=>"studiengang_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.studiengang_id FROM admission_seminar_studiengang a LEFT JOIN studiengaenge b USING(studiengang_id) WHERE ISNULL(b.studiengang_id) AND a.studiengang_id NOT LIKE 'all'");
$_views["STUD_ADMISSONUSER"]= array("pk"=>"studiengang_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.studiengang_id FROM admission_seminar_user a LEFT JOIN studiengaenge b USING(studiengang_id) WHERE ISNULL(b.studiengang_id) AND a.studiengang_id NOT LIKE 'all'");
$_views["STUD_USER"]= array("pk"=>"studiengang_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT   a.studiengang_id FROM user_studiengang a LEFT JOIN studiengaenge b USING(studiengang_id) WHERE ISNULL(b.studiengang_id)");


//UNION dokumente
$_views["DOCS_SEM"]= array("pk"=>"Seminar_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT DISTINCT a.Seminar_id from dokumente a INNER JOIN seminare USING(Seminar_id)");
$_views["DOCS_INST"]= array("pk"=>"Seminar_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT DISTINCT a.Seminar_id from dokumente a INNER JOIN Institute b ON(a.Seminar_id=b.Institut_id)");

//UNION folder
$_views["FOLDER_SEM"]= array("pk"=>"range_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT DISTINCT a.range_id from folder a INNER JOIN seminare b ON(b.Seminar_id=a.range_id)");
$_views["FOLDER_INST"]= array("pk"=>"range_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT DISTINCT a.range_id from folder a INNER JOIN Institute b ON(b.Institut_id=a.range_id)");
$_views["FOLDER_TERM"]= array("pk"=>"range_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT DISTINCT a.range_id from folder a INNER JOIN termine b ON(b.termin_id=a.range_id)");
$_views["FOLDER_FOLD"]= array("pk"=>"range_id","temp_table_type"=>"HEAP",
                            "query"=>"SELECT DISTINCT a.range_id from folder a INNER JOIN folder b ON(b.folder_id=a.range_id)");
?>

