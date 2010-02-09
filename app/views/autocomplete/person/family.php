<? require_once 'lib/classes/Avatar.class.php' ?>
<ul>
  <? foreach ($persons as $person) : ?>
    <li><span class="informal">

        <?= Avatar::getAvatar($person['user_id'])->getImageTag(Avatar::SMALL) ?>
        <?= htmlReady($person['title_front']) ?>
        <?= htmlReady($person['Vorname']) ?>

      </span><?= htmlReady($person['Nachname']) ?><span class="informal">

        <?= htmlReady($person['title_rear']) ?>

        <span class="username"><?= htmlReady($person['username']) ?></span>
        <span class="permission"><?= htmlReady($person['perms']) ?></span>

      </span></li>
  <? endforeach ?>
</ul>
