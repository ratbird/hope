<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* EditSettings.class.php
*
* all the forms/views to edit the settings
*
*
* @author		Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access		public
* @modulegroup		resources
* @module		EditSettings.class.php
* @package		resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// EditSettings.class.php
// enthaelt alle Forms/Views zum Bearbeiten der Einstellungen
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

require_once ($RELATIVE_PATH_RESOURCES."/lib/ResourceObject.class.php");
require_once ('lib/classes/cssClassSwitcher.inc.php');

$cssSw = new cssClassSwitcher;

class EditSettings {
	var $db;
	var $db2;

	//Konstruktor
	function EditSettings() {
		$this->db=new DB_Seminar;
		$this->db2=new DB_Seminar;
	}

	//private
	function getDependingResources($category_id)  {
		$db=new DB_Seminar;
		$db->query("SELECT count(resource_id) AS count FROM resources_objects WHERE category_id='$category_id' ");
		$db->next_record();
		return $db->f("count");
	}

	//private
	function getDependingTypes($property_id)  {
		$db=new DB_Seminar;
		$db->query("SELECT count(category_id) AS count FROM resources_categories_properties WHERE property_id='$property_id' ");
		$db->next_record();
		return $db->f("count");
	}

	//private
	function selectTypes() {
		$this->db->query("SELECT *  FROM resources_categories ORDER BY name");
		if (!$this->db->nf())
			return FALSE;
		else
			return TRUE;
	}

	//private
	function selectRootUser() {
		$this->db->query("SELECT *  FROM resources_user_resources WHERE resource_id ='all' ");
		if (!$this->db->nf())
			return FALSE;
		else
			return TRUE;
	}

	//private
	function selectProperties($category_id='', $all=FALSE) {
		if (!$all)
			$this->db2->query ("SELECT *  FROM resources_categories_properties LEFT JOIN resources_properties USING (property_id) WHERE category_id = '$category_id' ORDER BY name");
		else
			$this->db2->query ("SELECT *  FROM resources_properties ORDER BY name");
		if (!$this->db->nf())
			return FALSE;
		else
			return TRUE;
	}

	//private
	function selectLocks($type) {
		$this->db->query ("SELECT * FROM resources_locks WHERE type = '$type' ORDER BY lock_begin");
		if (!$this->db->nf())
			return FALSE;
		else
			return TRUE;
	}


	function showPermsForms() {
		global $PHP_SELF, $search_string_search_root_user, $search_root_user, $cssSw;

		$resObject =& ResourceObject::Factory();

		?>
		<table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
			<form method="POST" action="<?echo $PHP_SELF ?>?add_root_user=TRUE">
			<tr>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="4%">
					<img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width=1 height=20>&nbsp;
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="42%" align="left">
					<font size=-1><b><?=_("Name")?></b></font>
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="10%" align="center">
					<font size=-1><b><?=_("Berechtigungen")?></b></font>
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="10%" align="center">
					<font size=-1><b><?=_("X")?></b></font>
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="4%">
					<img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width=1 height=20>&nbsp;
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="30%" align="center">
					<font size=-1><b><?=_("Suchen/hinzuf&uuml;gen")?></b></font>
				</td>
			</tr>
			<tr>
				<td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="42%">
					<font size=-1><?=_("Diese NutzerInnen sind als globale Ressourcen-Administratoren mit folgenden Rechten eingetragen:")?></font>
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="10%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="10%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="30%" valign="top"><font size=-1><?=_("NutzerInnen hinzuf&uuml;gen")?></font><br />
				<? showSearchForm("search_root_user", $search_string_search_root_user, TRUE, FALSE, TRUE) ?>
				</td>
			</tr>
			<?
			$this->selectRootUser();
			while ($this->db->next_record()) {
			?>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="42%" valign="top">
					<font size=-1><a href="<? echo $resObject->getOwnerLink($this->db->f("user_id"))."\">".$resObject->getOwnerName(TRUE, $this->db->f("user_id")) ?></a>
					(<? echo get_username($this->db->f("user_id")); ?>)<br>
						<?
						switch ($this->db->f("perms")) {
							case "admin":
								print _("<b>Admin</b>: Nutzer kann s&auml;mtliche Belegungen und Eigenschaften &auml;ndern und Rechte vergeben");
							break;
							case "tutor":
								print _("<b>Tutor</b>: Nutzer kann s&auml;mtliche Belegungen &auml;ndern");
							break;
							case "autor":
								print _("<b>Autor</b>: Nutzer kann nur eigene Belegungen &auml;ndern");
							break;
						}
						?>
					</font><br />
				</td>
				<input type="HIDDEN" name="change_root_user_id[]" value="<? echo $this->db->f("user_id") ?>" />
				<td class="<? echo $cssSw->getClass() ?>" width="10%" valign="middle" align="center">
					<select name="change_root_user_perms[]">
						<font size=-1><option <? ($this->db->f("perms")=="admin") ? printf ("selected") : printf (""); ?>>admin</option></font>
						<?
						/*<font size=-1><option <? ($this->db->f("perms")=="tutor") ? printf ("selected") : printf (""); ?>>tutor</option></font>
						<font size=-1><option <? ($this->db->f("perms")=="autor") ? printf ("selected"): printf (""); ?>>autor</option></font>*/
						?>
					</select>
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="10%" valign="middle" align="center">
					<font size=-1>
						<a href="<? echo $PHP_SELF ?>?delete_root_user_id=<? echo $this->db->f("user_id") ?>">
							<img src="<?= $GLOBALS['ASSETS_URL'] ?>images/trash.gif" border="0" <?=tooltip(_("Berechtigungen l&ouml;schen")) ?> />
						</a>
					</font>
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="30%" align="center">&nbsp;
				</td>
			</tr>
			<? } ?>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" colspan=5 align="center"><br />&nbsp; <input type="IMAGE" <?=makeButton("uebernehmen", "src") ?> border=0 name="submit" value="<?=_("Zuweisen") ?>"><br />&nbsp;
				</td>
			</tr>
		</table>
		<br /><br />
		<?
	}

