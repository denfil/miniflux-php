<?php

use Miniflux\Session\SessionManager;
use Miniflux\Session\SessionStorage;
use Miniflux\Response;
use Miniflux\Model\Feed;
use Miniflux\Model\Item;
use PicoDb\Database;

require_once __DIR__ . '/app/common.php';

SessionManager::open(BASE_URL_DIRECTORY, SESSION_SAVE_PATH, 0);
if (! SessionStorage::getInstance()->isLogged() || ! SessionStorage::getInstance()->isAdmin()) {
    Response\redirect('index.php?action=login');
}

$userId = SessionStorage::getInstance()->getUserId();

class Bookmarks {
    private $database;

    private $feeds;

    private $error = '';

    private $userId;

    public function __construct($userId) {
        $this->userId = $userId > 0 ? (int)$userId : 1;
    }

    public function getDefault() {
        return [
            'user_id' => $this->userId,
            'feed_id' => 0,
            'checksum' => '',
            'status' => Item\STATUS_UNREAD,
            'bookmark' => 1,
            'url' => '',
            'title' => '',
            'author' => '',
            'content' => '',
            'updated' => time(),
            'enclosure_url' => '',
            'enclosure_type' => '',
            'language' => '',
            'rtl' => 0
        ];
    }

    public function hasBookmarksFeed() {
        $db = $this->getDatabase();
        $found = $db
            ->table(Feed\TABLE)
            ->columns('id')
            ->eq('id', 0)
            ->findOne();
        return isset($found['id']);
    }

    public function createBookmarksFeed() {
        if ($this->hasBookmarksFeed()) {
            return true;
        }
        $db = $this->getDatabase();
        $feed = [
            'id' => 0,
            'user_id' => $this->userId,
            'feed_url' => '',
            'site_url' => '',
            'title' => 'Bookmarks',
            'last_checked' => 0,
            'last_modified' => time(),
            'etag' => '',
            'enabled' => Feed\STATUS_INACTIVE,
            'download_content' => 0,
            'parsing_error' => 0,
            'rtl' => 0,
            'cloak_referrer' => 0,
            'parsing_error_message' => '',
            'expiration' => 0
        ];
        $success = $db->table(Feed\TABLE)->save($feed);
        if ($success !== true) {
            $this->setError('Bookmarks feed creation error.');
            return false;
        }
        return true;
    }

    public function getFeeds($force = false) {
        if ($this->feeds && !$force) {
            return $this->feeds;
        }
        $db = $this->getDatabase();
        $feeds = $db->table(Feed\TABLE)
            ->columns('id', 'title')
            ->findAll();
        $this->feeds = empty($feeds) ? [] : $feeds;
        return $this->feeds;
    }

    public function save(array $data) {
        $this->setError('');
        $item = $this->createItem($data);
        if ($item === false) {
            return false;
        }
        $db = $this->getDatabase();
        $found = $db
            ->table(Item\TABLE)
            ->columns('id', 'title', 'url')
            ->eq('url', $item['url'])
            ->findOne();
        if ($found !== null) {
            $this->setError('It seems that the same item already exists:<br>' . $found['id']
                . ' <a href="' . $found['url'] . '" target="_blank">' . strip_tags($found['title']) . '</a>');
            return false;
        }
        $success = $db->table(Item\TABLE)->save($item);
        if ($success !== true) {
            $this->setError('Item adding error.');
            return false;
        }
        return true;
    }

    public function getError() {
        return $this->error;
    }

    private function setError($error) {
        $this->error = $error;
    }

    private function createItem(array $data) {
        if (empty($data) || !isset($data['feed_id']) || empty($data['url']) || empty($data['title'])) {
            $this->setError('At least required feed, url and title of item.');
            return false;
        }
        $feedId = false;
        $feeds = $this->getFeeds();
        foreach ($feeds as $feed) {
            if ($feed['id'] == $data['feed_id']) {
                $feedId = (int)$data['feed_id'];
            }
        }
        if ($feedId === false) {
            $this->setError('Invalid feed.');
            return false;
        }
        $result = [
            'user_id' => $this->userId,
            'feed_id' => $feedId,
            'checksum' => '',
            'status' => isset($data['status']) ? Item\STATUS_READ : Item\STATUS_UNREAD,
            'bookmark' => isset($data['bookmark']) ? 1 : 0,
            'url' => trim($data['url']),
            'title' => trim($data['title']),
            'author' => empty($data['author']) ? '' : $data['author'],
            'content' => empty($data['content']) ? '' : $data['content'],
            'updated' => empty($data['updated']) ? time() : strtotime($data['updated']),
            'enclosure_url' => '',
            'enclosure_type' => '',
            'language' => empty($data['language']) ? '' : $data['language'],
            'rtl' => 0
        ];
        $result['checksum'] = hash('crc32b', implode([$result['title'], $result['url'], $result['content']]));
        return $result;
    }

