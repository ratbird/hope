<ul>
    <? foreach ($subtree->getChildren() as $child) : ?>
        <li>
            <?
                $has_children = $child->hasChildren();
            ?>

            <div class="<?= TextHelper::cycle('odd', 'even') ?>">
                <?= $this->render_partial('course/study_areas/entry',
                                          array('area' => $child,
                                                'show_link' => $has_children)) ?>
            </div>

            <? if ($selection->getShowAll() && $has_children) : ?>
                <?= $this->render_partial('course/study_areas/subtree', array('subtree' => $child)) ?>
            <? endif ?>

        </li>
    <? endforeach ?>
</ul>
