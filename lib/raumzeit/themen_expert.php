<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
themen_expert.php: GUI for the expert mode of the theme management
Copyright (C) 2005-2007 Till Glöggler <tgloeggl@uos.de>

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


// -- here you have to put initialisations for the current page
$sess->register('issue_open');
$sess->register('raumzeitFilter');
$sess->register('chronoGroupedFilter');

require_once ('lib/classes/Seminar.class.php');
require_once ('lib/raumzeit/raumzeit_functions.inc.php');
require_once ('lib/raumzeit/themen_expert.inc.php');
require_once 'lib/admin_search.inc.php';

if ($RESOURCES_ENABLE) {
	include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObject.class.php");
	include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourcesUserRoomsList.class.php");
	include_once ($RELATIVE_PATH_RESOURCES."/lib/VeranstaltungResourcesAssign.class.php");
	include_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObjectPerms.class.php");
	$resList = new ResourcesUserRoomsList($user->id, TRUE, FALSE, TRUE);
}

$CURRENT_PAGE = _("Verwaltung der Themen des Ablaufplans");
Navigation::activateItem('/admin/course/schedule');

//Change header_line if open object
$header_line = getHeaderLine($id);
if ($header_line)
	$CURRENT_PAGE = $header_line." - ".$CURRENT_PAGE;

//Output starts here

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
include 'lib/include/admin_search_form.inc.php';

if (!$perm->have_studip_perm('tutor', $id)) {
	die;
}

define('SELECTED', ' checked');
define('NOT_SELECTED', '');

$powerFeatures = true;

$sem = new Seminar($id);
$sem->checkFilter();
$themen =& $sem->getIssues();
if (isset($_REQUEST['cmd'])) {
	$cmd = $_REQUEST['cmd'];
}

//workarounds for multiple submit-buttons

foreach ($_REQUEST as $key => $val) {
	if ( (strlen($key) == 34) && ($key[33] == 'x') ) {
		$keys = explode('_', $key);
		$submitter_id = $keys[0];
	}

	if ( (strlen($key) == 45) && ($key[44] == 'x') ) {
		$keys = explode('_', $key);
		$submitter_id = $keys[0];
		$cycle_id = $keys[1];
	}

	if ( (strlen($key) == 67) && ($key[66] == 'x') ) {
		$keys = explode('_', $key);
		$submitter_id = $keys[0];
		$cycle_id = $keys[1];
	}
	if ($_REQUEST['allOpen']) {
		if (strstr($key, 'theme_title')) {
			$keys = explode('§', $key);
			$changeTitle[$keys[1]] = $val;
		}
		if (strstr($key, 'theme_description')) {
			$keys = explode('§', $key);
			$changeDescription[$keys[1]] = $val;
		}
		if (strstr($key, 'forumFolder')) {
			$keys = explode('§', $key);
			$changeForum[$keys[1]] = $val;
		}
		if (strstr($key, 'fileFolder')) {
			$keys = explode('§', $key);
			$changeFile[$keys[1]] = $val;
		}
	}
}

if (isset($_REQUEST['doAddIssue_x'])) {
	$cmd = 'doAddIssue';
}

if (isset($_REQUEST['changeIssue_x'])) {
	$cmd = 'changeIssue';
}

if (isset($_REQUEST['addIssue_x'])) {
	$cmd = 'addIssue';
}

if (isset($_REQUEST['saveAll_x'])) {
	$cmd = 'saveAll';
}

if (isset($_REQUEST['checkboxAction_x'])) {
	$cmd = 'checkboxAction';
}

if (isset($_REQUEST['chronoAutoAssign_x'])) {
	$cmd = 'chronoAutoAssign';
}

if (isset($submitter_id)) {
	if ($submitter_id == 'autoAssign') {
		$cmd = 'autoAssign';
	} else {
		if (is_array($_REQUEST['themen'])) {
			$termin =& $sem->getSingleDate($submitter_id, $cycle_id);
			foreach ($_REQUEST['themen'] as $iss_id) {
				$termin->addIssueID($iss_id);
			}
			$termin->store();
		} else {
			$sem->createInfo(_("Sie haben kein Thema für die Zuordnung ausgewählt!"));
		}
	}
}

if (($chronoGroupedFilter) == '') {
	$chronoGroupedFilter = 'grouped';
}

