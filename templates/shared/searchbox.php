<div class="search_box" align="center">
        <input name="searchtext" type="text" size="45" style="vertical-align: middle;">
        <input type="image" <?= makeButton('suchestarten','src')?> style="vertical-align: middle;">
         <a href="<?=URLHelper::getLink('',array('action' => 'deny'))?>">
            <?= makeButton('zuruecksetzen', 'img', _('Suche zurücksetzen')) ?>
        </a>
</div>
