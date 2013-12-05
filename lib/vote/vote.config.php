<?php
# Lifter002: TEST
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**
 * This file contains all public defines used for the votes / tests
 *
 * @author      Alexander Willner <mail@AlexanderWillner.de>
 * @copyright   2003 Stud.IP-Project
 * @access      public
 * @module      vote_config
 * @package     vote
 * @modulegroup vote_modules
 */


# Include all required files ================================================ #
# ====================================================== end: including files #


# Define public constants =================================================== #

define ("YES", 1);
define ("NO", 0);

define ("INSTANCEOF_VOTE", "vote");
define ("INSTANCEOF_TEST", "test");
define ("INSTANCEOF_AUTHOR_OBJECT", "AuthorObject");
define ("INSTANCEOF_VOTEDB", "VoteDB");

define ("VOTE_FILE_ADMIN", "admin_vote.php");
define ("VOTE_FILE_SHOW", "lib/vote/vote_show.inc.php");

define ("VOTE_ICON_BIG",     Assets::image_path('icons/16/white/vote.png'));
define ("VOTE_ICON_VOTE",    Assets::image_path('icons/16/grey/vote.png'));
define ("VOTE_ICON_TEST",    Assets::image_path('icons/16/grey/test.png'));
define ("VOTE_ICON_STOPPED", Assets::image_path('icons/16/grey/vote-stopped.png'));
define ("VOTE_ICON_ARROW",   Assets::image_path('icons/16/white/admin.png'));
define ("VOTE_ICON_SUCCESS", Assets::image_path("icons/16/green/accept.png"));
define ("VOTE_ICON_ERROR",   Assets::image_path("icons/16/red/decline.png"));
define ("VOTE_ICON_INFO",    Assets::image_path("icons/16/grey/exclaim.png"));
define ("VOTE_ICON_LIST",    Assets::image_path("icons/16/grey/file-generic.png"));

define ("VOTE_BAR_LEFT",   Assets::image_path("bar_l.gif"));
define ("VOTE_BAR_MIDDLE",  "vote_bar_");
define ("VOTE_BAR_RIGHT",  Assets::image_path("bar_r.gif"));

define ("VOTE_ANSWER_CORRECT", Assets::image_path("icons/16/green/accept.png"));
define ("VOTE_ANSWER_WRONG",   Assets::image_path("icons/16/red/decline.png"));

define ("VOTE_COLOR_SUCCESS", "#008000");
define ("VOTE_COLOR_ERROR",   "#E00000");
define ("VOTE_COLOR_INFO",    "#333333");

define ("VOTE_ANSWER_MAXLEN", 10);

define ("VOTE_STATE_ACTIVE", "active");
define ("VOTE_STATE_NEW", "new");
define ("VOTE_STATE_STOPPED", "stopped");
define ("VOTE_STATE_STOPVIS", "stopvis");
define ("VOTE_STATE_STOPINVIS", "stopinvis");

/**
 * State for resultvisibility. Show the result after the user has voted.
 * @access public
 * @const VOTE_RESULTS_AFTER_VOTE
 */

define ("VOTE_RESULTS_AFTER_VOTE", "delivery");
#define("STATUS_DELIVERY","delivery");

/**
 * State for resultVisibility. Show the result after the end of the vote.
 * @access public
 * @const VOTE_RESULTS_AFTER_END
 */

define ("VOTE_RESULTS_AFTER_END", "end");
#define("STATUS_END","end");

/**
 * State for resultVisibility. Show the result at any time.
 * @access public
 * @const VOTE_RESULTS_ALWAYS
 */

define ("VOTE_RESULTS_ALWAYS", "ever");
#define("STATUS_EVER","ever");

/**
 * State for resultVisibility. Show the result never.
 * @access public
 * @const VOTE_RESULTS_NEVER
 */

define ("VOTE_RESULTS_NEVER", "never");

/**
 * Votestate -> a new vote.
 * @access public
 * @const VOTE_NEW
 */

define ("VOTE_NEW", "new");

/**
 * Votestate -> an active vote.
 * @access public
 * @const VOTE_ACTIVE
 */

define ("VOTE_ACTIVE", "active");

/**
 * Votestate -> stopped and visible
 * @access public
 * @const VOTE_STOPPED_VISIBLE
 */

define ("VOTE_STOPPED_VISIBLE", "stopvis");

/**
 * Votestate -> stopped an invisible
 * @access public
 * @const VOTE_STOPPED_INVISIBLE
 */

define ("VOTE_STOPPED_INVISIBLE", "stopinvis");

/**
 * Imagetype PIE-Graph
 * @access public
 * @const  VOTE_IMAGETYPE_PIE
 */

define ("VOTE_IMAGETYPE_PIE", 0);

/**
 * Imagetype BAR-Graph
 * @access public
 * @const  VOTE_IMAGETYPE_BAR
 */

define ("VOTE_IMAGETYPE_BAR", 1);

/**
 * Imagetype LINE-Graph
 * @access public
 * @const  VOTE_IMAGETYPE_LINE
 */

define ("VOTE_IMAGETYPE_LINE", 2);

/**
 * max length of the title in a headline
 * @access public
 * @const  VOTE_SHOW_MAXTITLELENGTH
 */

define ("VOTE_SHOW_MAXTITLELENGTH", 80);

# ===================================================== end: public constants #

// workaround for BIEST00082
$_SESSION['vote_HTTP_REFERER_2'] = $_SESSION['vote_HTTP_REFERER_1'];
$_SESSION['vote_HTTP_REFERER_1'] = (($_SERVER['SERVER_PORT'] == 443 || $_SERVER['HTTPS'] == 'on')? 'https://':'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

?>
