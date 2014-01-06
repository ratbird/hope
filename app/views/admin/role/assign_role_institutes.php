<? foreach(PageLayout::getMessages() as $pm) : ?>
    <?= $pm ?>
<? endforeach; ?>
<h4><?= sprintf(_("Einrichtungszuordnung f�r %s in der Rolle %s"), htmlReady($user->getFullname()), htmlready($role->getRoleName()))?></h4>
<form action="<?= $controller->link_for('/assign_role_institutes/' . $role->getRoleid() . '/' . $user->id) ?>" method="post">
<?= $qsearch->render() ?>
<?= Studip\Button::create(_('Einrichtung hinzuf�gen'), "add_institute", array("rel" => "lightbox")) ?>
</form>
<h4><?= _("Vorhandene Zuordnungen") ?></h4>
<ul>
<? foreach ($institutes as $institute): ?>
    <li>
          <?= htmlReady($institute->name) ?>
          <a href="<?= $controller->link_for('/assign_role_institutes/' . $role->getRoleid() . '/' . $user->id, array('remove_institute' => $institute->id)) ?>" rel="lightbox">
          <?= Assets::img('icons/16/blue/trash.png') ?>
          </a>
    </li>
<? endforeach ?>
</ul>
<?=Studip\LinkButton::create(_('Abbrechen'), $controller->url_for('/assign_role/' . $user->id), array('rel' => 'close')) ?>
