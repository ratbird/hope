<?
# Lifter010: TODO
?>
<table class="default">
    <tr>
        <th width="50%"><?= _('Hauptnavigation') ?></th>
        <th width="50%"><?= _('Zusatznavigation') ?></th>
    </tr>
    <tr class="table_row_even">
        <td valign="top">
            <?= $this->render_partial('sitemap/navigation',
                    array('navigation' => $navigation, 'needs_image' => true, 'style' => 'bold')) ?>
        </td>
        <td valign="top">
            <?= $this->render_partial('sitemap/navigation',
                    array('navigation' => $quicklinks, 'needs_image' => false, 'style' => 'bold')) ?>
            <table class="default">
                <tr>
                    <th><?= _('Fu�zeile') ?></th>
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
                'text' => _('Auf dieser Seite finden Sie eine �bersicht �ber alle verf�gbaren Seiten.')
            )
        )
    )
);

$infobox = array('picture' => 'sidebar/admin-sidebar.png', 'content' => $infobox_content);
?>
