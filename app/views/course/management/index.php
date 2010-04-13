<?
/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */
$infobox['picture'] = 'verwalten.jpg';
if ($GLOBALS['perm']->have_perm('dozent')) {
    $infobox['content'] = array(
        array(
            'kategorie' => _("Aktionen"),
            'eintrag'   => array(
                array(
                    'text' => _("Diese Veranstaltung <a href=".URLHelper::getLink('copy_assi.php?list=TRUE&new_session=TRUE')."\>kopieren</a> und damit eine neue Veranstaltung mit gleichen Einstellungen erstellen."), 
                    'icon' => 'link_intern.gif'
                ),
      	    )
        )
    );
}

if (($GLOBALS['perm']->have_studip_perm('dozent', $GLOBALS['SessSemName'][1])
    && get_config('ALLOW_DOZENT_ARCHIV'))
    || $GLOBALS['perm']->have_perm('admin')) {

	$infobox['content'][0]['eintrag'][] = array(
        'text' => _("Diese Veranstaltung <a href=".URLHelper::getLink('archiv_assi.php?list=TRUE&new_session=TRUE')."\>archivieren</a> und damit beenden."), 
        'icon' => 'link_intern.gif'
    );

    if ($visible) {
        $text = sprintf(_('Diese Veranstaltung %sunsichtbar%s schalten'),
            '<a href="'.  $controller->url_for('course/management/visible/0') .'">',
            '</a>');
    } else {
        $text = sprintf(_('Diese Veranstaltung %ssichtbar%s schalten'),
            '<a href="'.  $controller->url_for('course/management/visible/1') .'">',
            '</a>');
    }
	$infobox['content'][0]['eintrag'][] = array(
        'text' => $text, 
        'icon' => 'link_intern.gif'
    );
}

$infobox['content'][] = array(
	'kategorie' => _("Information"), 
    'eintrag'   => array(
        array(
            'text' => _("Sie können hier Ihre Veranstaltung in mehreren Kategorien anpassen. Informationen wie Grunddaten oder Termine und Einstellungen  Zugangsbeschränkungen und Funktionen können Sie hier administrieren."),
            'icon' => 'ausruf_small.gif'
         )
     )
);

?>

<div style="padding-left:0.5em; background-color: white; width: 100%">
  <h2 class="smashbox_kategorie"><?=_("Grundeinstellungen");?></h2>

  <div class="smashbox_stripe">
	  <div style="margin-left: 1.5em;">

  		<a class="click_me" href="<?= UrlHelper::getLink('admin_seminare1.php?section=details') ?>">
		  <div>
			  <span class="click_head"><?=_("Grunddaten");?></span>
			  <p><?=_("Prüfen und Bearbeiten Sie in diesem Verwaltungsbereich die Grundeinstellungen dieser Veranstaltung.");?></p>
		  </div>
		  </a>

			 <a class="click_me" href="<?= UrlHelper::getLink('raumzeit.php?section=dates') ?>">
		  <div>
			  <span class="click_head"><?=_("Zeit und Ort");?></span>
			  <p><?=_("Verändern Sie hier Angaben über regelmäßige Veranstaltungszeiten, Einzeltermine und Ortsangaben.");?></p>
		  </div>
		  </a>

			 <a class="click_me" href="<?= $controller->url_for('course/management/index/studycourse') ?>">
		  <div>
			  <span class="click_head"><?=_("Studienbereiche");?></span>
			  <p><?=_("Legen Sie hier fest, in welchen Studienbereichen diese Veranstaltung im Verzeichnis aller Veranstaltungen erscheint.");?></p>
		  </div>
		  </a>

		  <a class="click_me" href="<?= UrlHelper::getLink('admin_admission.php?section=admission') ?>">
		  <div>
			  <span class="click_head"><?=_("Zugangseinstellungen");?></span>
			  <p><?=_("Richten Sie hier verschiedene Zugangsbeschränkungen, Anmeldeverfahren oder einen Passwortschutz für Ihre Veranstaltung ein.");?></p>
		  </div>
		  </a>

			<a class="click_me" href="<?= UrlHelper::getLink('admin_modules.php?section=modules') ?>">
			<div>
			  <span class="click_head"><?=_("Inhaltselemente");?></span>
			  <p><?=_("Sie können mit dieser Funktionen bestimmte Inhalte wie etwa Forum, Dateibereich oder Wiki ein- oder ausschalten und weitere Inhalte aktivieren.");?></p>
			  </div>
			 </a>


  	</div>
		 <br style="clear: both;"/>
  </div>

  <h2 class="smashbox_kategorie"><?=_("Weitere Inhaltselemente");?></h2>

  <div class="smashbox_stripe">
	  <div style="margin-left: 1.5em;">

		  <a class="click_me" href="<?= UrlHelper::getLink('admin_news.php?section=news') ?>">
		  <div>
			  <span class="click_head"><?=_("Ankündigungen");?></span>
			  <p><?=_("Erstellen Sie Ankündigungen (News) für Ihre Veranstaltung und bearbeiten Sie laufende Ankündigungen.");?></p>
			  </div>
		  </a>

          <? if (get_config('VOTE_ENABLE')) : ?>
		  <a class="click_me" href="<?= UrlHelper::getLink('admin_vote.php?section=votings') ?>">
		  <div>
			  <span class="click_head"><?=_("Umfragen und Tests");?></span>
			  <p><?=_("Erstellen Sie für Ihre Veranstaltung einfachen Umfragen und Tests.");?></p>
			  </div>
		  </a>

			<a class="click_me" href="<?= UrlHelper::getLink('admin_evaluation.php?section=evaluation') ?>">
			 <div>
			  <span class="click_head"><?=_("Evaluationen");?></span>
			  <p><?=_("Richten Sie für Ihre Veranstaltung fragebogenbasierte Umfragen und Lehr-Evaluationen ein.");?></p>
		  </div>
		  </a>
          <? endif ?>


		</div>
		<br style="clear: both;"/>
	</div>
</div>
