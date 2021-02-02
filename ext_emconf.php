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
    'version' => '10.4.1',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
            'ke_search' => '3.0.0-3.2.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
	'_md5_values_when_last_written' => '',
];
