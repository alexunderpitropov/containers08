<?php

require_once __DIR__ . '/testframework.php';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$testFramework = new TestFramework();

// test 1: check database connection
function testDbConnection() {
    global $config;
    try {
        $db = new Database($config["db"]["path"]);
        assertExpression($db instanceof Database, 'Pass', 'Database connection failed');
    } catch (Exception $e) {
        error('Database connection error: ' . $e->getMessage());
        return false;
    }
    return true;
}

// test 2: test count method
function testDbCount() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $count = $db->Count("page");
    return assertExpression($count > 0, 'Pass', 'Count method failed');
}

// test 3: test create method
function testDbCreate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = ['title' => 'Test Page', 'content' => 'Test content'];
    $id = $db->Create("page", $data);
    return assertExpression($id > 0, 'Pass', 'Create method failed');
}

// test 4: test read method
function testDbRead() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = $db->Read("page", 1);  // assuming there's an entry with id=1
    return assertExpression(isset($data['title']) && isset($data['content']), 'Pass', 'Read method failed');
}

// test 5: test update method
function testDbUpdate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = ['title' => 'Updated Page', 'content' => 'Updated content'];
    $updateResult = $db->Update("page", 1, $data);
    return assertExpression($updateResult !== false, 'Pass', 'Update method failed');
}

// test 6: test delete method
function testDbDelete() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $deleteResult = $db->Delete("page", 1);
    return assertExpression($deleteResult !== false, 'Pass', 'Delete method failed');
}

// test 7: test page render
function testPageRender() {
    $page = new Page(__DIR__ . '/../templates/index.tpl');
    $data = ['title' => 'Test Title', 'content' => 'Test content'];
    $renderResult = $page->Render($data);
    return assertExpression(strlen($renderResult) > 0, 'Pass', 'Page render failed');
}

// add tests
$testFramework->add('Database connection', 'testDbConnection');
$testFramework->add('Database count', 'testDbCount');
$testFramework->add('Database create', 'testDbCreate');
$testFramework->add('Database read', 'testDbRead');
$testFramework->add('Database update', 'testDbUpdate');
$testFramework->add('Database delete', 'testDbDelete');
$testFramework->add('Page render', 'testPageRender');

// run tests
$testFramework->run();

// print results
echo $testFramework->getResult();
