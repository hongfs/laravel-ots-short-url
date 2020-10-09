![展示图](https://user-images.githubusercontent.com/23376043/95541575-4c4bc080-0a26-11eb-8105-fd32402486cc.png)

<center>基于Laravel+阿里云表格存储的自建短链接生成</center>

---

## 安装

环境要求：

- PHP >= 7.2.5
- BCMath PHP 拓展
- Ctype PHP 拓展
- Fileinfo PHP 拓展
- JSON PHP 拓展
- Mbstring PHP 拓展
- OpenSSL PHP 拓展
- PDO PHP 拓展
- GMP PHP 拓展
- Tokenizer PHP 拓展
- XML PHP 拓展

## 下载

```shell
$ composer create-project hongfs/laravel-ots-short-url:dev-master
```

## 配置

`config/config.php`

```php
<?php

return [
    // 表格存储配置
    'ots' => [
        'EndPoint'          => '<your endpoint>',
        'AccessKeyID'       => '<your access key>',
        'AccessKeySecret'   => '<your access key>',
        // 实例名称
        'InstanceName'      => '<your instance name>',
    ],

    // 数据表名称
    'table_name' => 'data',

    // ID 起始值 pow(62, 3)
    'start_id_value' => 238328,

    // ID 最大自增值
    'inc_max_value' => 5,
];
```

## 初始化

```shell
$ php artisan init
```

## License

MIT
