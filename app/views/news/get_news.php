<?
if (!Request::isXhr()) :
    global $template_factory;
    $this->set_layout($template_factory->open('layouts/base_without_infobox'));
    ?>
    <div class="table_header_bold" style="clear:both">
        <div style="float:left">
            <?= Assets::img('icons/16/white/breaking-news.png') ?>
            <span style="padding-left:2px;"><?= htmlReady($news['topic']); ?></span>
        </div>
        <div style="text-align:right">
            <span><?=htmlReady($news['author']);?></span>
            <span style="padding-left:2px;"><?=strftime('%x', $news['chdate'])?></span>
        </div>
    </div>
<? endif ?>
<?= studip_utf8encode($content) ?>

