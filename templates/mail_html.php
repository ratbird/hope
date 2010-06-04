<HTML>
<HEAD>
  <LINK REL="stylesheet" HREF="<?=$GLOBALS['ASSETS_URL']?>stylesheets/style.css" TYPE="text/css" />
</HEAD>
<BODY>
<CENTER>
<TABLE STYLE="text-align:left; width:700px; min-width:700px; max-width:700px; background-color:white;" CELLSPACING=0 CELLPADDING=0>
  <TR STYLE="height:90px; min-height:90px; max-height:90px;">
    <TD STYLE="height:90px; min-height:90px; max-height:90px; background-color:white;">
      <IMG SRC="<?=$GLOBALS['ASSETS_URL']?>images/locale/<?=$lang?>/LC_PICTURES/mail_header_<?=$lang?>.png">
    </TD>
  </TR>
  <TR>
    <TD STYLE="padding:10px;">
<?=$message?>
    </TD>
  </TR>
  <TR>
    <TD>
      <HR>
      <SPAN STYLE="font-size:10px;"><?=sprintf(_("Diese E-Mail ist eine Kopie einer systeminternen Nachricht, die in Stud.IP an %s versendet wurde."), $rec_fullname)?></SPAN><BR/>
<? $studip = "<A HREF=\"".$GLOBALS['ABSOLUTE_URI_STUDIP']."\">".$GLOBALS['ABSOLUTE_URI_STUDIP']."</A>"; ?>
      <SPAN STYLE="font-size:10px;"><?=sprintf(_("Sie erreichen Stud.IP unter %s"), "<A HREF=\"".$GLOBALS['ABSOLUTE_URI_STUDIP']."\">".$GLOBALS['ABSOLUTE_URI_STUDIP']."</A>")?></SPAN>
    </TD>
  </TR>
</TABLE>
</CENTER>
</BODY>
</HTML>
