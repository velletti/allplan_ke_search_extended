<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * Php
 */
use DateTime;

class AllplanDocumentationsIndexer
{
	/**
	 * @param array $indexerConfig configuration from TYPO3 backend
	 * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
	 * @return int
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function main(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject): int
	{

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getConnectionForTable('tx_maritelearning_domain_model_download')->createQueryBuilder();
		$queryBuilder
			->select('*')
			->from('tx_maritelearning_domain_model_download')
		;
		$indexerRows = $queryBuilder->execute();

		$origData = [];
		if($indexerRows){
			$resCount = 0;

			while(( $record = $indexerRows->fetch())){
				$resCount++;
				$title = $record['title'];
				$abstract = $record['desc_short'];
				$description = $record['desc_long'];

				$content = $title . PHP_EOL . nl2br($abstract) . PHP_EOL . nl2br($description);
				$content .= $this->getFileContent($connectionPool, $record['uid']);
				$tags = '#pdf#,#downloads#';
				$sys_language_uid = $record['sys_language_uid'];

				/** @var DateTime $sortdate */
				$sortdate = $record['tstamp'];
				$endtime = 0;
				$debugOnly = false;

				if(intval($record['l18n_parent']) > 0){
					$parameters = [
						'tx_maritelearning_pi1[download]=' . intval($record['l18n_parent']),
						'tx_maritelearning_pi1[action]=single',
						'tx_maritelearning_pi1[controller]=Download'
					];
				} else {
					$parameters = [
						'tx_maritelearning_pi1[download]=' . intval($record['uid']),
						'tx_maritelearning_pi1[action]=single',
						'tx_maritelearning_pi1[controller]=Download'
					];
				}

				// https://connect.allplan.com/de/training/dokumente.html?tx_maritelearning_pi1%5Bdownload%5D=2701
				// &tx_maritelearning_pi1%5BdownloadCat%5D=&tx_maritelearning_pi1%5Baction%5D=single&tx_maritelearning_pi1%5Bcontroller%5D=Download&cHash=26cd946f09ee1762121db6d4f03cb9ed

				$origId = $record['l18n_parent'];
				$feGroup = $record['fe_group'];
				unset($recordOrig) ;
				if($origId > 0){
					if(is_array($origData[$origId])){
						$recordOrig = $origData[$origId];
					} else {
						$subQuery = $queryBuilder;
						$expr = $subQuery->expr();
						$subQuery->where($expr->eq('uid', $origId));
						$recordOrig = $subQuery->execute()->fetch();
						$origData[$origId] = $recordOrig;
					}
					$feGroup = $recordOrig['fe_group'];
				}

				$feGroupArray = explode(',', $feGroup);
				if(in_array('1', $feGroupArray) || in_array('3', $feGroupArray) || in_array('10', $feGroupArray) || in_array('11', $feGroupArray) || in_array('8', $feGroupArray)){
					$feGroup = '';
				}

				$additionalFields = [
					'orig_uid' => $record['uid'],
					'sortdate' => 0,
					'servername' => EnvironmentUtility::getServerName()
				];

				if($sortdate > 0){
					$additionalFields['sortdate'] = intval($sortdate);
				}

				$pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'];
				$url = 'https://' . $additionalFields['servername'] . '/index.php?id=' . $indexerConfig['targetpid'] . '&' . implode('&', $parameters);
				if($sys_language_uid > -1){
					$url.= '&L=' . $sys_language_uid;
				}

				$indexerObject->storeInIndex(
					$pid,
					$title,
					'documentation',
					$url,
					$content,
					$tags,
					'&' . implode('&', $parameters),
					$abstract,
					$sys_language_uid,
					0,
					$endtime,
					$feGroup,
					$debugOnly,
					$additionalFields
				);
			}
		}

		$insertFields = [
			'action' => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => $pid,
			'details' => 'Allplan PDF Documents  Indexer had updated / inserted ' . $resCount . ' entries',
			'tstamp' => time(),
			'type' => 1,
			'message' => var_export($indexerObject,true),
		];

		DbUtility::writeToSyslog($insertFields);
		return $resCount;

	}

	/**
	 * @param ConnectionPool $connectionPool
	 * @param $uid
	 * @return string
	 */

	/**
	 * @param ConnectionPool $connectionPool
	 * @param int|string $uid
	 * @return string
	 * @throws DoctrineDBALDriverException
	 */
	public function getFileContent(ConnectionPool $connectionPool, $uid): string
	{

		$queryBuilder = $connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();
		$queryBuilder
			->select('sf.uid', 'sf.missing', 'sf.identifier', 'sf.name', 'sf.sha1', 'sf.creation_date', 'sf.modification_date')
			->from('sys_file', 'sf')
			->leftJoin('sf','sys_file_reference','sfr','sf.uid = sfr.uid_local')
		;

		$expr = $queryBuilder->expr();
		$queryBuilder
			->where($expr->eq('sf.missing',0))
			->andWhere($expr->eq('sfr.fieldname', $queryBuilder->createNamedParameter('tx_maritelearning_domain_model_download_download',Connection::PARAM_STR)))
			->andWhere($expr->eq('sfr.uid_foreign', $queryBuilder->createNamedParameter($uid,Connection::PARAM_INT))
		);

		$fileRecord = $queryBuilder->execute()->fetchAssociative();
		$file = Environment::getPublicPath() . '/fileadmin' . $fileRecord['identifier'];
		$className = 'tx_kesearch_indexer_filetypes_pdf';

		// check if class exists
		if (class_exists($className) && file_exists($file)){

			$fileObj = GeneralUtility::makeInstance($className);
			// check if new object has interface implemented

			// Todo old stuff
			if ($fileObj instanceof tx_kesearch_indexer_filetypes){
				// Do the check if a file has already been indexed at this early point in order
				// to skip the time expensive "get content" process which includes calls to external tools
				// fetch the file content directly from the index
				// echo " is class  " ;
				$fileContent = $fileObj->getContent($file);
				// remove line breaks from content in order to identify
				// additional content (which will have trailing linebreaks)
				return 'Filename: ' . $fileRecord['name'] . str_replace("\n", ' ', $fileContent);

			} else {
				return '';
			}
		}
		return  'fileadmin' . $fileRecord['identifier'];
	}

}