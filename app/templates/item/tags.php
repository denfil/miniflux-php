<div class="item-tags" data-item-id="<?php echo $item_id ?>">
    <span class="tags-pseudo-link tags-link-edit"><?php echo t('Edit tags') ?></span>
    <div class="tag-list">
        <ul>
            <?php foreach ($tags as $tag): ?>
            <li><a href="?action=search-tag&amp;tag_id=<?php echo $tag['id'] ?>" data-tag-id="<?php echo $tag['id'] ?>"><?php echo $tag['title'] ?></a></li>
            <?php endforeach ?>
        </ul>
    </div>
    <div class="item-tag-add">
        <input type="text" placeholder="<?php echo t('Press Enter to save.') ?>">
        <span class="tags-pseudo-link tags-link-cancel"><?php echo t('cancel') ?> (Esc)</span>
    </div>
</div>