$sem->registerCommand('autoAssign', 'themen_autoAssign');
$sem->registerCommand('changeChronoGroupedFilter', 'themen_changeChronoGroupedFilter');
$sem->registerCommand('chronoAutoAssign', 'themen_chronoAutoAssign');
$sem->registerCommand('open', 'themen_open');
$sem->registerCommand('close', 'themen_close');
$sem->registerCommand('doAddIssue', 'themen_close');
$sem->registerCommand('doAddIssue', 'themen_doAddIssue');
$sem->registerCommand('deleteIssueID', 'themen_deleteIssueID');
$sem->registerCommand('changeIssue', 'themen_changeIssue');
$sem->registerCommand('deleteIssue', 'themen_deleteIssue');
$sem->registerCommand('addIssue', 'themen_addIssue');
$sem->registerCommand('changePriority', 'themen_changePriority');
$sem->registerCommand('openAll', 'themen_openAll');
$sem->registerCommand('saveAll', 'themen_saveAll');
$sem->registerCommand('checkboxAction', 'themen_checkboxAction');
$sem->processCommands();

unset($themen);
$themen =& $sem->getIssues(true);	// read again, so we have the actual sort order and so on
?>
<FORM action="<?= URLHelper::getLink($PHP_SELF) ?>" method="post">
<TABLE width="100%" border="0" cellpadding="2" cellspacing="0">
	<TR>
		<TD class="blank" colspan="2">
			<TABLE border="0" cellspacing="0" cellpadding="2" width="100%">
				<TR>
					<TD class="blank">
						<A name="filter">
						<?
							$all_semester = $semester->getAllSemesterData();
							$passed = false;
							foreach ($all_semester as $val) {
								if ($sem->getStartSemester() <= $val['vorles_beginn']) $passed = true;
								if ($passed && ($sem->getEndSemesterVorlesEnde() >= $val['vorles_ende'])) {
									$tpl['semester'][$val['beginn']] = $val['name'];
									if ($raumzeitFilter != ($val['beginn'])) {
									} else {
										$tpl['seleceted'] = $val['beginn'];
									}
								}
							}
							$tpl['selected'] = $raumzeitFilter;
							$tpl['semester']['all'] = _("Alle Semester");
							include('lib/raumzeit/templates/choose_filter.tpl');
						?>
					 </TD>
					 <TD class="blank" align="right">
						<?
							$tpl['view']['simple'] = 'Standard';
							$tpl['view']['expert'] = 'Erweitert';
							$tpl['selected'] = $viewModeFilter;
							include('lib/raumzeit/templates/choose_view.tpl');
						?>
					</TD>
				</TR>
			</TABLE>
		</TD>
  </TR>
	<? while ($msg = $sem->getNextMessage()) { ?>
	<TR>
		<TD class="blank" colspan=2><br>
			<?parse_msg($msg);?>
		</TD>
	</TR>
	<? } ?>
	<TR>
		<TD class="blank" width="50%" height="15"></TD>
		<TD class="blank" width="50%" height="15"></TD>
	</TR>
  <TR>
		<TD align="center" class="blank" width="50%" valign="top">
			<TABLE width="90%" cellspacing="0" cellpadding="2" border="0">
				<TR>
					<TD colspan="3" height="28">
						<FONT size="-1">&nbsp;</FONT>
					</TD>
				</TR>
				<TR>
			    <TD class="printhead" colspan="3">
	    			<FONT size="-1">
			    		&nbsp;<B><?=_("Sitzungsthemen")?></B>
			    	</FONT>
			    </TD>
				</TR>
				<TR>
					<TD class="blank" colspan="3">
						<FONT size="-1">
							<SELECT name="numIssues">
								<? for ($i = 1; $i <= 15; $i++) { ?>
								<OPTION value="<?=$i?>"><?=$i?></OPTION>
								<? } ?>
							</SELECT>
							<?=("neue Themen")?>
						</FONT>
						<INPUT type="image" <?=makebutton('anlegen', 'src')?> align="absmiddle" name="addIssue">
					</TD>
				</TR>
				<TR>
					<TD class="blank" colspan="3">
						&nbsp;
					</TD>
				</TR>
				<TR>
					<TD class="steelgraulight" colspan="3" align="center">
						<A href="<?= URLHelper::getLink($PHP_SELF."?cmd=openAll") ?>">
							<IMG src="<?= $GLOBALS['ASSETS_URL'] ?>images/forumgraurunt.gif" title="<?=_("Alle Themen aufklappen")?>" border="0">
						</A>
					</TD>
				</TR>
				<?
				if ( isset($cmd) && ($cmd == 'addIssue') && ($numIssues == 1)) {
					$tpl['submit_name'] = 'doAddIssue';
					$tpl['first'] = true;
					$tpl['last'] = true;
					$issue_open[''] = true;
					include('lib/raumzeit/templates/thema.tpl');
				}

				$count = 0;
				$max = sizeof($themen);
				$max--;
				if (is_array($themen))	foreach ($themen as $themen_id => $thema) {
					if (isset($_REQUEST['checkboxAction'])) {
						switch ($_REQUEST['checkboxAction']) {
							case 'chooseAll':
								$tpl['selected'] = SELECTED;
								break;

							case 'invert':
								if ($choosen[$themen_id] == TRUE) {
									$tpl['selected'] = NOT_SELECTED;
								} else {
									$tpl['selected'] = SELECTED;
								}
								break;
						}
					}

					$tpl['theme_title'] = htmlReady($thema->getTitle());
					$tpl['class'] = 'steel';
					$tpl['issue_id'] = $thema->getIssueID();
					$tpl['priority'] = $thema->getPriority();

					$tpl['first'] = false;
					$tpl['last'] = false;
					if ($count == 0) {
						$tpl['first'] = true;
					}  // no else condition here, because it can be first and last at same time
					if ($count == $max) {		// instead of an else condition
						$tpl['last'] = true;
					}

					if ($openAll) {
						$tpl['openAll'] = TRUE;
						$issue_open[$themen_id] = TRUE;
					}

					if (($issue_open[$themen_id] && $open_close_id == $themen_id) || $openAll) {
						$tpl['submit_name'] = 'changeIssue';
						$tpl['theme_description'] = htmlReady($thema->getDescription());
						$tpl['forumEntry'] = ($thema->hasForum()) ? SELECTED : NOT_SELECTED;
						$tpl['fileEntry'] = ($thema->hasFile()) ? SELECTED : NOT_SELECTED;
						include('lib/raumzeit/templates/thema.tpl');
					} else {
						unset($issue_open[$themen_id]);
						include('lib/raumzeit/templates/thema.tpl');
					}
					$count++;
				}
				if ($openAll) {
				?>
				<TR>
					<TD class="blank" colspan="3" align="center">
						<INPUT type="hidden" name="allOpen" value="1">
						<INPUT type="image" <?=makebutton('allesuebernehmen', 'src')?> name="saveAll">&nbsp;
						<A href="<?= URLHelper::getLink($PHP_SELF) ?>">
							<IMG <?=makebutton('abbrechen', 'src')?> border="0">
						</A>
					</TD>
				</TR>
				<?
				} else {
				?>
				<TR>
					<TD class="blank" colspan="3" align="left">
					<?
						include('lib/raumzeit/templates/actions_thema.tpl');
					?>
					</TD>
				</TR>
				<?
				}
				?>
			</TABLE>
		</TD>
		<TD align="center" class="blank" width="50%" valign="top">
			<TABLE width="90%" cellspacing="0" cellpadding="2" border="0">
				<TR>
					<TD colspan="3" align="right" height="28">
						<TABLE width="100%" cellspacing="0" cellpadding="0" border="0">
						<? if ($chronoGroupedFilter == 'grouped') { ?>
							<TD background="<?= $GLOBALS['ASSETS_URL'] ?>images/steel1info.jpg">
								<IMG src="<?= $GLOBALS['ASSETS_URL'] ?>images/reiter1.jpg" align="middle">
							</TD>
							<TD background="<?= $GLOBALS['ASSETS_URL'] ?>images/steel1info.jpg">
								<FONT size="-1">
									&nbsp;&nbsp;<?=_("gruppiert")?>&nbsp;&nbsp;
								</FONT>
							</TD>
							<TD background="<?= $GLOBALS['ASSETS_URL'] ?>images/steel1info.jpg">
								<IMG src="<?= $GLOBALS['ASSETS_URL'] ?>images/reiter1.jpg" align="middle">
							</TD>
							<TD background="<?= $GLOBALS['ASSETS_URL'] ?>images/steel2.jpg">
								<FONT size="-1">
									<A href="<?= URLHelper::getLink($PHP_SELF."?cmd=changeChronoGroupedFilter&newFilter=chrono") ?>">
										&nbsp;&nbsp;<?=_("chronologisch")?>&nbsp;&nbsp;
									</A>
								</FONT>
							</TD>
						<? } else { ?>
							<TD background="<?= $GLOBALS['ASSETS_URL'] ?>images/steel2.jpg">
								<FONT size="-1">
									<A href="<?= URLHelper::getLink($PHP_SELF."?cmd=changeChronoGroupedFilter&newFilter=grouped") ?>">
										&nbsp;&nbsp;<?=_("gruppiert")?>&nbsp;&nbsp;
									</A>
								</FONT>
							</TD>
							<TD background="<?= $GLOBALS['ASSETS_URL'] ?>images/steel1info.jpg">
								<IMG src="<?= $GLOBALS['ASSETS_URL'] ?>images/reiter1.jpg" align="middle">
							</TD>
							<TD background="<?= $GLOBALS['ASSETS_URL'] ?>images/steel1info.jpg">
								<FONT size="-1">
									&nbsp;&nbsp;<?=_("chronologisch")?>&nbsp;&nbsp;
								</FONT>
							</TD>
							<TD background="<?= $GLOBALS['ASSETS_URL'] ?>images/steel1info.jpg">
								<IMG src="<?= $GLOBALS['ASSETS_URL'] ?>images/reiter1.jpg" align="middle">
							</TD>
							<? } ?>
						</FONT>
						</TABLE>
					</TD>
				</TR>
				<? if ($chronoGroupedFilter == 'grouped') { ?>
					<TR>
						<TD class="printhead" colspan="3">
							<FONT size="-1">
								&nbsp;<B><?=_("Allgemeine Zeiten")?></B>
							</FONT>
						</TD>
					</TR>
					<?
					$turnus = $sem->getFormattedTurnusDates();

					foreach ($sem->metadate->cycles as $metadate_id => $val) {
						$tpl['md_id'] = $metadate_id;
						$tpl['date'] = $turnus[$metadate_id];
						include('lib/raumzeit/templates/metadate_themen.tpl');

						if ($issue_open[$metadate_id]) {
							$all_semester = $semester->getAllSemesterData();
							$grenze = 0;

							$termine =& $sem->getSingleDatesForCycle($metadate_id);
							foreach ($termine as $singledate_id => $singledate) {

								if ( ($grenze == 0) || ($grenze < $singledate->getStartTime()) ) {
									foreach ($all_semester as $zwsem) {
										if ( ($zwsem['beginn'] < $singledate->getStartTime()) && ($zwsem['ende'] > $singledate->getStartTime()) ) {
											$grenze = $zwsem['ende'];
											?>
												<TR>
												<TD class="steelgraulight" align="center" colspan="9">
												<FONT size="-1"><B><?=$zwsem['name']?></B></FONT>
												</TD>
												</TR>
												<?
										}
									}
								}

								// Template fuer einzelnes Datum
								$tpl = getTemplateDataForSingleDate($singledate, $metadate_id);
								$tpl['space'] = true;
								$tpl['cycle_id'] = $metadate_id;
								if ($tpl['type'] != 1) {
									$tpl['art'] = $TERMIN_TYP[$tpl['type']]['name'];
								} else {
									$tpl['art'] = FALSE;
								}

								include('lib/raumzeit/templates/singledate_themen.tpl');
								if ($iss = $singledate->getIssueIDs()) {
									foreach ($iss as $issue_id) {
										if ($themen[$issue_id]) {
											$tpl['name'] = htmlReady($themen[$issue_id]->getTitle());
											$tpl['class'] = 'steelgraulight';
											$tpl['space'] = true;
											$tpl['issue_id'] = $issue_id;
											$tpl['sd_id'] = $singledate_id;
											$tpl['cycle_id'] = $metadate_id;
										} else {
											$tpl['name'] = '<FONT color="red">Fehlerhafter Eintrag!</FONT>';
											$tpl['class'] = 'steelgraulight';
											$tpl['space'] = true;
											$tpl['issue_id'] = $issue_id;
											$tpl['sd_id'] = $singledate_id;
											$tpl['cycle_id'] = $metadate_id;
										}
											include('lib/raumzeit/templates/thema_short.tpl');
									}
								}

							}
						}
						?>
						<TR>
							<TD class="blank" height="4" colspan="3"></TD>
						</TR>
							<?
					}
					?>
					<TR>
						<TD class="blank" colspan="3">
							&nbsp;
						</TD>
					</TR>
					<TR>
						<TD class="printhead" colspan="3">
							<FONT size="-1">
								&nbsp;<B><?=_("unregelm&auml;&szlig;ige Termine / Blocktermine")?></B>
							</FONT>
						</TD>
					</TR>
					<?
					$termine =& $sem->getSingleDates(true);
					foreach ($termine as $singledate_id => $singledate) {
						$tpl = getTemplateDataForSingleDate($singledate);
						$tpl['space'] = false;

						include('lib/raumzeit/templates/singledate_themen.tpl');
						if ($iss = $singledate->getIssueIDs()) {
							foreach ($iss as $issue_id) {
								$tpl['name'] = htmlReady($themen[$issue_id]->getTitle());
								$tpl['class'] = 'steelgraulight';
								$tpl['space'] = false;
								$tpl['issue_id'] = $issue_id;
								$tpl['sd_id'] = $singledate_id;
								$tpl['cycle_id'] = '';
								include('lib/raumzeit/templates/thema_short.tpl');
							}
						}
					}
				} else {
					/* * * * * * * * * * * * * * * * * * * * * * * * * *
					 *   C H R O N O L O G I S C H E   A N S I C H T   *
					 * * * * * * * * * * * * * * * * * * * * * * * * * */
					?>
					<TR>
						<TD class="printhead" colspan="3">
							<FONT size="-1">
								&nbsp;<B><?=_("Zeiten")?></B>
							</FONT>
						</TD>
					</TR>
					<TR>
						<TD class="steel1" colspan="3">
							&nbsp;
							<FONT size="-1"><?=_("ausgewählte Themen freien Terminen")?></FONT>&nbsp;
							<INPUT type="image" <?=makebutton('zuordnen', 'src')?> align="absMiddle" border="0" name="chronoAutoAssign">
						</TD>
					</TR>
					<?

					$termine = getAllSortedSingleDates($sem);

					$all_semester = $semester->getAllSemesterData();
					$grenze = 0;

					foreach ($termine as $singledate_id => $singledate) {

						// show semester heading
						if ( ($grenze == 0) || ($grenze < $singledate->getStartTime()) ) {
							foreach ($all_semester as $zwsem) {
								if ( ($zwsem['beginn'] < $singledate->getStartTime()) && ($zwsem['ende'] > $singledate->getStartTime()) ) {
									$grenze = $zwsem['ende'];
									?>
										<TR>
											<TD class="steelgraulight" align="center" colspan="9">
												<FONT size="-1"><B><?=$zwsem['name']?></B></FONT>
											</TD>
										</TR>
										<?
								}
							}
						}
						// end "show semester heading"

						$tpl = getTemplateDataForSingleDate($singledate, $metadate_id);
						$tpl['space'] = false;
						$tpl['cycle_id'] = $singledate->getCycleID();
						if ($tpl['type'] != 1) {
							$tpl['art'] = $TERMIN_TYP[$tpl['type']]['name'];
						} else {
							$tpl['art'] = FALSE;
						}

						include('lib/raumzeit/templates/singledate_themen.tpl');

						if ($iss = $singledate->getIssueIDs()) {
							foreach ($iss as $issue_id) {
								$tpl['name'] = htmlReady($themen[$issue_id]->getTitle());
								$tpl['class'] = 'steelgraulight';
								$tpl['space'] = false;
								$tpl['issue_id'] = $issue_id;
								$tpl['sd_id'] = $singledate_id;
								$tpl['cycle_id'] = $singledate->getCycleID();
								include('lib/raumzeit/templates/thema_short.tpl');
							}
						}

					} // foreach termine
				}
				?>
			</TABLE>
		</TD>
  </TR>
	<TR>
		<TD class="blank" width="50%">
			&nbsp;
		</TD>
		<TD class="blank" width="50%">
			&nbsp;
	</TR>
</TABLE>
</FORM>
<?
	$sem->store();
	include 'lib/include/html_end.inc.php';
	page_close();
?>
