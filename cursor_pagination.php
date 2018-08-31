<?php

interface ItemRegistryInterface
{
    public function getAfter(int $itemId, int $limit): array;
    public function getBefore(int $itemId, int $limit): array;
}

class TestItemRegistry implements ItemRegistryInterface
{
    /**
     * @var PDO
     */
    private $pdo;

    public function __construct(array $items)
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $this->pdo = new PDO('sqlite::memory:', null, null, $options);
        $this->pdo->exec('CREATE TABLE items (id INTEGER, value TEXT)');
        $query = 'INSERT INTO items (id, value) VALUES (:id, :value)';
        $sth = $this->pdo->prepare($query);
        foreach ($items as $item) {
            $sth->execute($item);
        }
    }

    public function getAfter(int $itemId, int $limit): array
    {
        $limit = $limit > 0 ? $limit : 1;
        $query = 'SELECT * FROM items WHERE id <= :id ORDER BY id LIMIT 1';
        $sth = $this->pdo->prepare($query);
        $sth->execute(['id' => $itemId]);
        $items = $sth->fetchAll();
        $query = 'SELECT * FROM items WHERE id > :id ORDER BY id LIMIT ' . ($limit + 1);
        $sth = $this->pdo->prepare($query);
        $sth->execute(['id' => $itemId]);
        $items = array_merge($items, $sth->fetchAll());
        return array_map(function (array $item) {$item['id'] = (int)$item['id']; return $item;}, $items);
    }

    public function getBefore(int $itemId, int $limit): array
    {
        $limit = $limit > 0 ? $limit : 1;
        $query = 'SELECT * FROM items WHERE id < :id ORDER BY id DESC LIMIT ' . ($limit + 1);
        $sth = $this->pdo->prepare($query);
        $sth->execute(['id' => $itemId]);
        $items = $sth->fetchAll();
        sort($items);
        $query = 'SELECT * FROM items WHERE id >= :id ORDER BY id LIMIT 1';
        $sth = $this->pdo->prepare($query);
        $sth->execute(['id' => $itemId]);
        $items = array_merge($items, $sth->fetchAll());
        return array_map(function (array $item) {$item['id'] = (int)$item['id']; return $item;}, $items);
    }
}

class CursorPagination
{
    /**
     * @var ItemRegistryInterface
     */
    private $registry;

    /**
     * @var int
     */
    private $itemsPerPage;

    public function __construct(ItemRegistryInterface $registry, int $itemsPerPage)
    {
        $this->registry = $registry;
        $this->itemsPerPage = $itemsPerPage > 0 ? (int)$itemsPerPage : 1;
    }

    public function getAfter(int $itemId)
    {
        $items = $this->registry->getAfter($itemId, $this->itemsPerPage);
        $result = [
            'has_prev' => false,
            'items' => [],
            'has_next' => false,
        ];
        if (empty($items)) {
            return $result;
        }
        if ($items[0]['id'] <= $itemId) {
            array_shift($items);
            $result['has_prev'] = true;
        }
        if (count($items) > $this->itemsPerPage) {
            array_pop($items);
            $result['has_next'] = true;
        }
        $result['items'] = $items;
        return $result;
    }

    public function getBefore(int $itemId)
    {
        $items = $this->registry->getBefore($itemId, $this->itemsPerPage);
        $result = [
            'has_prev' => false,
            'items' => [],
            'has_next' => false,
        ];
        if (empty($items)) {
            return $result;
        }
        $last = end($items);
        reset($items);
        if ($last['id'] >= $itemId) {
            array_pop($items);
            $result['has_next'] = true;
        }
        if (count($items) > $this->itemsPerPage) {
            array_shift($items);
            $result['has_prev'] = true;
        }
        $result['items'] = $items;
        return $result;
    }
}

$items = [
    ['id' => 1, 'value' => 'i1'],
    ['id' => 2, 'value' => 'i2'],
    ['id' => 3, 'value' => 'i3'],
    ['id' => 4, 'value' => 'i4'],
    ['id' => 5, 'value' => 'i5'],
];
$itemsPerPage = 3;
$cursor = new CursorPagination(new TestItemRegistry($items), $itemsPerPage);

const NORMAL="\e[0m";
const RED="\e[1;31m";
const GREEN="\e[1;32m";

$tests = [
    [0, ['has_prev' => false, 'items' => array_slice($items, 0, $itemsPerPage), 'has_next' => true]],
    [1, ['has_prev' => true, 'items' => array_slice($items, 1, $itemsPerPage), 'has_next' => true]],
    [2, ['has_prev' => true, 'items' => array_slice($items, 2, $itemsPerPage), 'has_next' => false]],
    [3, ['has_prev' => true, 'items' => array_slice($items, 3, $itemsPerPage), 'has_next' => false]],
    [4, ['has_prev' => true, 'items' => array_slice($items, 4, $itemsPerPage), 'has_next' => false]],
    [5, ['has_prev' => true, 'items' => [], 'has_next' => false]],
];
foreach ($tests as $test) {
    [$itemId, $expected] = $test;
    $actual = $cursor->getAfter($itemId);
    echo "Test getAfter($itemId)\t";
    if ($expected !== $actual) {
        echo RED . 'Failure' . NORMAL
            . "\ngetAfter test failed for itemId " . $itemId
            . "\nexpected: " . var_export($expected, true)
            . "\nactual : " . var_export($actual, true)
            . "\n\n";
        exit;
    }
    echo GREEN . 'Success' . NORMAL . "\n";
}
echo "\n";
$tests = [
    [0, ['has_prev' => false, 'items' => [], 'has_next' => true]],
    [1, ['has_prev' => false, 'items' => [], 'has_next' => true]],
    [2, ['has_prev' => false, 'items' => array_slice($items, 0, 1), 'has_next' => true]],
    [3, ['has_prev' => false, 'items' => array_slice($items, 0, 2), 'has_next' => true]],
    [4, ['has_prev' => false, 'items' => array_slice($items, 0, $itemsPerPage), 'has_next' => true]],
    [5, ['has_prev' => true, 'items' => array_slice($items, 1, $itemsPerPage), 'has_next' => true]],
    [6, ['has_prev' => true, 'items' => array_slice($items, 2, $itemsPerPage), 'has_next' => false]],
];
foreach ($tests as $test) {
    [$itemId, $expected] = $test;
    $actual = $cursor->getBefore($itemId);
    echo "Test getBefore($itemId)\t";
    if ($expected !== $actual) {
        echo RED . 'Failure' . NORMAL
            . "\ngetBefore test failed for itemId " . $itemId
            . "\nexpected: " . var_export($expected, true)
            . "\nactual : " . var_export($actual, true)
            . "\n\n";
        exit;
    }
    echo GREEN . 'Success' . NORMAL . "\n";
}
