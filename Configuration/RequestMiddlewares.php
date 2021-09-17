<?php

return [
    'frontend' => [
        'allplan/allplankesearchextended/ajax' => [
            'target' => \Allplan\AllplanKeSearchExtended\Middleware\Ajax::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ],
        ],
    ],
];