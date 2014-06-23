<p>
    <strong><?= _("Öffentlich") ?></strong> - <?= _("jeder darf diesen Blubber sehen.") ?>
</p>
<hr>
<? $i_shared = false ?>
<?= _("Folgende Personen haben diesen Blubber geschrieben bzw. weitergesagt:") ?>
<ul class="blubber_contacts">
    <li>
        <? $author = $thread->getUser() ?>
        <? if ($author['user_id'] === $GLOBALS['user']->id) $i_shared = true ?>
        <a href="<?= URLHelper::getLink($author->getURL()) ?>">
            <?= $author->getAvatar()->getImageTag(Avatar::MEDIUM, array('title' => $author->getName())) ?>
        </a>
    </li>
    <? foreach ($thread->getSharingUsers() as $user) : ?>
    <? if ($user['user_id'] === $GLOBALS['user']->id) $i_shared = true ?>
    <li>
        <a href="<?= URLHelper::getLink($user->getURL()) ?>">
            <?= $user->getAvatar()->getImageTag(Avatar::MEDIUM, array('title' => $user->getName())) ?>
        </a>
    </li>
    <? endforeach ?>
    <? if (!$i_shared) : ?>
    <li class="want_to_share" data-thread_id="<?= htmlReady($thread->getId()) ?>">
        <?= Assets::img("icons/24/blue/add", array('title' => _("Weitersagen"))) ?>
    </li>
    <? endif ?>
</ul>
<? if (!$i_shared) : ?>
<p>
    <?= _("Klicken Sie auf das Plus, um den Blubber weiterzusagen.") ?>
</p>
<? endif ?>
<br><br>