<?php

$EM_CONF['allplan_ke_search_extended'] = [
    'title' => 'Allplan: ke_search extended',
    'description' => 'Indexers for ke_search',
    'category' => 'plugin',
	'author' => 'Allplan Webteam',
	'author_email' => 'web-admin@allplan.com',
	'author_company' => 'Allplan GmbH',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '11.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
            'ke_search' => '4.0.0-4.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
