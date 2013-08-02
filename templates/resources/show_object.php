<table class="zebra" border="0" celpadding="2" cellspacing="0" width="99%" align="center">
    <colgroup>
        <col width="4%">
        <col width="36%">
        <col width="60%">
    </colgroup>
    <tbody style="vertical-align: top;">
        <tr>
            <td>&nbsp;</td>
            <td>
                <b><?= _('Name:') ?></b><br>
                <?= htmlReady($this->resObject->getName()) ?>
                (<?= htmlReady($this->resObject->getCategoryName()) ?: _('Hierachieebene') ?>)
            </td>
            <td>
                <b><?= _('verantwortlich:') ?></b><br>
            <?  if (Request::option('view_mode') == 'no_nav'): ?>
                <?= htmlReady($this->resObject->getOwnerName(TRUE)) ?>
            <? else: ?>
                <a href="<?= $this->resObject->getOwnerLink() ?>">
                    <?= htmlReady($this->resObject->getOwnerName(TRUE)) ?>
                </a>
            <? endif; ?>
            </td>
            <!-- Infobox -->
            <td class="blank" rowspan="5" valign="top" style="padding-left: 20px" align="right">
            <?
                $content[] = array('kategorie' => _("Raum:"),
                    'eintrag' => array(
                        array(
                            'icon' => 'icons/16/black/info.png',
                            'text' => htmlReady($resObject->getName())
                        )
                    )
                );

                $content[] = array('kategorie' => _("Informationen:"),
                    'eintrag' => array(
                        array(
                            'icon' => 'icons/16/black/info.png',
                            'text' => _("Hier sehen Sie detaillierte Informationen der Ressource.")
                        ),

                        array(
                            'icon' => 'icons/16/black/search.png',
                            'text' => '<a href="'. URLHelper::getLink('resources.php?view=search&quick_view_mode=' . $view_mode) .'">'
                                   . _('zur Ressourcensuche') . '</a>'
                        )
                    )
                );

                $infobox = $GLOBALS['template_factory']->open('infobox/infobox_generic_content.php');
                $infobox->picture = 'infobox/schedules.jpg';
                $infobox->content = $content;

                echo $infobox->render();
            ?>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td colspan="2">
                <b><?= _('Beschreibung:') ?></b><br>
                <?= htmlReady($this->resObject->getDescription()) ?>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        <? if (!$children): ?>
            <td colspan="2">
        <? else: ?>
            <td>
        <? endif; ?>
                <b><?= _('Einordnung:') ?></b><br>
                <?= ResourcesBrowse::getHistory($resObject->getId()) ?>
            </td>
        <? if ($children): ?>
            <td>
                <b><?= _('Untergeordnete Objekte:') ?></b><br>
                <? $list->showListObjects($this->resObject->getId()) ?>
            </td>
        <? endif; ?>
        </tr>
<? if ($this->resObject->getCategoryId()): ?>
        <tr>
            <td>&nbsp;</td>
            <td colspan="2">
                <b><?= _('Eigenschaften:') ?></b>
            </td>
        </tr>
    <? foreach ($properties as $property): ?>
        <tr>
            <td>&nbsp;</td>
            <td>
                &nbsp; &nbsp;
                &bull;
                <?= htmlReady($property['name']); ?>
            </td>
            <td>
            <? if ($property['type'] == 'bool'): ?>
                <?= $property['state'] ? htmlReady($property['options']) : '-' ?>
            <? elseif (in_array($property['type'], words('num text'))): ?>
                <?= htmlReady($property['state']) ?: '-' ?>
            <? elseif ($property['type'] == 'select'): ?>
                <?= in_array($property['state'], explode(';', $property['options'])) ? htmlReady($property['state']) : '-' ?>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach; ?>
<? endif; ?>
    </tbody>
</table>
