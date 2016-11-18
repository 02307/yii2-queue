DB драйвер
==========

DB дравер для хранения очереди заданий использует базу данных.

Пример настройки:

```php
return [
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\db\Driver::class,
                'db' => 'db', // ID подключения
                'tableName' => '{{%queue}}', // таблица
                'mutex' => \yii\mutex\MysqlMutex::class, // мьютекс для синхронизации запросов
            ],
        ],
    ],
];
```

В базу данных нужно добавить таблицу. Схема, на примере MySQL:

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

Эта команда в цикле извлекает задания из очереди и выполняет их, пока очередь не опустеет, и
завершает свою работу. Это способ подойдет для обработки очереди заданий через cron.

```bash
yii queue/run-loop [delay]
```

Команда `run-loop` запускает обработку очереди в режиме демона. Очередь опрашивается непрерывно.
Если добавляются новые задания, то они сразу же извлекаются и выполняются. `delay` - время ожидания
в секундах перед следующим опросом очереди. Способ наиболее эфективен если запускать команду через
демон-супервизор, например `supervisord`.

```bash
yii queue/run-one
```

Эта команда выполняет самую первую задачу в очереди. Можно использовать в процессе разработки и
отладки.

```bash
yii queue/purge
```

Команда `purge` чистит очередь.