    private function getDatabase() {
        if ($this->database === null) {
            $this->database = Database::getInstance('db');
        }
        return $this->database;
    }
}

$model = new Bookmarks($userId);
$message = '';
if (isset($_GET['create_feed'])) {
    $success = $model->createBookmarksFeed();
    $message = $success
        ? '<div class="success">Bookmarks feed created succesfully.</div>'
        : '<div class="error">' . $model->getError() . '</div>';
}
$hasBookmarksFeed = $model->hasBookmarksFeed();

$feeds = $model->getFeeds();
$item = $model->getDefault();
if (isset($_POST['item'])) {
    $success = $model->save($_POST['item']);
    $message = $success
        ? '<div class="success">Item added succesfully.</div>'
        : '<div class="error">' . $model->getError() . '</div>';
    if ($success == false) {
        $item = $_POST['item'];
        $item['status'] = isset($_POST['item']['status']) ? Item\STATUS_READ : Item\STATUS_UNREAD;
        $item['bookmark'] = (int)isset($_POST['item']['bookmark']);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="robots" content="noindex,nofollow">
    <meta name="referrer" content="no-referrer">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Add item</title>

    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="assets/img/touch-icon-iphone.png">
    <link rel="apple-touch-icon" sizes="72x72" href="assets/img/touch-icon-ipad.png">
    <link rel="apple-touch-icon" sizes="114x114" href="assets/img/touch-icon-iphone-retina.png">
    <link rel="apple-touch-icon" sizes="144x144" href="assets/img/touch-icon-ipad-retina.png">
    <style>
    body {font-family:sans-serif;}
    form {margin:50px;}
    form > div {margin-bottom:1em;}
    input[type=text], textarea {width:70%; min-width:15em;}
    textarea {height:7em;}
    input[type=text].short {width:15em;}
    .help-block {color:grey; font-size:81.25%; white-space:nowrap;}
    .nav {margin-bottom:2em;}
    .nav a {margin-right:1em;}
    .success {padding:1em; background-color:#dff0d8;}
    .error {padding:1em; background-color:#f2dede;}
    #lang-list span {color:#05b; border-bottom:1px dotted #05b; margin-left:.5em; cursor:pointer;}
    #lang-list span:first-child {margin-left:0;}
    #lang-list span:hover {color:#b10; border-bottom-color:#b10}
    </style>
</head>
<body>
    <form action="add.php" method="post">
        <div class="nav"><a href="index.php?action=unread">unread</a><a href="index.php?action=bookmarks">bookmarks</a><a href="index.php?action=history">history</a></div>
        <?php echo $message; ?>
        <div>
            Feed <select name="item[feed_id]">
                <?php foreach ($feeds as $feed) { ?>
                <option value="<?php echo $feed['id'] ?>"<?php echo $item['feed_id'] == $feed['id'] ? 'selected="selected"' : ''; ?>><?php echo $feed['title'] ?></option>
                <?php } ?>
            </select>
            <?php if ($hasBookmarksFeed === false) { echo ' &nbsp; <a class="help-block" href="?create_feed=1">Create bookmarks feed</a>'; } ?>
        </div>
        <div><input type="text" name="item[url]" value="<?php echo $item['url']; ?>" placeholder="Url" required="required"></div>
        <div><input type="text" name="item[title]" value="<?php echo $item['title']; ?>" placeholder="Title" required="required"></div>
        <div><input type="text" class="short" name="item[author]" value="<?php echo $item['author']; ?>" placeholder="Author"></div>
        <div><textarea name="item[content]" placeholder="Content"><?php echo $item['content']; ?></textarea></div>
        <div><input type="text" class="short" name="item[updated]" value="<?php echo date('Y-m-d H:i:s', $item['updated']); ?>" placeholder="Updated"></div>
        <div><input id="lang" type="text" class="short" name="item[language]" value="<?php echo $item['language']; ?>" placeholder="Language"><div id="lang-list" class="help-block">
            <span>ru</span>
            <span>ru-RU</span>
            <span>en-US</span>
            <span>en-us</span>
            <span>en</span>
        </div></div>
        <div><label><input type="checkbox" name="item[status]" value="<?php echo Item\STATUS_READ; ?>"<?php echo $item['status'] == Item\STATUS_READ ? ' checked="checked"' : ''; ?>> Read</label></div>
        <div><label><input type="checkbox" name="item[bookmark]" value="1"<?php echo $item['bookmark'] ? ' checked="checked"' : ''; ?>> Bookmark</label></div>
        <div><input type="submit" value="Add item"></div>
    </form>
    <script>
    (function() {
        var lang = document.getElementById("lang"),
            langs = document.querySelectorAll("#lang-list > span");
        for (var i = 0; i < langs.length; i++) {
            langs[i].addEventListener("click", function (e) {
                lang.value = e.target.textContent;
            });
        }
    })();
    </script>
</body>
</html>