	function showTypesForms() {
		global $PHP_SELF, $RELATIVE_PATH_RESOURCES, $created_category_id, $cssSw;

		//the avaiable object-icons for every category
		$availableIcons = array (1=>"cont_res1.gif",2=> "cont_res2.gif",3=> "cont_res3.gif", 4=>"cont_res4.gif",5=> "cont_res5.gif");

		?>
		<table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
			<tr>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="4%">
					<img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width=1 height=20>&nbsp;
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="25%" align="left">
					<font size=-1><b><?=_("Typ")?></b></font>
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="65%" align="left">
					<font size=-1><b><?=_("zugeordnete Eigenschaften")?></b></font>
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="6%" align="center">
					<font size=-1><b><?=_("X")?></b></font>
				</td>
			</tr>
			<form method="POST" action="<?echo $PHP_SELF ?>#a">
			<tr>
				<td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="25%" align="left" valign="top">
					<font size=-1><?=_("neuer Typ:")?></font>
				</td>
				<td class="<? echo $cssSw->getClass() ?>" colspan=2 align="left">
					<font size=-1><input type="TEXT" name="add_type" size=50 maxlength=255 value="<<?=_("bitte geben Sie hier den Namen ein")?>>" /></font>
					&nbsp; <font size=-1><input type="IMAGE" name="_add_type" <?=makeButton("anlegen", "src")?> border=0 />
					<br /><input type="CHECKBOX" name="resource_is_room">&nbsp;Ressourcen-Typ wird als Raum behandelt</font>


				</td>
			</tr>
			</form>
			<form method="POST" action="<?echo $PHP_SELF ?>?change_categories=TRUE">
			<?
			$this->selectTypes();
			while ($this->db->next_record()) {
				$depRes=$this->getDependingResources($this->db->f("category_id"));
				if ($created_category_id == $this->db->f("category_id"))
					print "<a name=\"a\"></a>";
				?>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="25%" valign="top">
					<font size=-1><input type="TEXT" name="change_category_name[<?=$this->db->f("category_id")?>]" value="<? echo $this->db->f("name") ?>" size="20" maxlength="255" /></font><br />

					<?
					foreach ($availableIcons as $key => $val) {
						printf ("<input type=\"RADIO\" name=\"change_category_iconnr[%s]\" %s value=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/%s\" />&nbsp; ", $this->db->f("category_id"), ($this->db->f("iconnr") == $key) ? "checked" : "", $key, $val);
					}
					?>
					<font size=-1><? ($this->db->f("is_room")) ? print "<br />"._("wird als <i>Raum</i> behandelt"):print("");?></font>
					<font size=-1><? printf("<br />"._("wird von <b>%s</b> Objekten verwendet")."</font><br />", $depRes); ?>
					<font size=-1><? ($this->db->f("system")) ? print( _("(systemobjekt)")."<br />") :print("") ?></font>


					<input type="HIDDEN" name="change_properties_id[]" value="<?=$this->db->f("category_id")?>" />
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="65%" valign="top">
					<table border=0 celpadding=2 cellspacing=0 width="100%" align="center">
						<?
						$tmp_resvis='';
						$this->selectProperties($this->db->f("category_id"));
						while ($this->db2->next_record()) {
							//schon zugewiesene Properties merken
							$tmp_resvis[]=$this->db2->f("property_id");
						?>
						<tr>
							<td class="<? echo $cssSw->getClass() ?>" width="33%">
								<font size=-1><? echo $this->db2->f("name") ?></font><br />
							</td>
							<td class="<? echo $cssSw->getClass() ?>" width="33%" nowrap>
								<font size=-1><?
									switch ($this->db2->f("type")) {
										case "bool":
											echo _("Zustand Ja/Nein");
										break;
										case "text":
											echo _("mehrzeiliges Textfeld");
										break;
										case "num":
											echo _("einzeiliges Textfeld");
										break;
										case "select":
											echo _("Auswahlfeld");
										break;
									}
									?>
								</font><br />
							</td>
							<td class="<? echo $cssSw->getClass() ?>" width="3%">
								<?
								if (!$this->db2->f("system")) {
									?>
									<a href="<? echo $PHP_SELF ?>?delete_type_property_id=<? echo $this->db2->f("property_id") ?>&delete_type_category_id=<? echo $this->db2->f("category_id") ?>">
									<img src="<?= $GLOBALS['ASSETS_URL'] ?>images/trash.gif" border="0" <?=tooltip(_("Eigenschaft löschen"))?>
									</a>
									<?
								} else {
									?>
									<img src="<?= $GLOBALS['ASSETS_URL'] ?>images/lighttrash.gif" border="0" <?=tooltip(_("Löschen der Eigenschaft nicht möglich, Systemobjekt!"))?>
									<?
								}
								?>
							</td>
							<td class="<? echo $cssSw->getClass() ?>" width="31%">
								<?
								if ($this->db->f("is_room")) {
									?>
									<input type="HIDDEN" name="requestable[]" value="_id1_<?=($this->db->f("category_id"))?>" />
									<input type="HIDDEN" name="requestable[]" value="_id2_<?=($this->db2->f("property_id"))?>" />
									<input type="CHECKBOX" name="requestable[]" <?=($this->db2->f("requestable")) ? "checked" : "" ?> />
									<font size="-1"><?=_("w&uuml;nschbar")?></font>
									<?
								} else {
									print "&nbsp;";
								}
								?>
							</td>
						</tr>
						<? } ?>
						<tr>
							<td class="<? echo $cssSw->getClass() ?>" width="33%">
							<?
							$this->selectProperties($this->db->f("category_id"), TRUE);
							if (($this->db2->nf() != sizeof ($tmp_resvis)) || (!is_array($tmp_resvis))) {
								?>
								<select name="add_type_property_id[<?=$this->db->f("category_id")?>]">
								<?
								//Noch nicht vergebene Properties zum Vergeben anbieten
								while ($this->db2->next_record()) {
									if (is_array($tmp_resvis))
										if (!in_array($this->db2->f("property_id"), $tmp_resvis))
											$give_it=TRUE;
										else
											$give_it=FALSE;
									else
										$give_it=TRUE;
									if ($give_it) {
										?>
									<option value="<? echo $this->db2->f("property_id") ?>"><? echo htmlReady($this->db2->f("name")) ?></option>
									</option>
										<?

									}
								}
								?>
								</select>
							</td>
								<td class="<? echo $cssSw->getClass() ?>" width="67%" colspan=2>
									<input type="IMAGE" <?=makeButton("zuweisen", "src") ?> name="change_category_add_property<?=$this->db->f("category_id")?>" border=0 />&nbsp;
									<input type="IMAGE" name="change_types" <?=makeButton("uebernehmen", "src")?> border="0" /><br />
							</td>
							<?
							} else {
							?>
							<td class="<? echo $cssSw->getClass() ?>" width="100%" colspan=3>
									<input type="IMAGE" <?=makeButton("zuweisen", "src") ?> name="change_category_add_property<?=$this->db->f("category_id")?>" border=0 />&nbsp;
							</td>
							<?
							}
							?>
						</tr>
					</table>
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="10%" valign="bottom"align="center">
					<font size=-1>
						diesen Typ<br />
						<?
						if (($depRes==0) && (!$this->db->f("system"))) {
						?>
						<a href="<? echo $PHP_SELF ?>?delete_type=<? echo $this->db->f("category_id") ?>">
						<?=makeButton ("loeschen", "img");
						} else {
							print "<img ".makeButton ("n_loeschen", "src")." border=\"0\" ".tooltip(_("Dieser Typ kann nicht gelöscht werden, da er von Ressourcen verwendet wird!"))." />";
						} ?>
					</font><br />
				</td>
			</tr>
			<? } ?>
			</form>
		</table>
		<br /><br />
		<?
	}

