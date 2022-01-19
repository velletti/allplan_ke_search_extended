<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * AllplanKesearchIndexer
 */
use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Php
 */
use DateTime;

class AllplanElearningsIndexer
{
	/**
	 * Allplan E-learning indexer
	 * @param array $indexerConfig
	 * @param AllplanKesearchIndexer $indexerObject
	 * @return int
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function main(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject): int
	{

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getConnectionForTable('tx_maritelearning_domain_model_lesson')->createQueryBuilder();
		$queryBuilder
			->select('*')
			->from('tx_maritelearning_domain_model_lesson')
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

				$tags = '#videos#';
				$sys_language_uid = $record['sys_language_uid'];

				/** @var DateTime $sortDate */
				$sortDate = $record['date'];
				$endtime = 0;
				$debugOnly = false;

				$parameters = [
					'tx_maritelearning_pi1[lesson]=' . intval( $record['uid']),
					'tx_maritelearning_pi1[action]=single',
					'tx_maritelearning_pi1[controller]=Lesson'
				];

				// https://connect.local/en/learn/featured/play-a-video.html?tx_maritelearning_pi1%5Blesson%5D=102&
				// &tx_maritelearning_pi1%5Bsys_language_uid%5D=0&tx_maritelearning_pi1%5Baction%5D=single
				//&tx_maritelearning_pi1%5Bcontroller%5D=Lesson&cHash=e4dc73713b0043f46ab2cb6baff4b013

				$origId = $record['l18n_parent'];
				$feGroup = $record['fe_group'];
				unset($recordOrig);
				if( $origId > 0 ){
					if( is_array( $origData[$origId])){
						$recordOrig = $origData[$origId];
					} else {
						$subQuery = $queryBuilder;
						$expr = $subQuery->expr();
						$subQuery->where($expr->eq('uid' , $origId));
						$recordOrig = $subQuery->execute()->fetch();
						$origData[$origId] = $recordOrig;
					}
					$feGroup = $recordOrig['fe_group'];
				}

				$feGroupArray = explode(',', $feGroup);

				// if video is available for everybody (forum member) or normal customers => we will include this in search index
				// The final access is managed by the extension. So only internal videos or "student"-videos will have a fe-groups entry
				if(in_array('1', $feGroupArray) || in_array('3', $feGroupArray) || in_array('10', $feGroupArray) || in_array('11', $feGroupArray) || in_array('8', $feGroupArray)){
					$feGroup = '';
				}

				$additionalFields = [
					'orig_uid' => $record['uid'],
					'sortdate' => 0,
					'servername' => EnvironmentUtility::getServerName()
				];
				if($sortDate > 0){
					$additionalFields['sortdate'] = intval($sortDate);
				}

				$pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'];
				$url = 'https://connect.allplan.com/index.php?id=' . $indexerConfig['targetpid'] . '&' . implode('&', $parameters);
				if($sys_language_uid > -1){
					$url .= '&L=' . $sys_language_uid;
				}

				$indexerObject->storeInIndex(
					$pid,
					$title,
					'lessons',
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

		DbUtility::writeToSyslog([
			'action' => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => $pid,
			'details' => 'Allplan Elearning lessons Indexer had updated / inserted ' . $resCount . ' entries',
			'tstamp' => time(),
			'type' => 1,
			'message' => var_export($indexerObject,true),
		]);

		return $resCount;
	}
}