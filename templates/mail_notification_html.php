<HTML>
<HEAD>
<style type="text/css">
<!--
body, td, th, blockquote {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    color: #000000;
}
body {
    margin: 0px;
    background-color: #91A2B6;
    background : url('<?=$GLOBALS['ASSETS_URL']?>images/bathtile.jpg');
}
.topic {
    border: 0px;
    background-image: url('<?=$GLOBALS['ASSETS_URL']?>/images/fill1.gif');
    background-color:#4A5681;
    color:#FFFFFF;
    font-size: 12pt;
    padding: 2px;
}
.steel1 {
    background-color:#e5e5e5;
}
.steelgraulight {
    background-color:#fdfdfd;
}
a, a:link, a:visited {
    color: #213499;
    text-decoration: none;
}
-->
</style>
</HEAD>
<BODY>
<CENTER>
<TABLE STYLE="text-align:left; width:700px; min-width:700px; max-width:700px; background-color:white;">
  <TR STYLE="height:90px; min-height:90px; max-height:90px;">
    <TD STYLE="height:90px; min-height:90px; max-height:90px; background-color:white;">
      <IMG SRC="<?=$GLOBALS['ASSETS_URL']?>images/locale/<?=$lang?>/LC_PICTURES/mail_header_notification_<?=$lang?>.png">
    </TD>
  </TR>
  <TR>
    <TD>
    <SPAN STYLE="font-size:12px;"><?=_("Sie erhalten hiermit in regelmäßigen Abständen Informationen über Neuigkeiten und Änderungen in Ihren abonnierten Veranstaltungen.")?><BR/><BR/>
    <?=_("Über welche Inhalte und in welchem Format Sie informiert werden wollen, können Sie hier einstellen:")?><BR/>
    <A HREF="<?=$GLOBALS['ABSOLUTE_URI_STUDIP']?>sem_notification.php"><?=$GLOBALS['ABSOLUTE_URI_STUDIP']?>sem_notification.php</A><BR/><BR/></SPAN>
    <TABLE BORDER=0 STYLE="width:700px;" CELLSPACING=0>
<? foreach ($news as $sem_titel=>$data) : ?>
      <TR>
        <TD COLSPAN="2" CLASS="topic" STYLE="font-size:14px; font-weight:bold;"><A STYLE="text-decoration:none; color:white;" HREF="<?=$GLOBALS['ABSOLUTE_URI_STUDIP']?>seminar_main.php?auswahl=<?=$data[0]['range_id']?>"><?=htmlReady($sem_titel)?><?=(($semester = get_semester($n['range_id'])) ? ' ('.$semester.')' : '')?></A></TD>
      </TR>
<? foreach ($data as $n) : ?>
<? $cssSw->switchClass(); ?>
      <TR>
        <TD CLASS="<?=$cssSw->getClass()?>" STYLE="font-size:12px;"><A STYLE="text-decoration:none;" HREF="<?=$n['url']?>"><?=htmlReady($n['txt'])?></A></TD>
        <TD CLASS="<?=$cssSw->getClass()?>" STYLE="width:25px; text-align:center;"><A HREF="<?=$n['url']?>"><IMG SRC="<?=$GLOBALS['ASSETS_URL']?>images/<?=$n['icon']?>" ALT="<?=htmlReady($n['txt'])?>" TITLE="<?=htmlReady($n['txt'])?>" BORDER=0></A></TD>
      </TR>
<? endforeach ?>
<? endforeach ?>
    </TABLE>
    </TD>
  </TR>
  <TR>
    <TD>
      <HR>
      <SPAN STYLE="font-size:10px;"><?=_("Diese Nachricht wurde automatisch vom Stud.IP-System generiert. Sie können darauf nicht antworten.")?></SPAN>
    </TD>
  </TR>
</TABLE>
</CENTER>
</BODY>
</HTML>
