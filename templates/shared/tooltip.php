<span class="tooltip <? if ($important) echo 'tooltip-important'; ?>" data-tooltip <? if (!$html) printf('title="%s"', htmlReady($text, true, true)) ?>>
<? if ($html): ?>
    <span class="tooltip-content"><?= $text ?></span>
<? endif; ?>
</span>
