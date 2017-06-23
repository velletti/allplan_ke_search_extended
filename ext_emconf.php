<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Allplan: ke_search extended',
    'description' => 'Indexers for ke_search',
    'category' => 'plugin',
	'author' => 'Peter Benke',
	'author_email' => 'pbenke@allplan.com',
	'author_company' => 'Allplan GmbH',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-7.6.99',
            'ke_search' => '2.0.0-2.9.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
