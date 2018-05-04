<?php

namespace Miniflux\Model\Tag;

use PicoDb\Database;

const TABLE = 'tags';
const JOIN_TABLE = 'items_tags';

function get_all($user_id)
{
    return Database::getInstance('db')
        ->table(TABLE)
        ->eq('user_id', $user_id)
        ->orderBy('title')
        ->findAll();
}

function get_tag($user_id, $tag_id)
{
    return Database::getInstance('db')
        ->table(TABLE)
        ->eq('user_id', $user_id)
        ->eq('id', $tag_id)
        ->findOne();
}

function search_tags($user_id, $text)
{
    $tags = Database::getInstance('db')
        ->table(TABLE)
        ->columns('title')
        ->eq('user_id', $user_id)
        ->ilike('title', '%' . $text . '%')
        ->findAll();
    $result = array();
    foreach ($tags as $tag) {
        $result[] = $tag['title'];
    }
    return $result;
}

function get_frequent_tags($user_id, $limit = 10)
{
    $tags = Database::getInstance('db')
        ->table(JOIN_TABLE)
        ->join(TABLE, 'id', 'tag_id')
        ->columns('title', 'COUNT(*) AS qty')
        ->eq('user_id', $user_id)
        ->groupBy('title')
        ->desc('qty')
        ->asc('title')
        ->limit($limit)
        ->findAll();
    $result = array();
    foreach ($tags as $tag) {
        $result[] = $tag['title'];
    }
    return $result;
}

function attach_tags_to_items($user_id, array &$items) {
    $item_ids = array();
    foreach ($items as $item) {
        if (!empty($item['id'])) {
            $item_ids[] = $item['id'];
        }
    }
    if (empty($item_ids)) {
        return;
    }
    $tags = get_items_tags($user_id, $item_ids);
    foreach ($items as &$item) {
        $item_id = $item['id'];
        $item['tags'] = isset($tags[$item_id]) ? $tags[$item_id] : array();
    }
}

function get_items_tags($user_id, array $item_ids)
{
    $rows = Database::getInstance('db')
        ->table(JOIN_TABLE)
        ->columns('item_id', 'tag_id', 'title')
        ->join(TABLE, 'id', 'tag_id')
        ->eq('user_id', $user_id)
        ->in('item_id', $item_ids)
        ->findAll();
    $result = array();
    foreach ($rows as $row) {
        $item_id = $row['item_id'];
        $tag = array(
            'id' => $row['tag_id'],
            'title' => $row['title']
        );
        if (isset($result[$item_id])) {
            $result[$item_id][] = $tag;
        } else {
            $result[$item_id] = array($tag);
        }
    }
    return $result;
}

function get_item_tag_ids($user_id, $item_id)
{
    return Database::getInstance('db')
        ->table(TABLE)
        ->join(JOIN_TABLE, 'tag_id', 'id')
        ->eq('user_id', $user_id)
        ->eq('item_id', $item_id)
        ->findAllByColumn('id');
}

function get_item_tags($user_id, $item_id)
{
    return Database::getInstance('db')
        ->table(TABLE)
        ->columns('tags.id', 'tags.title')
        ->join(JOIN_TABLE, 'tag_id', 'id')
        ->eq('user_id', $user_id)
        ->eq('item_id', $item_id)
        ->findAll();
}

function get_tag_id_from_title($user_id, $title)
{
    return Database::getInstance('db')
        ->table(TABLE)
        ->eq('user_id', $user_id)
        ->eq('title', $title)
        ->findOneColumn('id');
}

function create_tag($user_id, $title)
{
    $tag_id = get_tag_id_from_title($user_id, $title);
    if ($tag_id === false) {
        $tag_id = Database::getInstance('db')
            ->table(TABLE)
            ->persist(array('title' => $title, 'user_id' => $user_id));
    }
    return $tag_id;
}

function add_tag($user_id, $item_id, $tag_title)
{
    $tag_id = create_tag($user_id, $tag_title);
    $item_tag_ids = get_item_tag_ids($user_id, $item_id);
    if (empty($item_tag_ids) || !in_array($tag_id, $item_tag_ids)) {
        $data = array('item_id' => $item_id, 'tag_id' => $tag_id);
        Database::getInstance('db')
            ->table(JOIN_TABLE)
            ->insert($data);
    }
}

function dissociate_item_tag($user_id, $item_id, $tag_id)
{
    $tag = get_tag($user_id, $tag_id);
    if (empty($tag)) {
        return false;
    }
    $result = Database::getInstance('db')
        ->table(JOIN_TABLE)
        ->eq('item_id', $item_id)
        ->eq('tag_id', $tag_id)
        ->remove();
    if ($result) {
        purge_tags();
    }
    return $result;
}

function purge_tags()
{
    $tags = Database::getInstance('db')
        ->table(TABLE)
        ->join(JOIN_TABLE, 'tag_id', 'id')
        ->isNull('item_id')
        ->findAllByColumn('id');
    if (!empty($tags)) {
        Database::getInstance('db')
            ->table(TABLE)
            ->in('id', $tags)
            ->remove();
    }
}