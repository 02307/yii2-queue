Yii2 Queue Extension
====================

Расширение для асинхронного выполнения задач через механизм очередей.


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist zhuravljov/yii2-queue
```

or add

```
"zhuravljov/yii2-queue": "*"
```

to the require section of your `composer.json` file.


Конфигурация
------------

Необходимо дополнить конфигурацию вашего приложения следующим образом:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [], // Конфигурация драйвера
        ],
        // ...
    ],
    ...
];
```


Использование
-------------

Пример класса задания:

```php
class DownloadJob extends Object implements \zhuravljov\yii\queue\Job
{
    public $url;
    public $file;
    
    public function run($queue)
    {
        file_put_contents($this->file, file_get_contents($this->url));
    }
}
```

Отправить задание в очередь можно с помощью кода:

```php
Yii::$app->queue->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```

Способ выполнения задач зависит от используемого драйвера.


### Драйвер db\Driver

Для хранения очереди использует базу данных.

Пример настройки:

```php
'driver' => [
    'class' => \zhuravljov\yii\queue\db\Driver::class,
    'db' => 'db', // ID подключения к базе данных,
    'tableName' => '{{%queue}}', // таблица для хранения очереди
    'mutex' => \yii\mutex\MysqlMutex::class, // мьютекс для синхронизации запросов
]
```

Схема таблицы на примере MySQL:

```SQL
CREATE TABLE `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job` text NOT NULL,
  `created_at` int(11) NOT NULL,
  `started_at` int(11) DEFAULT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
```

Для выполнения задач используются консольные команды.

```bash
yii queue/run-all
```

Эта команда в цикле извлекает задания из очереди и выполняет их, пока очередь
не опустеет, и завершает свою работу. Это способ подойдет для обработки очереди
заданий через cron.

```bash
yii queue/run-loop [delay]
```

Команда `run-loop` запускает обработку очереди в режиме демона. Очередь
опрашивается непрерывно. Если добавляются новые задания, то они сразу же
извлекаются и выполняются. `delay` - это время ожидания в секундах перед
следующим опросом очереди. Способ наиболее эфективен если запускать команду
через демон-супервизор, например `supervisord`.

```bash
yii queue/run-one
```

Эта команда выполняет самую первую задачу в очереди. Необходимо для разработки и
отладки.

```bash
yii queue/purge
```

Команда `purge` чистит очередь.


### Драйвер redis\Driver

Для хранения очереди использует Redis.

Для использования этого драйвера необходимо расширение **yiisoft/yii2-redis**.

Пример настройки:

```php
'driver' => [
    'class' => \zhuravljov\yii\queue\redis\Driver::class,
    'redis' => 'redis', // ID подключения к базе данных,
    'keyPrefix' => '',
]
```

Команды в консоль этот драйвер добавляет такие же как и **db\Driver**.


### Драйвер sync\Driver

Драйвер используется для отладки. Добавленные в очередь задачи сразу же
выполняются в том же процессе.

Настройка:

```php
'driver' => \zhuravljov\yii\queue\sync\Driver::class
```

Команды в консоль этот драйвер не добавляет.

Отладочный режим
----------------

Для удобств разработки в отладочный модуль Yii можно добавить панель, которая
будет выводить список поставленных в очередь заданий и их количество.

Настройка:

```php
return [
    'bootstrap' => ['debug', 'queue'],
    'modules' => [
        'debug' => [
            'class' => \yii\debug\Module::class,
            'panels' => [
                'queue' => \zhuravljov\yii\queue\DebugPanel::class,
            ],
        ],
    ],
];
```
