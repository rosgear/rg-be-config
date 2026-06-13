<?php
/**
 * Этот файл является частью модуля веб-приложения RosGear.
 * 
 * Файл конфигурации установки модуля.
 * 
 * @link https://rosgear.ru/
 * @copyright Copyright (c) 2015 RosGear
 * @license https://rosgear.ru/license/
 */

return [
    'use'         => BACKEND,
    'id'          => 'rg.be.config',
    'name'        => 'Configuration',
    'description' => 'System configuration',
    'expandable'  => true,
    'namespace'   => 'Rg\Backend\Config',
    'path'        => '/rg/rg.be.config',
    'route'       => 'config',
    'routes'      => [
        [
            'type'    => 'extensions',
            'options' => [
                'module'      => 'rg.be.config',
                'route'       => 'config[/:extension[/:controller[/:action[/:id]]]]',
                'prefix'      => BACKEND,
                'constraints' => [
                    'id' => '[A-Za-z0-9_-]+'
                ],
                'redirect' => [
                    'info:*@*' => ['info', '*', null]
                ]
            ]
        ]
    ],
    'locales'     => ['ru_RU', 'en_GB'],
    'permissions' => ['any', 'extension', 'info'],
    'events'      => [],
    'required'    => [
        ['php', 'version' => '8.2'],
        ['app', 'code' => 'RG Workspace'],
        ['app', 'code' => 'RG CMS'],
        ['app', 'code' => 'RG CRM']
    ]
];
