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
