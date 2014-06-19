<table class="default nohover">
    <tbody>
    <? foreach ($topics as $topic) : ?>
        <tr>
            <td>
                <input type="checkbox" id="check_topic_<?= htmlReady($topic->getId()) ?>" name="topic[<?= htmlReady($topic->getId()) ?>]" value="1" checked>
            </td>
            <td>
                <label for="check_topic_<?= htmlReady($topic->getId()) ?>">
                <?= htmlReady($topic['title']) ?>
                </label>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>
