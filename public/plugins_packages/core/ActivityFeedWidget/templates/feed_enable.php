<? if ($key): ?>
    <a href="<?= PluginEngine::getLink("activityfeed/atom/$user/$key", array('cid' => NULL)) ?>">
        <?= _('Feed abonnieren') ?>
    </a>
    <br>
    (<a href="<?= PluginEngine::getLink('activityfeed/activities', array('enable' => 0)) ?>">
        <?= _('ausschalten') ?>
    </a>)
<? else: ?>
    <a href="<?= PluginEngine::getLink('activityfeed/activities', array('enable' => 1)) ?>">
        <?= _('Feed einschalten') ?>
    </a>
<? endif ?>
