# Лабораторная работа №8 : Непрерывная интеграция с помощью Github Actions

**Питропов Александр,группа I2302**  
**Дата выполнения:14.04.2025** 

## Цель работы

В рамках данной работы студенты научатся настраивать непрерывную интеграцию с помощью Github Actions.

## Задание

Создать Web-приложение, написать тесты для него и настроить непрерывную интеграцию с помощью Github Actions на базе контейнеров.

## Описание выполнения работы

### Шаг 1. Подготовка репозитория

Создан репозиторий `containers08`, склонирован на локальный компьютер.

### Шаг 2. Создание структуры проекта

Создана директория `./site` со следующей структурой:

```
site
├── modules/
│   ├── database.php
│   └── page.php
├── templates/
│   └── index.tpl
├── styles/
│   └── style.css
├── config.php
└── index.php
```

### Шаг 3. Реализация backend

#### `modules/database.php`

Содержит класс `Database` с методами:

- `__construct($path)`
- `Execute($sql)`
- `Fetch($sql)`
- `Create($table, $data)`
- `Read($table, $id)`
- `Update($table, $id, $data)`
- `Delete($table, $id)`
- `Count($table)`

```php

<?php

class Database {
    private $pdo;

    public function __construct($path) {
        $this->pdo = new PDO("sqlite:" . $path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function Execute($sql) {
        return $this->pdo->exec($sql);
    }

    public function Fetch($sql) {
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function Create($table, $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_map(function ($key) {
            return ":" . $key;
        }, array_keys($data)));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return $this->pdo->lastInsertId();
    }

    public function Read($table, $id) {
        $sql = "SELECT * FROM {$table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function Update($table, $id, $data) {
        $setPart = implode(", ", array_map(function ($key) {
            return "{$key} = :{$key}";
        }, array_keys($data)));

        $sql = "UPDATE {$table} SET {$setPart} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function Delete($table, $id) {
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function Count($table) {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}

```

#### `modules/page.php`

Содержит класс `Page` с методами:

- `__construct($template)`
- `Render($data)`

```php
<?php

class Page {
    private $template;

    public function __construct($template) {
        $this->template = $template;
    }

    public function Render($data) {
        $output = file_get_contents($this->template);
        
        foreach ($data as $key => $value) {
            $output = str_replace("{{" . $key . "}}", htmlspecialchars($value), $output);
        }

        return $output;
    }
}
```

#### `templates/index.tpl`

HTML-шаблон страницы с переменными для подстановки.

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{title}}</title>
    <link rel="stylesheet" href="/styles/style.css">
</head>
<body>
    <h1>{{title}}</h1>
    <div class="content">
        {{content}}
    </div>
</body>
</html>
```

#### `styles/style.css`

CSS-стили для страницы.

#### `config.php`

Конфигурационный файл с путём к базе данных:

```php
<?php
$config = [
    "db" => [
        "path" => __DIR__ . "/../db/db.sqlite"
    ]
];
```

#### `index.php`

Основной файл приложения:

```php
<?php
require_once __DIR__ . '/modules/database.php';
require_once __DIR__ . '/modules/page.php';
require_once __DIR__ . '/config.php';

$db = new Database($config["db"]["path"]);
$page = new Page(__DIR__ . '/templates/index.tpl');
$pageId = $_GET['page'];
$data = $db->Read("page", $pageId);
echo $page->Render($data);
```

### Шаг 4. SQL-структура

Создан файл `./sql/schema.sql`:

```sql
CREATE TABLE page (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT
);

INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1');
INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2');
INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3');
```

### Шаг 5. Создание тестов

Создан каталог `./tests` с файлами `testframework.php` и `tests.php`.

#### `testframework.php`

Фреймворк для тестирования. Содержит функции `info`, `error`, `assertExpression` и класс `TestFramework`.

```php
<?php

function message($type, $message) {
    $time = date('Y-m-d H:i:s');
    echo "{$time} [{$type}] {$message}" . PHP_EOL;
}

function info($message) {
    message('INFO', $message);
}

function error($message) {
    message('ERROR', $message);
}