	function showPropertiesForms() {
		global $PHP_SELF, $cssSw;

		?>
		<table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
			<tr>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="4%">
					<img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width=1 height=20>&nbsp;
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="25%" align="left">
					<font size=-1><b><?=_("Eigenschaft")?></b></font>
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="65%" align="left">
					<font size=-1><b><?=_("Art der Eigenschaft")?></b></font>
				</td>
				<td class="<? echo $cssSw->getHeaderClass() ?>" width="6%" align="center">
					<font size=-1><b><?=_("X")?></b></font>
				</td>
			</tr>
			<form method="POST" action="<?echo $PHP_SELF ?>?add_type_category_id=<? echo $this->db2->f("category_id")?>">
			<tr>
				<td class="<? echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="25%" align="left">
					<font size=-1><?=_("neue Eigenschaft:")?></font>
				</td>
				<td class="<? echo $cssSw->getClass() ?>" colspan=2 align="left" valign="bottom">
					<font size=-1><input type="TEXT" name="add_property" size=50 maxlength=255 value="<bitte geben Sie hier den Namen ein>" /></font>
					<select name="add_property_type">
						<font size=-1><option value="bool"><?=_("Zustand")?></option></font>
						<font size=-1><option value="num"><?=_("einzeiligesTextfeld")?></option></font>
						<font size=-1><option value="text"><?=_("mehrzeiligesTextfeld")?></option></font>
						<font size=-1><option value="select"><?=_("Auswahlfeld")?></option></font>
					</select>
					&nbsp;<font size=-1><input type="IMAGE" name="_add_property" <?=makeButton("anlegen", "src")?> border=0 /></font>
				</td>
			</tr>
			</form>
			<form method="POST" action="<?echo $PHP_SELF ?>?change_properties=TRUE">
			<?
			$this->selectProperties($dummy, TRUE);
			while ($this->db2->next_record()) {
				$depTyp=$this->getDependingTypes($this->db2->f("property_id"));
				?>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="25%" valign="top">
					<font size=-1><input type="TEXT" name="change_property_name[<?=$this->db2->f("property_id")?>]" value="<? echo htmlReady($this->db2->f("name")) ?>" size="20" maxlength="255" /></font><br />
					<font size=-1>wird von <b><? echo  $depTyp ?></b> Typen verwendet</font><br />
					<font size=-1><? ($this->db2->f("system")) ? print( _("(systemobjekt)")) :print("") ?></font><br />
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="65%" valign="top">
					<table border=0 celpadding=2 cellspacing=0 width="100%" align="center">
					<tr>
						<td class="<? echo $cssSw->getClass() ?>" width="50%">
							<font size=-1><?=_("Art:")?></font>
							<br />
							<select name="send_property_type[<?=$this->db2->f("property_id")?>]">
								<font size=-1><option <? ($this->db2->f("type") == "bool") ? print "selected" : print "" ?> value="bool"><?=_("Zustand")?></option></font>
								<font size=-1><option <? ($this->db2->f("type") == "num") ? print "selected" : print "" ?> value="num"><?=_("einzeiliges Textfeld")?></option></font>
								<font size=-1><option <? ($this->db2->f("type") == "text") ? print "selected" : print "" ?> value="text"><?=_("mehrzeiliges Textfeld")?></option></font>
								<font size=-1><option <? ($this->db2->f("type") == "select") ? print "selected" : print "" ?> value="select"><?=_("Auswahlfeld")?></option></font>
							</select>
							<br />
							<?
							if ($this->db2->f("type") == "bool") {
								printf ("<font size=-1>"._("Bezeichnung:")."</font><br />");
								printf ("<font size=-1><input type=\"TEXT\" name=\"send_property_bool_desc[%s]\" value=\"%s\" size=30 maxlength=255 /></font><br />", $this->db2->f("property_id"), htmlReady($this->db2->f("options")));
							}
							if ($this->db2->f("type") == "select") {
								printf ("<font size=-1>"._("Optionen:")."</font><br />");
								printf ("<font size=-1><input type=\"TEXT\" name=\"send_property_select_opt[%s]\" value=\"%s\" size=30 maxlength=255 /></font><br />", $this->db2->f("property_id"), htmlReady($this->db2->f("options")));
							}
							?>
							<font size=-1>Vorschau:</font>
							<br>
							<?
							switch ($this->db2->f("type")) {
								case "bool":
									printf ("<input type=\"CHECKBOX\" name=\"dummy\" checked />&nbsp; <font size=-1>%s</font>", htmlReady($this->db2->f("options")));
								break;
								case "num":
									printf ("<input type=\"TEXT\" name=\"dummy\" size=30 maxlength=255 />");
								break;
								case "text";
									printf ("<textarea name=\"dummy\" cols=30 rows=2 ></textarea>");
								break;
								case "select";
									$options=explode (";",$this->db2->f("options"));
									printf ("<select name=\"dummy\">");
									foreach ($options as $a) {
										printf ("<option value=\"%s\">%s</option>", $a, htmlReady($a));
									}
									printf ("</select>");
								break;
							}
							?>
						</td>
						<td class="<? echo $cssSw->getClass() ?>" width="50%" valign="bottom">&nbsp;
						 	<input type="IMAGE" name="_send_property_type" <?=makeButton("uebernehmen", "src") ?> border=0 />
						</td>
					</tr>
					</table>
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="10%" valign="bottom"align="center">
					<font size=-1>
						<?=_("diese Eigenschaft")?><br />
						<?
						if (($depTyp==0) && (!$this->db->f("system"))) {
						?>
						<a href="<? echo $PHP_SELF ?>?delete_property=<? echo $this->db2->f("property_id") ?>">
						<?=makeButton("loeschen", "img");
						} else {
							print makeButton("n_loeschen", "img");
						} ?>
					</font><br />
				</td>
			</tr>
			<? } ?>
		</form>
		</table>
		<br /><br />
		<?
	}

