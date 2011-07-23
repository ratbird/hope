<?
# Lifter010: TODO
?>
<table class="default">
    <tr>
        <th width="50%"><?= _('Hauptnavigation') ?></th>
        <th width="50%"><?= _('Zusatznavigation') ?></th>
    </tr>
    <tr class="steel1">
        <td valign="top">
            <?= $this->render_partial('sitemap/navigation',
                    array('navigation' => $navigation, 'needs_image' => true, 'style' => 'bold')) ?>
        </td>
        <td valign="top">
            <?= $this->render_partial('sitemap/navigation',
                    array('navigation' => $quicklinks, 'needs_image' => false, 'style' => 'bold')) ?>
            <table class="default">
                <tr>
                    <th><?= _('Fußzeile') ?></th>
                </tr>
                <tr>
                    <td>
                        <?= $this->render_partial('sitemap/navigation',
                                array('navigation' => $footer, 'needs_image' => false, 'style' => 'bold')) ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/grey/info-circle.png',
                'text' => _('Auf dieser Seite finden Sie eine Übersicht über alle verfügbaren Seiten.')
            )
        )
    )
);

$infobox = array('picture' => 'infobox/administration.png', 'content' => $infobox_content);
?>
