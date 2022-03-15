<?php

return [
    'frontend' => [
        'allplan/allplan-ke-search-extended/ajax-faq-select-box' => [
            'target' => \Allplan\AllplanKeSearchExtended\Middleware\AjaxFaqSelectBox::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ],
        ],
    ],
];