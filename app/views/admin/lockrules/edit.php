<?
# Lifter010: TODO
?>
<h3><?=sprintf("Sperrebene \"%s\" ändern", htmlready($lock_rule["name"]))?></h3>
<?
echo $message;
echo $this->render_partial('admin/lockrules/_form.php', array('action' => $this->controller->url_for('admin/lockrules/edit/' . $lock_rule->getId())));

$infobox_content = array(
           array(
               'kategorie' => _('Sperrebenen verwalten'),
               'eintrag'   => array(array(
               'icon' => 'icons/16/black/trash.png',
               'text' => '<a href="'.$controller->url_for('admin/lockrules/delete/' . $lock_rule->getid()).'">'._("Diese Ebene löschen").'</a>'
            ),
            array(
                'icon' => 'icons/16/black/minus.png',
                'text' => '<a href="'.$controller->url_for('admin/lockrules').'">'._('Bearbeiten abbrechen').'</a>'
                ))
            ), array(
                'kategorie' => _('Informationen'),
                'eintrag'   => array(array(
                    'icon' => 'icons/16/black/info.png',
                    'text' => sprintf(_("Diese Sperrebene wird von %s Objekten benutzt."), $lock_rule->getUsage())
                ))
            )
);

$infobox = array('picture' => 'infobox/administration.png', 'content' => $infobox_content);