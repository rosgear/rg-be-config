<?php
/**
 * Этот файл является частью модуля веб-приложения RosGear.
 * 
 * Файл конфигурации модуля.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

return [
    'translator' => [
        'locale'   => 'auto',
        'patterns' => [
            'text' => [
                'basePath' => __DIR__ . '/../lang',
                'pattern'   => 'text-%s.php'
            ]
        ],
        'autoload' => ['text'],
        'external' => [BACKEND]
    ],

    'accessRules' => [
        // для авторизованных пользователей Панели управления
        [ // разрешение "Полный доступ" (any: extension)
            'allow',
            'permission' => 'any',
            'users'      => ['@backend']
        ],
        [ // разрешение "Расширение" (extension)
            'allow',
            'permission' => 'extension',
            'users'      => ['@backend']
        ],
        [ // разрешение "Информация о модуле" (info)
            'allow',
            'permission'  => 'info',
            'controllers' => ['Info'],
            'users'       => ['@backend']
        ],
        [ // для всех остальных, доступа нет
            'deny'
        ]
    ],

    'viewManager' => [
        'id'          => 'rg-configuration-{name}',
        'useTheme'    => true,
        'useLocalize' => true,
        'viewMap'     => [
            // информации о модуле
            'info' => [
                'viewFile'      => '//backend/module-info.phtml', 
                'forceLocalize' => true
            ],
        ]
    ]
];
