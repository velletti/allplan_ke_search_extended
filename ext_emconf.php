<?php

$EM_CONF['allplan_ke_search_extended'] = [
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
    'version' => '10.4.2',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
            'ke_search' => '3.2.0-3.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
	'_md5_values_when_last_written' => '',
];
