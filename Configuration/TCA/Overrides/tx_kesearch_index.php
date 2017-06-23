<?php

// Enable the needed field for the indexer-configuration
$GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['sysfolder']['displayCond'] .= ',jv_events,allplan_ce';
$GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['startingpoints_recursive']['displayCond'] .= ',jv_events,allplan_ce';
