<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

/**
 * StudIPTemplateEngine.class.php
 *
 * @author 		Dennis Reil <dennis.reil@offis.de>
 * @author 		Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright
 * @license
 * @package 	studip
 * @subpackage 	pluginengine
 */

/**
 * @deprecated is now deprecated
 *
 */
class StudIPTemplateEngine
{
	static function makeHeadline($title,$full_width=true,$img="")
	{
		if (!$full_width) {
			echo "\n<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"blank\" width=\"70%\">";
		} else {
			echo"\n<table  border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" >";
		}
		// echo "\n<tr><td>";
		if (strlen($img) > 0){
			printf("\n<tr><td class=\"topic\" width=\"99%%\">&nbsp;<img src=\"$img\" border=\"0\" align=\"texttop\"><b>&nbsp;&nbsp;");
		}
		else {
			print("\n<tr><td class=\"topic\" width=\"99%%\">&nbsp;<b>&nbsp;&nbsp;");
		}
		printf($title);
		printf("</b></td></tr></table>");
	}

	static function startContentTable($full_width=true)
	{
		if (!$full_width){
			echo ("<table border=\"0\" width=\"70%\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"#ffffff\">");
		}
		else {
			echo ("<table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"#ffffff\">");
		}
		?>
		<tr>
			<td height="5" colspan="3"></td>
		</tr>
		<tr>
			<td width="5">
			<!-- Pixelrand 1%??-->
			</td>
			<td valign="top">
				<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td>
		<?php
	}

	static function createInfoBoxTableCell()
	{
		?>
					</td>
				</tr>
				</table>
			   </td>
			<td align="right" valign="top" width="270" class="blank">
		<?php
	}

	static function endInfoBoxTableCell()
	{
		?>
			</td>
		</tr>
		<tr>
			<td width="5">
			<!-- Pixelrand 1%??-->
			</td>
			<td valign="top">
				<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td>
		<?php
	}

	static function endContentTable()
	{
		?>
					</td>
				</tr>
				   </table>
			</td>
			<td width="5">
			</td>
		</tr>
		<tr>
			<td height="5" colspan="3"></td>
		</tr>
		</table>
		<?php
	}

	static function makeContentHeadline($title,$colspan=2)
	{
		printf('<table width="100%%" cellpadding="0" cellspacing="0"><tr><th align="left">&nbsp;%s</th></tr></table>', $title);
	}

	/**
	 * @deprecated since Stud.IP version 1.10
	 *
	 * @param unknown_type $text
	 * @param unknown_type $colspan
	 */
	static function showErrorMessage($text,$colspan=2)
	{
		echo MessageBox::error($text);
	}

	/**
	 * @deprecated since Stud.IP version 1.10
	 *
	 * @param unknown_type $text
	 * @param unknown_type $colspan
	 */
	static function showSuccessMessage($text,$colspan=2)
	{
		echo MessageBox::success($text);
	}

	/**
	 * @deprecated since Stud.IP version 1.10
	 *
	 * @param unknown_type $text
	 * @param unknown_type $colspan
	 */
	static function showInfoMessage($text,$colspan=2)
	{
		echo MessageBox::info($text);
	}

	static function showQuestionMessage($text,$colspan=2,$newrow=true)
	{
		$colspan = $colspan -1;
		?>

		<tr>
			<td valign="top"><img src="<?=$GLOBALS['ASSETS_URL']?>images/ausruf.gif"></td>
			<td valign="top" colspan=<?= $colspan?>>
			<?= sprintf("%s <br>", htmlReady($text))?>
			<?= sprintf("<a href=\"%s\">" . makeButton("ja2") . "</a>&nbsp; \n",$GLOBALS["PHP_SELF"])?>
			<?= sprintf("<a href=\"$PHP_SELF\">" . makeButton("nein") . "</a>\n")?>
			</td>
		</tr>
		<tr>
			<td colspan="<?=$colspan?>" height="5">&nbsp;</td>
		</tr>
		<?php
	}
}
?>
