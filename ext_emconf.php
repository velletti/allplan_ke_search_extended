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
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-8.7.99',
            'ke_search' => '2.0.0-2.9.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
	'_md5_values_when_last_written' => '',
];
