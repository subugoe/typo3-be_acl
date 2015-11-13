<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Backend ACLs',
    'description' => 'Backend Access Control Lists',
    'category' => 'be',
    'shy' => 0,
    'version' => '3.0.0',
    'dependencies' => '',
    'conflicts' => '',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 0,
    'lockType' => '',
    'author' => 'Sebastian Kurfuerst',
    'author_email' => 'sebastian@garbage-group.de',
    'author_company' => '',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-5.6.99',
            'typo3' => '7.0.0-7.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
