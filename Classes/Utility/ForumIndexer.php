<?php
namespace Allplan\AllplanKeSearchExtended\Utility;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Jörg velletti (allplan) <jVelletti@allplan.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class ForumIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param \tx_kesearch_indexer $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {
        /**
         * @var $db \TYPO3\CMS\Core\Database\DatabaseConnection
         */
        $db = $GLOBALS['TYPO3_DB'];
        // $db->store_lastBuiltQuery = true;

        // Get the data from tx_jvevents_domain_model_event


        // ToDo  Enhence the Query to respect Access Rights, get language from Forum and much more !!!
        $fields = 'uid, text';
        $table = 'tx_mmforum_domain_model_forum_post';
        $where = 'pid=67';

        // ToDo

        $res = $db->exec_SELECTquery($fields,$table,$where);
        $resCount = $db->sql_num_rows($res);

        // echo $db->debug_lastBuiltQuery . PHP_EOL;

        if($resCount) {

            while(($record = $db->sql_fetch_assoc($res))){

                // Prepare data for the indexer
                $title = $record['text'];
                $abstract = '';
                $teaser = '';
                $description = '';

                $content = $title;


                // ToDo  Adjust settings , field infso and parameters
                $tags = '';
                $sys_language_uid = 0;
                $starttime = 0;
                $endtime = 0;
                $feGroup = '';
                $debugOnly = false;

                #$parameters = '&tx_jvevents_events[event]=' . intval($record['uid'] . '&');
                $parameters = [
                    'tx_jvevents_events[event]=' . intval($record['uid']),
                    'tx_jvevents_events[action]=show',
                    'tx_jvevents_events[controller]=Event'
                ];

                // The following should be filled (in accordance with the documentation), see also:
                // http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/
                $additionalFields = array(
                    'orig_uid' => $record['uid']
                );

                // take storage PID form indexexer Configuration or overwrite it with storagePid From Indexer Task ??
                $pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'] ;


                // ToDo  Adjust Target URL, must be an external URL
                $url =  $indexerConfig['targetpid'] ;

                $indexerObject->storeInIndex(
                    $pid  ,			// folder, where the indexer is stored (not where the data records are stored!)
                    $title,							// title in the result list
                    'forum',				        // content type (not used at the moment, useful, if you want to use additionalResultMarker)
                   $url ,	// uid of the targetpage (see indexer-config in the backend)
                    $content, 						// below the title in the result list
                    $tags,							// tags (not used here)
                    '&' . implode('&', $parameters),						// additional typolink-parameters, e.g. '&tx_jvevents_events[event]=' . $record['uid'];
                    $abstract,						// abstract (not used here)
                    $sys_language_uid,				// sys_language_uid
                    $starttime,						// starttime (not used here)
                    $endtime,						// endtime (not used here)
                    $feGroup,						// fe_group (not used here)
                    $debugOnly,						// debug only?
                    $additionalFields				// additional fields added by hooks
                );

            }

        }

        return intval($resCount);
    }
}