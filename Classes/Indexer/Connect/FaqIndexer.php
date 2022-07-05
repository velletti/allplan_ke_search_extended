<?php
namespace Allplan\AllplanKeSearchExtended\Indexer\Connect;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\IndexerBase;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerInterface;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\FaqUtility;
use Allplan\AllplanKeSearchExtended\Utility\FormatUtility;
use Allplan\AllplanKeSearchExtended\Utility\IndexerUtility;

/**
 * KeSearch
 */

use Allplan\Library\Salesforce\Model\Knowledgebase;
use Allplan\Library\Salesforce\Service\KnowledgeBases;
use Allplan\Library\Salesforce\Utility\DateUtility;
use Allplan\NemMysupport\Utility\GetConfig;
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * Php
 */
use Exception;


/**
 * Indexer for forum (EXT:mm_forum)
 */
class FaqIndexer extends IndexerBase implements IndexerInterface
{

	/**
	 * For Development
	 * Limit the number of topics to a number for faster development
	 * @var int|null
	 */
	const FAQ_INDEXER_NR_OF_TOPICS_TO_INDEX = 5 ;

	/**
	 * Forum indexer types
	 */
	const FAQ_INDEXER_TYPE_DEFAULT = 'supportfaq';


    const FAQ_DEFAULT_TAG = "#allplanfaq#" ;



	/**
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing(): int
	{

		// Todo delete unneeded functions in utilities

		// Better variable name
		/** @var KeSearchIndexerRunner|IndexerRunner $indexerRunner */
		$indexerRunner = $this->pObj;
		$indexerConfig = $this->indexerConfig;
        $starttime = time() ;
		$latest = DbUtility::getLatestSortdateByIndexerType( self::FAQ_INDEXER_TYPE_DEFAULT . "%" ) ;

        $knowledgeBases = new KnowledgeBases( GetConfig::read() ) ;


		$result = $knowledgeBases->getKnowledgeBasesModifiedAfterDate(
            DateUtility::convertTimestampToSalesforceDate( $latest , false ) ,
            self::FAQ_INDEXER_NR_OF_TOPICS_TO_INDEX , 0 , 'Online' , '' , true ) ;

		$count = 0;
		$errorCount = 0;
        $details = "\n" . ' modified after: '  .  DateUtility::convertTimestampToSalesforceDate($latest , false )  ;
        $details .= "\n" . " Query:  " . $knowledgeBases->getLatestQuery() . " | ";
        $logdata = '' ;

		if($result){
            /** @var Knowledgebase  $recordObj */
            foreach ($result as $recordObj ){
                if ( $count ==  0 ) {
                    $details .= "\n" . " First: " . $recordObj->getId() ;
                }
                if( $recordObj  ) {
                    if( !$recordObj->getTitle()  ) {
                        $recordObj->setText("Missing Title") ;
                    }
                    if(  !$recordObj->getText() ) {
                        $recordObj->setText("Missing Text") ;
                    }
                    $record = FaqUtility::getRecordAsArray($recordObj , self::FAQ_DEFAULT_TAG ) ;

                    try{
                        // Todo : remove Entry from Index.
                        // IMPORTANT as the TYPE can change from "Available for All" to "restricted to Support"
                        // and insert7Update compairs also the TYPE
                        // Write record to index
                        if($this->storeInKeSearchIndex($record, $indexerRunner, $indexerConfig)){
                            $count++;

                        } else {
                            $errorCount++;
                            $logdata .= " Error on ID: " . $recordObj->getId() . "\n";
                        }
                    } catch (\TYPO3\CMS\Core\Type\Exception $e) {
                        // nothing
                        $errorCount++;
                        $logdata .= " Exception: " . $e->getMessage() . " on ID: ". $recordObj->getId() . "\n";
                    }
                }


			}
            if ($count > 0 ) {
                $details .= "\n" . " Last: " . $recordObj->getId() . " ( " . $recordObj->getLastPublishedDateAsString() . " ) ";
            }

		}
        if (   $errorCount ) {
            $details .= "\n" . "Errors on  " . $errorCount . " entries (see sys_log) "  ;
        }
        $details .= "\n" . "( took " . ( time() - $starttime ) . " seconds) "  ;
        // Write to sys_log
        DbUtility::saveIndexerResultInSysLog(
            'Indexer: Forum (EXT:FAQ from Salesforce)',
            $count ,
            ' Inserted/updated:  ' . $count . " FAQs. " . "\n" . $details ,
            $logdata
        );



		return $count;

	}

	/**
	 * Write data to index (tx_kesearch_index)
	 * @param array $record
	 * @param IndexerRunner|KeSearchIndexerRunner $indexerRunner
	 * @param array $indexerConfig
	 * @return bool|int
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function storeInKeSearchIndex(array $record, IndexerRunner $indexerRunner, array $indexerConfig)
	{

		$debugOnly = false;


		// Call the function from ke_search
		return $indexerRunner->storeInIndex(
            $record['pid'] ,
			$record['title'],
			$record['type'],
			$record['targetPid'],
			$record['content'],
			$record['tags'],
			$record['params'],
			$record['abstract'],
			$record['language'],
			$record['startTime'],
			$record['endTime'],
			$record['feGroup'],
			$debugOnly,
			$record['additionalFields']
		);

	}

}