function assertExpression($expression, $pass = 'Pass', $fail = 'Fail'): bool {
    if ($expression) {
        info($pass);
        return true;
    }
    error($fail);
    return false;
}

class TestFramework {
    private $tests = [];
    private $success = 0;

    public function add($name, $test) {
        $this->tests[$name] = $test;
    }

    public function run() {
        foreach ($this->tests as $name => $test) {
            info("Running test {$name}");
            if ($test()) {
                $this->success++;
            }
            info("End test {$name}");
        }
    }

    public function getResult() {
        return "{$this->success} / " . count($this->tests);
    }
}
```
#### `tests.php`

Регистрирует и запускает тесты всех методов классов `Database` и `Page`.

```php
<?php

require_once __DIR__ . '/testframework.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$testFramework = new TestFramework();

function testDbConnection() { ... }
function testDbCount() { ... }
function testDbCreate() { ... }
function testDbRead() { ... }
function testDbUpdate() { ... }
function testDbDelete() { ... }
function testPageRender() { ... }

$testFramework->add('Database connection', 'testDbConnection');
$testFramework->add('Database count', 'testDbCount');
$testFramework->add('Database create', 'testDbCreate');
$testFramework->add('Database read', 'testDbRead');
$testFramework->add('Database update', 'testDbUpdate');
$testFramework->add('Database delete', 'testDbDelete');
$testFramework->add('Page render', 'testPageRender');

$testFramework->run();
echo $testFramework->getResult();
```

### Шаг 6. Dockerfile

Создан файл `Dockerfile`:

```Dockerfile
FROM php:7.4-fpm as base

RUN apt-get update && \
    apt-get install -y sqlite3 libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

VOLUME ["/var/www/db"]

COPY sql/schema.sql /var/www/db/schema.sql

RUN echo "prepare database" && \
    cat /var/www/db/schema.sql | sqlite3 /var/www/db/db.sqlite && \
    chmod 777 /var/www/db/db.sqlite && \
    rm -rf /var/www/db/schema.sql && \
    echo "database is ready"

COPY site /var/www/html
```

### Шаг 7. Github Actions

Создан файл `.github/workflows/main.yml`:

```yaml
name: CI

on:
  push:
    branches:
      - main

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
      - name: Build the Docker image
        run: docker build -t containers08 .
      - name: Create `container`
        run: docker create --name container --volume database:/var/www/db containers08
      - name: Copy tests to the container
        run: docker cp ./tests container:/var/www/html
      - name: Up the container
        run: docker start container
      - name: Run tests
        run: docker exec container php /var/www/html/tests/tests.php
      - name: Stop the container
        run: docker stop container
      - name: Remove the container
        run: docker rm container
```

### Шаг 8. Проверка CI

Коммит изменений и push в репозиторий. Переход во вкладку Actions, где отображаются успешно пройденные тесты.

![image](https://i.imgur.com/tInp08j.png)

## Ответы на вопросы

**Что такое непрерывная интеграция?**
Непрерывная интеграция (Continuous Integration) — это практика регулярной интеграции изменений кода в общий репозиторий с последующей автоматической сборкой и тестированием.

**Для чего нужны юнит-тесты? Как часто их нужно запускать?**
Юнит-тесты проверяют корректность отдельных компонентов приложения. Их нужно запускать при каждом изменении кода и при любом коммите в репозиторий.

**Что нужно изменить в `.github/workflows/main.yml`, чтобы тесты запускались при Pull Request?**

```yaml
on:
  pull_request:
    branches:
      - main
```

**Что нужно добавить в `.github/workflows/main.yml`, чтобы удалять созданные образы после выполнения тестов?**

```yaml
      - name: Remove Docker image
        run: docker rmi containers08 || true
```

## Вывод

В ходе выполнения лабораторной работы было разработано простое веб-приложение с использованием PHP и SQLite. Написаны юнит-тесты для основных модулей, а также реализован процесс непрерывной интеграции с помощью GitHub Actions и Docker. Все тесты успешно прошли в процессе CI, что подтверждает корректность работы приложения и настройку автоматического тестирования. Полученные навыки позволяют автоматизировать разработку и повысить надежность программного продукта.
