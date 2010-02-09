<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
 * Configurationfile for the evaluation module
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


# Include all required files ================================================ #

# ====================================================== end: including files #


# Define public constants =================================================== #

/* General constants ------------------------------------------------------- */
define ("YES", 1);
define ("NO", 0);
define ("DEBUG", 1);
define ("QUOTED", 1);
define ("UNQUOTED", 0);
define ("EVAL_MIN_SEARCHLEN", 3);
define ("EVAL_MAX_TEMPLATENAMELEN", 22);
/* -------------------------------------------------- end: general constants */

/* Path constants ---------------------------------------------------------- */
define ("EVAL_PATH_RELATIV", "lib/evaluation/");
define ("EVAL_PATH", EVAL_PATH_RELATIV);
define ("EVAL_PATH_CLASSES", EVAL_PATH."classes/");
define ("EVAL_PATH_DBCLASSES", EVAL_PATH_CLASSES."db/");
define ("PATH_PICTURES", $GLOBALS['ASSETS_URL'] . "images/");
/* ----------------------------------------------------- end: path constatns */

/* Class constants --------------------------------------------------------- */
define ("EVAL_FILE_EDIT", "evaluation_admin_edit.inc.php");
define ("EVAL_FILE_TEMPLATE", "evaluation_admin_template.inc.php");
define ("EVAL_FILE_OVERVIEW", "evaluation_admin_overview.inc.php");
define ("EVAL_FILE_SHOW", "show_evaluation.php");
define ("EVAL_FILE_ADMIN", "admin_evaluation.php");

define ("EVAL_FILE_OBJECT", EVAL_PATH_CLASSES."EvaluationObject.class.php");
define ("EVAL_FILE_OBJECTDB", EVAL_PATH_DBCLASSES."EvaluationObjectDB.class.php");
define ("EVAL_FILE_EVAL", EVAL_PATH_CLASSES."Evaluation.class.php");
define ("EVAL_FILE_EVALDB", EVAL_PATH_DBCLASSES."EvaluationDB.class.php");
define ("EVAL_FILE_GROUP", EVAL_PATH_CLASSES."EvaluationGroup.class.php");
define ("EVAL_FILE_GROUPDB", EVAL_PATH_DBCLASSES."EvaluationGroupDB.class.php");
define ("EVAL_FILE_QUESTION", EVAL_PATH_CLASSES."EvaluationQuestion.class.php");
define ("EVAL_FILE_QUESTIONDB", EVAL_PATH_DBCLASSES."EvaluationQuestionDB.class.php");
define ("EVAL_FILE_ANSWER", EVAL_PATH_CLASSES."EvaluationAnswer.class.php");
define ("EVAL_FILE_ANSWERDB", EVAL_PATH_DBCLASSES."EvaluationAnswerDB.class.php");
define ("EVAL_FILE_EXPORTMANAGER", EVAL_PATH_CLASSES."EvaluationExportManager.class.php");
define ("EVAL_FILE_EXPORTMANAGERCSV", EVAL_PATH_CLASSES."EvaluationExportManagerCSV.class.php");
define ("EVAL_FILE_EVALTREE", EVAL_PATH_CLASSES."EvaluationTree.class.php");
define ("EVAL_FILE_EDIT_TREEVIEW", EVAL_PATH_CLASSES."EvaluationTreeEditView.class.php");
define ("EVAL_FILE_SHOW_TREEVIEW", EVAL_PATH_CLASSES."EvaluationTreeShowUser.class.php");
define ("EVAL_FILE_CSS", "evaluation.css");

define ("HTML", EVAL_PATH_CLASSES."HTML.class.php");
define ("HTMLempty", EVAL_PATH_CLASSES."HTMLempty.class.php");
/* --------------------------------------------------- end:  class constants */

/* Library constants ------------------------------------------------------- */
define ("EVAL_LIB_COMMON", EVAL_PATH."evaluation.lib.php");
define ("EVAL_LIB_OVERVIEW", EVAL_PATH."evaluation_admin_overview.lib.php");
define ("EVAL_LIB_EDIT", EVAL_PATH."evaluation_admin_edit.lib.php");
define ("EVAL_LIB_TEMPLATE", EVAL_PATH."evaluation_admin_template.lib.php");
define ("EVAL_LIB_SHOW", EVAL_PATH."evaluation_show.lib.php");
/* -------------------------------------------------- end: library constants */

