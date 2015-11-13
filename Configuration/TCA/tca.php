<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$TCA['tx_beacl_acl'] = [
    'ctrl' => $TCA['tx_beacl_acl']['ctrl'],
    'interface' => [
        'showRecordFieldList' => 'type,object_id,permissions,recursive'
    ],
    'feInterface' => $TCA['tx_beacl_acl']['feInterface'],
    'columns' => [
        'type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.type',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.type.I.0', '0'],
                    ['LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.type.I.1', '1'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 0,
            ]
        ],
        'object_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.object_id',
            'config' => [
                'type' => 'select',
                'itemsProcFunc' => 'Subugoe\\BeAcl\ObjectSelector->select',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ]
        ],
        'permissions' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.permissions',
            'config' => [
                'type' => 'check',
                'cols' => 5,
                'items' => [
                    ['LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.permissions.I.0', ''],
                    ['LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.permissions.I.1', ''],
                    ['LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.permissions.I.2', ''],
                    ['LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.permissions.I.3', ''],
                    ['LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.permissions.I.4', ''],
                ],
            ]
        ],
        'recursive' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl.recursive',
            'config' => [
                'type' => 'check'
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'type;;;;1-1-1, object_id, permissions, recursive'],
        '1' => ['showitem' => 'type;;;;1-1-1, object_id, permissions, recursive']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
