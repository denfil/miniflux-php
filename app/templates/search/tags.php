<?php echo Miniflux\Template\load('common/search', array('opened' => true, 'text' => '')) ?>

<?php if (empty($items)): ?>
    <p class="alert alert-info"><?php echo t('There are no results for your search') ?></p>
<?php else: ?>
    <div class="page-header">
        <h2><?php echo $tag_title ?><span id="page-counter"><?php echo isset($nb_items) ? $nb_items : '' ?></span></h2>
        <ul>
            <?php foreach ($tags as $tag): ?>
            <li><a href="?action=search-tag&amp;tag_id=<?php echo $tag['id'] ?>"><?php echo $tag['title'] ?></a></li>
            <?php endforeach ?>
        </ul>
    </div>

    <section class="items" id="listing">
        <?php foreach ($items as $item): ?>
            <?php echo Miniflux\Template\load('items/item', array(
                'item' => $item,
                'menu' => $item['bookmark'] ? 'bookmarks' : ($item['status'] == 'unread' ? 'unread' : 'history'),
                'offset' => $offset,
                'hide' => false,
                'display_mode' => $display_mode,
                'item_title_link' => $item_title_link,
                'favicons' => $favicons,
                'original_marks_read' => $original_marks_read,
            )) ?>
        <?php endforeach ?>

        <?php echo Miniflux\Template\load('items/paging', array('menu' => $menu, 'nb_items' => $nb_items, 'items_per_page' => $items_per_page, 'offset' => $offset, 'tag_id' => $tag_id)) ?>
    </section>

<?php endif ?>