/* Picture constants ------------------------------------------------------- */
define ("EVAL_PIC_ICON", PATH_PICTURES."eval-icon.gif");
define ("EVAL_PIC_PREVIEW", PATH_PICTURES."preview.gif");
define ("EVAL_PIC_ADMIN", PATH_PICTURES."administration.gif");
define ("EVAL_PIC_LOGO", "evaluation.jpg");
define ("EVAL_PIC_ARROW", PATH_PICTURES."forumgruen.gif");
define ("EVAL_PIC_ARROW_ACTIVE", PATH_PICTURES."forumgruenrunt.gif");
define ("EVAL_PIC_SUCCESS", PATH_PICTURES."ok.gif");
define ("EVAL_PIC_ERROR",   PATH_PICTURES."x.gif");
define ("EVAL_PIC_INFO",    PATH_PICTURES."ausruf.gif");
define ("EVAL_PIC_INFO_SMALL", PATH_PICTURES."ausruf_small2.gif");
define ("EVAL_PIC_HELP",    PATH_PICTURES."info.gif");
define ("EVAL_PIC_MOVE_THIS_GROUP",    PATH_PICTURES."move_this_group.gif");
define ("EVAL_PIC_MOVE_GROUP",    PATH_PICTURES."move_left.gif");
define ("EVAL_PIC_MOVE_UP",    PATH_PICTURES."move_up.gif");
define ("EVAL_PIC_MOVE_DOWN",  PATH_PICTURES."move_down.gif");
define ("EVAL_PIC_MOVE_RIGHT", PATH_PICTURES."move_right.gif");
define ("EVAL_PIC_MOVE_LEFT", PATH_PICTURES."move_left.gif");
define ("EVAL_PIC_MOVE_RIGHT_QUESTION", PATH_PICTURES."move_right_question.gif");
define ("EVAL_PIC_CREATE_ANSWERS", PATH_PICTURES."eval_create_answers.gif");
define ("EVAL_PIC_EDIT_ANSWERS", PATH_PICTURES."eval_edit_answers.gif");
define ("EVAL_PIC_TIME",       PATH_PICTURES."icon-uhr.gif");
define ("EVAL_PIC_EXCLAIM",    PATH_PICTURES."icon-news.gif");
define ("EVAL_PIC_DELETE_GROUP",    PATH_PICTURES."delete-group.gif");
define ("EVAL_PIC_MOVE_BUTTON",    PATH_PICTURES."eval_move_button.gif");
define ("EVAL_PIC_ADD",    PATH_PICTURES."add_right.gif");
define ("EVAL_PIC_ADD_TEMPLATE", PATH_PICTURES."add_sheet.gif");
define ("EVAL_PIC_REMOVE",    PATH_PICTURES."trash.gif");
define ("EVAL_PIC_EDIT",    PATH_PICTURES."edit_transparent.gif");
define ("EVAL_PIC_BACK",    PATH_PICTURES."link_intern.gif");
define ("EVAL_PIC_ARROW_TEMPLATE", PATH_PICTURES."forumgrau.gif");
define ("EVAL_PIC_ARROW_TEMPLATE_OPEN", PATH_PICTURES."forumgraurunt.gif");
define ("EVAL_PIC_ARROW_NEW", PATH_PICTURES."forumgelb.gif");
define ("EVAL_PIC_ARROW_NEW_OPEN", PATH_PICTURES."forumgelbrunt.gif");
define ("EVAL_PIC_ARROW_RUNNING", PATH_PICTURES."forumgruen.gif");
define ("EVAL_PIC_ARROW_RUNNING_OPEN", PATH_PICTURES."forumgruenrunt.gif");
define ("EVAL_PIC_ARROW_STOPPED", PATH_PICTURES."forumrot.gif");
define ("EVAL_PIC_ARROW_STOPPED_OPEN", PATH_PICTURES."forumrotrunt.gif");
define ("EVAL_PIC_TREE_ARROW", PATH_PICTURES."forumgrau.gif");
define ("EVAL_PIC_TREE_ARROW_ACTIVE", PATH_PICTURES."forumrotrunt.gif");
define ("EVAL_PIC_TREE_BLANC", PATH_PICTURES."forumleer.gif");
define ("EVAL_PIC_TREE_ROOT", PATH_PICTURES."eval-icon.gif");
define ("EVAL_PIC_TREE_GROUP", PATH_PICTURES."eval_group.gif");
define ("EVAL_PIC_TREE_GROUP_FILLED", PATH_PICTURES."eval_group_filled.gif");
define ("EVAL_PIC_TREE_QUESTIONGROUP", PATH_PICTURES."eval_qgroup.gif");
define ("EVAL_PIC_TREE_QUESTIONGROUP_FILLED", PATH_PICTURES."eval_qgroup_filled.gif");
define ("EVAL_PIC_EXPORT_FILE", PATH_PICTURES."xls-icon.gif");
define ("EVAL_PIC_YES", PATH_PICTURES."symbol01.gif");
define ("EVAL_PIC_NO",  PATH_PICTURES."symbol02.gif");
define ("EVAL_PIC_SHARED",  PATH_PICTURES."on_small_transparent.gif");
define ("EVAL_PIC_NOTSHARED",  PATH_PICTURES."off_small_blank_transparent.gif");
/* -------------------------------------------------- end: picture constants */

/* CSS constants ----------------------------------------------------------- */
define ("EVAL_CSS_SUCCESS", "eval_success");
define ("EVAL_CSS_ERROR",   "eval_error");
define ("EVAL_CSS_INFO",    "eval_info");
$_include_extra_stylesheet = EVAL_FILE_CSS;
/* ------------------------------------------------------ end: css constants */

# ===================================================== end: define constants #
?>
