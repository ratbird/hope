<tr>
	<td class="infobox" width="100%" colspan="2">
		<font size="-1"><b><?= $selectionlist_title ?>:</b></font>
	</td>    
</tr>  
<tr>
	<td class="infobox" width="100%" align="center" colspan="2">
		<form action="<?=$GLOBALS['PHP_SELF']?>">
			<table border="0" cellspacing="0" cellpadding="0">
				<tbody><tr>
					<td valign="center">
						<select name="newFilter" size="1">    
						<? for ($i = 0; $i < count($selectionlist); $i++) : ?>
							<? if ( $selectionlist[$i]['is_selected'] ) : ?>
							<option value="<?=$selectionlist[$i]['value']?>" selected><?=$selectionlist[$i]['linktext']?></option>
							<? else: ?>
							<option value="<?=$selectionlist[$i]['value']?>"><?=$selectionlist[$i]['linktext']?></option>
							<? endif; ?>    
						<? endfor; ?>  
						</select>
					</td>
					<td valign="center">
						&nbsp;
						<input type="image" style="width: 22px; height: 23px;" src="<?=$GLOBALS['ASSETS_URL']?>images/GruenerHakenButton.png" border="0">
						<input type="hidden" name="cmd" value="applyFilter">
					</td>
				</tr></tbody>     
			</table> 
		</form>
	</td>    
</tr>

