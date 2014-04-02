<?
# Lifter010: TODO
?>
<h3>
    <?=_("Neue Sperrebene eingeben für den Bereich:")?>
    &nbsp;
    <?=$rule_type_names[$lock_rule_type];?>
</h3>
<?
echo $message;
echo $this->render_partial('admin/lockrules/_form.php', array('action' => $this->controller->url_for('admin/lockrules/new')));

$infobox_content = array(
           array(
               'kategorie' => _('Sperrebenen verwalten'),
               'eintrag'   => array(
                array(
                'icon' => 'icons/16/black/remove.png',
                'text' => '<a href="'.$controller->url_for('admin/lockrules').'">'._('Bearbeiten abbrechen').'</a>'
                ))
            )
);

$infobox = array('picture' => 'sidebar/lock-sidebar.png', 'content' => $infobox_content);