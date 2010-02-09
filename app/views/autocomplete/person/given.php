<ul>
  <? foreach ($persons as $person) : ?>
    <li><?= htmlReady($person) ?></li>
  <? endforeach ?>
</ul>