	function showSettingsForms() {
		global $PHP_SELF, $cssSw, $resources_data;

		?>
		<table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
		<form method="POST" action="<?echo $PHP_SELF ?>?change_global_settings=TRUE">
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="96%" align="left">
					<font size=-1><b><?=_("Zulassen von <i>Raum</i>anfragen")?></b><br /><br />
					&nbsp;&nbsp;&nbsp;<input type="CHECKBOX" name="allow_requests" <? print($GLOBALS["RESOURCES_ALLOW_ROOM_REQUESTS"]) ? "checked" : ""; print ">&nbsp;"._("NutzerInnen k&ouml;nnen im Rahmen der Veranstaltungsverwaltung Raumeigenschaften und konkrete R&auml;ume w&uuml;nschen.")?><br />
					<br />
				</td>
			</tr>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="96%" align="left">
					<font size=-1><b><?=_("Sperrzeiten f&uuml;r die Bearbeitung von <i>Raum</i>belegungen")?></b><br /><br />
					<?=_("Die <b>Bearbeitung</b> von Belegungen soll f&uuml;r alle lokalen Ressourcen-Administratoren zu folgenden Bearbeitungszeiten geblockt werden:")?><br /><br />
					&nbsp;&nbsp;&nbsp;<input type="CHECKBOX" name="locking_active" <? print($GLOBALS['RESOURCES_LOCKING_ACTIVE']) ? "checked" : ""; print ">&nbsp;"._("Blockierung ist zu den angegebenen Sperrzeiten aktiv:")?><br />
					<br />
					<table border="0" cellspacing="0" cellpadding="0" width="50%" align="left">
					<?
					$this->selectLocks("edit");
					if ($this->db->nf()) {
						$rows = 0;
						?>

						<tr>
							<td width="20%"><font size="-1">
								<?=_("Beginn:")?>
							</td>
							<td width="20%">
								<font size="-1">
								<?=_("Ende:")?>
							</td>
						</tr>
						<?
						while ($this->db->next_record()) {
							$rows++;
							if ($rows <= $this->db->nf()) {
								?>
						<tr>
							<td colspan="3" style="{background-image: url('<?= $GLOBALS['ASSETS_URL'] ?>images/line.gif')}"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width ="10" height="1" /></td>
						</tr>
						<tr>
							<td colspan="3"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width ="10" height="3" /></td>
						</tr>
								<?
							}
						?>
						<tr>
						<?
							if ($resources_data["lock_edits"][$this->db->f("lock_id")]) {
								//edit lock start time
								print"<td width=\"40%%\"><font size=\"-1\">";
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_day[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />.", ($this->db->f("lock_begin")) ? date("d", $this->db->f("lock_begin")) : _("tt"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_month[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />.", ($this->db->f("lock_begin")) ? date("m", $this->db->f("lock_begin")) : _("mm"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_year[]\" size=\"4\" maxlength=\"4\" value=\"%s\" />&nbsp;", ($this->db->f("lock_begin")) ? date("Y", $this->db->f("lock_begin")) : _("jjjj"));
								printf ("<br /><input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_hour[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />:", ($this->db->f("lock_begin")) ? date("H", $this->db->f("lock_begin")) : _("ss"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_min[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />", ($this->db->f("lock_begin")) ? date("i", $this->db->f("lock_begin")) : _("mm"));
								print "</font></td>";

								//edit lock end time
								print "<td width=\"40%%\"><font size=\"-1\">";
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_day[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />.", ($this->db->f("lock_end")) ? date("d", $this->db->f("lock_end")) : _("tt"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_month[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />.", ($this->db->f("lock_end")) ? date("m", $this->db->f("lock_end")) : _("mm"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_year[]\" size=\"4\" maxlength=\"4\" value=\"%s\" />&nbsp;", ($this->db->f("lock_end")) ? date("Y", $this->db->f("lock_end")) : _("jjjj"));
								printf ("<br /><input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_hour[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />:", ($this->db->f("lock_end")) ? date("H", $this->db->f("lock_end")) : _("ss"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_min[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />", ($this->db->f("lock_end")) ? date("i", $this->db->f("lock_end")) : _("mm"));
								print "</font></td>";

								print "<td width=\"20%%\" align=\"right\" valign=\"top\"><font size=\"-1\">";
								print "<br /><input type=\"HIDDEN\" name=\"lock_id[]\" value=\"".$this->db->f("lock_id")."\" />";
								print "<input type=\"IMAGE\" name=\"lock_sent\" src=\"".$GLOBALS['ASSETS_URL']."images/haken_transparent.gif\" border=\"0\" ".tooltip(_("Diesen Eintrag speichern"))." />";
								print "&nbsp;&nbsp;<a href=\"$PHP_SELF?kill_lock=".$this->db->f("lock_id")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" ".tooltip(_("Diesen Eintrag löschen"))."/></a>";
								print "</td></tr>";
							} else {
								printf ("<td width=\"40%%\"><font size=\"-1\">%s</font></td>", date("d.m.Y H:i", $this->db->f("lock_begin")));
								printf ("<td width=\"40%%\"><font size=\"-1\">%s</font></td>", date("d.m.Y H:i", $this->db->f("lock_end")));
								print "<td width=\"10%%\" align=\"right\" valign=\"top\"><a href=\"$PHP_SELF?edit_lock=".$this->db->f("lock_id")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/edit_transparent.gif\" border=\"0\" ".tooltip(_("Diesen Eintrag bearbeiten"))."/>&nbsp;&nbsp;</a>";
								print "<a href=\"$PHP_SELF?kill_lock=".$this->db->f("lock_id")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" ".tooltip(_("Diesen Eintrag löschen"))."/></a></td>";
							}
						}
						print "</tr>";
					}
					?>
						<tr>
							<td colspan="3">
								<a href="<?=$PHP_SELF?>?create_lock=edit"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/add_right.gif" border="0" /></a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="96%" align="left">
					<font size=-1><b><?=_("Sperrzeiten f&uuml;r f&uuml;r <i>Raum</i>belegungen")?></b><br /><br />
					<?=_("Die <b>Belegung</b> soll f&uuml;r alle lokalen Ressourcen-Administratoren zu folgenden Belegungszeitenzeiten geblockt werden:")?><br /><br />
					&nbsp;&nbsp;&nbsp;<input type="CHECKBOX" name="assign_locking_active" <? print($GLOBALS['RESOURCES_ASSIGN_LOCKING_ACTIVE']) ? "checked" : ""; print ">&nbsp;"._("Blockierung ist zu den angegebenen Sperrzeiten aktiv:")?><br />
					<br />
					<table border="0" cellspacing="0" cellpadding="0" width="50%" align="left">
					<?
					$this->selectLocks("assign");
					if ($this->db->nf()) {
						$rows = 0;
						?>

						<tr>
							<td width="20%"><font size="-1">
								<?=_("Beginn:")?>
							</td>
							<td width="20%">
								<font size="-1">
								<?=_("Ende:")?>
							</td>
						</tr>
						<?
						while ($this->db->next_record()) {
							$rows++;
							if ($rows <= $this->db->nf()) {
								?>
						<tr>
							<td colspan="3" style="{background-image: url('<?= $GLOBALS['ASSETS_URL'] ?>images/line.gif')}"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width ="10" height="1" /></td>
						</tr>
						<tr>
							<td colspan="3"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" width ="10" height="3" /></td>
						</tr>
								<?
							}
						?>
						<tr>
						<?
							if ($resources_data["lock_edits"][$this->db->f("lock_id")]) {
								//edit lock start time
								print"<td width=\"40%%\"><font size=\"-1\">";
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_day[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />.", ($this->db->f("lock_begin")) ? date("d", $this->db->f("lock_begin")) : _("tt"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_month[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />.", ($this->db->f("lock_begin")) ? date("m", $this->db->f("lock_begin")) : _("mm"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_year[]\" size=\"4\" maxlength=\"4\" value=\"%s\" />&nbsp;", ($this->db->f("lock_begin")) ? date("Y", $this->db->f("lock_begin")) : _("jjjj"));
								printf ("<br /><input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_hour[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />:", ($this->db->f("lock_begin")) ? date("H", $this->db->f("lock_begin")) : _("ss"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_begin_min[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />", ($this->db->f("lock_begin")) ? date("i", $this->db->f("lock_begin")) : _("mm"));
								print "</font></td>";

								//edit lock end time
								print "<td width=\"40%%\"><font size=\"-1\">";
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_day[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />.", ($this->db->f("lock_end")) ? date("d", $this->db->f("lock_end")) : _("tt"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_month[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />.", ($this->db->f("lock_end")) ? date("m", $this->db->f("lock_end")) : _("mm"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_year[]\" size=\"4\" maxlength=\"4\" value=\"%s\" />&nbsp;", ($this->db->f("lock_end")) ? date("Y", $this->db->f("lock_end")) : _("jjjj"));
								printf ("<br /><input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_hour[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />:", ($this->db->f("lock_end")) ? date("H", $this->db->f("lock_end")) : _("ss"));
								printf ("<input type=\"TEXT\" style=\"{font-size:8pt;}\" name=\"lock_end_min[]\" size=\"2\" maxlength=\"2\" value=\"%s\" />", ($this->db->f("lock_end")) ? date("i", $this->db->f("lock_end")) : _("mm"));
								print "</font></td>";

								print "<td width=\"20%%\" align=\"right\" valign=\"top\"><font size=\"-1\">";
								print "<br /><input type=\"HIDDEN\" name=\"lock_id[]\" value=\"".$this->db->f("lock_id")."\" />";
								print "<input type=\"IMAGE\" name=\"lock_sent\" src=\"".$GLOBALS['ASSETS_URL']."images/haken_transparent.gif\" border=\"0\" ".tooltip(_("Diesen Eintrag speichern"))." />";
								print "&nbsp;&nbsp;<a href=\"$PHP_SELF?kill_lock=".$this->db->f("lock_id")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" ".tooltip(_("Diesen Eintrag löschen"))."/></a>";
								print "</td></tr>";
							} else {
								printf ("<td width=\"40%%\"><font size=\"-1\">%s</font></td>", date("d.m.Y H:i", $this->db->f("lock_begin")));
								printf ("<td width=\"40%%\"><font size=\"-1\">%s</font></td>", date("d.m.Y H:i", $this->db->f("lock_end")));
								print "<td width=\"10%%\" align=\"right\" valign=\"top\"><a href=\"$PHP_SELF?edit_lock=".$this->db->f("lock_id")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/edit_transparent.gif\" border=\"0\" ".tooltip(_("Diesen Eintrag bearbeiten"))."/>&nbsp;&nbsp;</a>";
								print "<a href=\"$PHP_SELF?kill_lock=".$this->db->f("lock_id")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" border=\"0\" ".tooltip(_("Diesen Eintrag löschen"))."/></a></td>";
							}
						}
						print "</tr>";
					}
					?>
						<tr>
							<td colspan="3">
								<a href="<?=$PHP_SELF?>?create_lock=assign"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/add_right.gif" border="0" /></a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="96%" align="left">
					<font size=-1><b><?=_("Optionen beim Bearbeiten von Anfragen")?></b><br /><br />
					&nbsp;&nbsp;&nbsp;Anzahl der Belegungen, ab der R&auml;ume dennoch mit Einzelterminen passend belegt werden k&ouml;nnen: <input type="TEXT" size="5" maxlength="10" name="allow_single_assign_percentage" value="<? print($GLOBALS["RESOURCES_ALLOW_SINGLE_ASSIGN_PERCENTAGE"]);?>" />%<br />
					&nbsp;&nbsp;&nbsp;Anzahl ab der Einzeltermine gruppiert bearbeitet werden sollen: <input type="TEXT" size="3" maxlength="5" name="allow_single_date_grouping" value="<? print($GLOBALS["RESOURCES_ALLOW_SINGLE_DATE_GROUPING"]);?>" /><br />
					<br />
				</td>
			</tr>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="96%" align="left">
					<font size=-1><b><?=_("Einordnung von <i>R&auml;umen</i> in Orga-Struktur")?></b><br /><br />
					&nbsp;&nbsp;&nbsp;<input type="CHECKBOX" name="enable_orga_classify" <? print($GLOBALS["RESOURCES_ENABLE_ORGA_CLASSIFY"]) ? "checked" : ""; print ">&nbsp;"._("<i>R&auml;ume</i> k&ouml;nnen Fakult&auml;ten und Einrichtungen unabh&auml;ngig von Besitzerrechten zugeordnet werden.")?><br />
					<?
					/*&nbsp;&nbsp;&nbsp;<input type="CHECKBOX" name="enable_orga_admin_notice" <? print($GLOBALS["RESOURCES_ENABLE_ORGA_ADMIN_NOTICE"]) ? "checked" : ""; print ">&nbsp;"._("Bei <i>Raum</i>w&uuml;nschen von DozentInnen auf <i>R&auml;ume</i> fremder Einrichtungen und Fakult&auml;ten die Administratoren benachrichtigen. ")?><br />*/
					?>
					<br />
				</td>
			</tr>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="96%" align="left">
					<font size=-1><b><?=_("Anlegen von <i>R&auml;umen</i>")?></b><br /><br />
					<?=_("Das Anlegen von <i>R&auml;umen</i> kann nur durch folgende Personenkreise vorgenommen werden:")?><br /><br />
					&nbsp;&nbsp;&nbsp;<select name="allow_create_resources">
						<option value="1" <? print($GLOBALS["RESOURCES_ALLOW_CREATE_ROOMS"] == "1") ? "selected" : ""; print ">"._("NutzerInnen ab globalem Status Tutor")?></option>
						<option value="2" <? print($GLOBALS["RESOURCES_ALLOW_CREATE_ROOMS"] == "2") ? "selected" : ""; print ">"._("NutzerInnen ab globalem Status Admin")?></option>
						<option value="3" <? print($GLOBALS["RESOURCES_ALLOW_CREATE_ROOMS"] == "3") ? "selected" : ""; print ">"._("nur globale Ressourcenadministratoren")?></option>
					</select>
					<br />&nbsp;
				</td>
			</tr>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="96%" align="left">
					<font size=-1><b><?=_("Vererbte Berechtigungen von Veranstaltungen und Einrichtungen f&uuml;r Ressourcen")?></b><br /><br />
					<?=_("Mitglieder von Veranstaltungen oder Einrichtungen erhalten folgende Rechte in Ressourcen, die diesen Veranstaltungen oder Einrichtungen geh&ouml;ren:")?><br /><br />
						&nbsp;&nbsp;&nbsp;<input type="RADIO" name="inheritance_rooms" value="1" <? print ($GLOBALS["RESOURCES_INHERITANCE_PERMS_ROOMS"] == "1") ? "checked" : "" ?>/><?=_("die lokalen Rechte der Einrichtung oder Veranstaltung werden &uuml;bertragen")?><br />
						&nbsp;&nbsp;&nbsp;<input type="RADIO" name="inheritance_rooms" value="2" <? print ($GLOBALS["RESOURCES_INHERITANCE_PERMS_ROOMS"] == "2") ? "checked" : "" ?>/><?=_("nur Autorenrechte (eigene Belegungen anlegen und bearbeiten)")?><br />
						&nbsp;&nbsp;&nbsp;<input type="RADIO" name="inheritance_rooms" value="3" <? print ($GLOBALS["RESOURCES_INHERITANCE_PERMS_ROOMS"] == "3") ? "checked" : "" ?>/><?=_("keine Rechte")?><br />
					</select>
					<br />
				</td>
			</tr>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" width="4%">&nbsp;
				</td>
				<td class="<? echo $cssSw->getClass() ?>" width="96%" align="left">
					<font size=-1><b><?=_("Vererbte Berechtigungen von Veranstaltungen und Einrichtungen f&uuml;r <i>R&auml;ume</i>")?></b><br /><br />
					<?=_("Mitglieder von Veranstaltungen oder Einrichtungen erhalten folgende Rechte in <i>R&auml;umen</i>, die diesen Veranstaltungen oder Einrichtungen geh&ouml;ren:")?><br /><br />
						&nbsp;&nbsp;&nbsp;<input type="RADIO" name="inheritance" value="1" <? print ($GLOBALS["RESOURCES_INHERITANCE_PERMS"] == "1") ? "checked" : "" ?>/><?=_("die lokalen Rechte der Einrichtung oder Veranstaltung werden &uuml;bertragen")?><br />
						&nbsp;&nbsp;&nbsp;<input type="RADIO" name="inheritance" value="2" <? print ($GLOBALS["RESOURCES_INHERITANCE_PERMS"] == "2") ? "checked" : "" ?>/><?=_("nur Autorenrechte (eigene Belegungen anlegen und bearbeiten)")?><br />
						&nbsp;&nbsp;&nbsp;<input type="RADIO" name="inheritance" value="3" <? print ($GLOBALS["RESOURCES_INHERITANCE_PERMS"] == "3") ? "checked" : "" ?>/><?=_("keine Rechte")?><br />
					</select>
					<br />
				</td>
			</tr>
			<tr>
				<td class="<? $cssSw->switchClass(); echo $cssSw->getClass() ?>" colspan="2" align="middle">&nbsp;
				 	<input type="IMAGE" name="_send_settings" <?=makeButton("uebernehmen", "src") ?> border=0 />
				</td>
			</tr>
		</form>
		</table>
		<br /><br />
		<?
	}
	function showPesonalSettingsForms() {
		global $PHP_SELF;

		?>
		<table border=0 celpadding=2 cellspacing=0 width="99%" align="center">
		<form method="POST" action="<?echo $PHP_SELF ?>">
		</table>
		<br /><br />
		<?
	}
}
