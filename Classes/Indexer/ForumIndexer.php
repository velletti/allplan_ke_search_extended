<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

class ForumIndexer
{
	/**
	 * Indexer for Allplan forum (mm_forum => connect)
	 * @param array $indexerConfig
	 * @param AllplanKesearchIndexer $indexerObject
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function main(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject): int
	{

		$forumDataStoragePid = 67;

		$debug = '[KE search Indexer] Indexer Forum Entries starts ' . PHP_EOL ;

		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index');

		$lastRunQuery = $queryBuilder
			->select( 'sortdate' )
			->from('tx_kesearch_index' )
			->where($queryBuilder->expr()->like('type', $queryBuilder->createNamedParameter('allplanforu%')))
			->setMaxResults(1)->orderBy('sortdate','DESC');

		$debug .= 'Query LastRun : ' . $lastRunQuery->getSQL();

		$lastRunRow = $lastRunQuery->execute()->fetchAllAssociative();
		$debug .= 'Result: ' . var_export($lastRunRow, true);
		if($indexerObject->period > 365){
			$lastRun = time() - (60 * 60 * 24 * ($indexerObject->period));
		}
		if(is_array($lastRunRow)) {
			$debug .= 'Last Forumsentry in Index: index uid: ' . $lastRunRow['uid'] . ' post uid: ' .  $lastRunRow['orig_uid']  . PHP_EOL . ' sort date: ' . date ( 'd.m.Y H:i' , $lastRunRow['sortdate']) . PHP_EOL . PHP_EOL;
			$lastRun = $lastRunRow['sortdate'];
		}
		if (intval($lastRun) < 100000){
			$lastRun = mktime(0,0,0, date('m'), date('d'),date('Y') - 10);
		}
		// remove 2 seconds: We can write > condition instead of >= AND we will get also posts if they are written in the same second
		$lastRun = $lastRun - 2;
		$debug .= 'Set lastRun to : ' . date( 'd.m.Y H:i:s', $lastRun);


		// Now build the query to get 9999 posts with needed infos
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_post');
		$resultQuery = $queryBuilder
			->select('p.uid', 'u.username', 'p.text', 't.subject', 'f.displayed_pid', 'f.title', 'p.topic', 'f.sys_language_uid', 'p.crdate', 'p.tstamp', 'f.uid AS forumUid')
			->from('tx_mmforum_domain_model_forum_post','p')
			->join(
				'p',
				'tx_mmforum_domain_model_forum_topic',
				't',
				$queryBuilder->expr()->eq('t.uid', $queryBuilder->quoteIdentifier('p.topic'))
			)->join(
				't',
				'tx_mmforum_domain_model_forum_forum',
				'f',
				$queryBuilder->expr()->eq('t.forum', $queryBuilder->quoteIdentifier('f.uid'))
			)->join(
				'p',
				'fe_users',
				'u',
				$queryBuilder->expr()->eq('p.author', $queryBuilder->quoteIdentifier('u.uid'))
			)
			->andWhere(
				$queryBuilder->expr()->eq('p.pid', $forumDataStoragePid)
			)->andWhere(
				$queryBuilder->expr()->eq('p.deleted', 0)
			)->andWhere(
				$queryBuilder->expr()->eq('t.deleted', 0)
			)->andWhere(
				$queryBuilder->expr()->eq('f.deleted', 0)
			)->setMaxResults(999)
		;

		$sortDate = 'p.crdate';
		$result =  $resultQuery
			->andWhere($queryBuilder->expr()->gt('p.crdate', $lastRun))
			->orderBy($sortDate, 'ASC')
			->execute()
		;

		if(!$result->fetchAllAssociative()){
			// all new entries since last run are indexed => so wie need to index modified entries
			$sortDate = 'p.tstamp';
			$result =  $resultQuery
				->andWhere($queryBuilder->expr()->gt('p.tstamp', $lastRun))
				->orderBy($sortDate, 'ASC')
				->execute()
			;
		}

		$debug2 = '';
		$count = 0;
		$tagsFound = 0;
		while($record = $result->fetchAllAssociative()){

			// Prepare data for the indexer
			$title = $record['subject'];
			$abstract = '';
			if( $debug2 == ''){
				$debug2 = 'Indexing the following posts: ' . $record['uid'] . " - ";
			}

			// name des forums, subject of topics and the content
			$content = $record['title'] . PHP_EOL . $title . PHP_EOL . $record['text'] . PHP_EOL;
			$content .=  PHP_EOL . 'Topic:' .$record['topic'];
			if( $record['username']){
				$content .=  PHP_EOL . "User:" .$record['username'];
			}
			$tagQueryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_tag_topic');

			$tagQuery = $tagQueryBuilder
				->select('t.name as name')
				->from('tx_mmforum_domain_model_forum_tag_topic','mm')
				->join(
					'mm',
					'tx_mmforum_domain_model_forum_tag',
					't',
					$tagQueryBuilder->expr()->eq('t.uid', $tagQueryBuilder->quoteIdentifier('mm.uid_foreign')))
				->where($tagQueryBuilder->expr()->eq('mm.uid_local', $record['topic']))
				->execute()
			;

			$content .=  PHP_EOL . 'Tag: ';
			while($tagRow = $tagQuery->fetchAllAssociative()){
				$content .= " " . $tagRow['name'];
				$tagsFound ++;
			}
			$attachmentRows = GeneralUtility::makeInstance(ConnectionPool::class)
				->getConnectionForTable('tx_mmforum_domain_model_forum_attachment')
				->select(
					['filename'],
					'tx_mmforum_domain_model_forum_attachment',
					['post' => $record['uid']]
				)
			;

			while( $attachmentRow = $attachmentRows->fetchAllAssociative()) {
				$content .=  PHP_EOL . 'File:' . $attachmentRow['filename'];
			}

			$tags = '#forum#';

			switch ($record['sys_language_uid']){
				case 1:
					$sys_language_uid = -1;
					$pid = 5003;
					break ;
				case 0:
					$sys_language_uid = 0;
					$pid = 5004;
					break ;
				default :
					$sys_language_uid = $record['sys_language_uid'];
					$pid = 5005;
					break ;
			}

			$starttime = 0;
			$endtime = 0;
			$debugOnly = false;

			// The following should be filled (in accordance with the documentation), see also:
			// http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/

			$additionalFields = [
				'orig_uid' => $record['uid'],
				'sortdate' => $record[$sortDate],
				'servername' => EnvironmentUtility::getServerName()
			];

			// Todo: Adjust target URL, must be an external URL
			$url =  'https://connect.allplan.com/index.php?id=' . $record['displayed_pid'] . '&L=' . $record['sys_language_uid'];
			$url .= '&tx_mmforum_pi1[controller]=Topic&tx_mmforum_pi1[action]=show&tx_mmforum_pi1[topic]=' . $record['topic'] . '&tx_mmforum_pi1[forum]='  . $record['forumUid'];

			// get FE Groups and decide if we store show this
			// a) public or
			// b) allow access only for specific fe_groups

			$type = "allplanforumlocked";
			$feGroup = '' ;

			$accessData = GeneralUtility::makeInstance(ConnectionPool::class)
				->getConnectionForTable('tx_mmforum_domain_model_forum_attachment')
				->select(
					['login_level' , 'affected_group'],
					'tx_mmforum_domain_model_forum_access',
					['operation' => 'read', 'forum' =>  $record['forumUid']],
					[],
					['affected_group' => 'DESC']
				)
			;

			$feGroupsArray = [];
			while( $access = $accessData->fetchAllAssociative()){
				if($access['login_level'] == 0 ||  $access['login_level'] == 1){
					$type = 'allplanforum';
				} else {
					if($access['affected_group'] == 3){
						$type = 'allplanforumsp';
					}
					if ($access['affected_group'] == 1){
						$type = 'allplanforum' ;
					}
					$feGroupsArray[] = $access['affected_group'];
				}
			}
			if($type == 'allplanforumlocked' && count( $feGroupsArray) > 0){
				$feGroup = implode(',' , $feGroupsArray);
			}

			$indexerObject->storeInIndex(
				$pid,               // folder, where the indexer is stored (not where the data records are stored!)
				$record['subject'], // title in the result list
				$type,              // content type
				$url,               // uid of the target page (see indexer-config in the backend)
				$content,           // below the title in the result list
				$tags,              // tags (not used here)
				'',          // additional typolink-parameters, e.g. '&tx_jvevents_events[event]=' . $record['uid'];
				$abstract,          // abstract (not used here)
				$sys_language_uid,  // sys_language_uid
				$starttime,         // starttime (not used here)
				$endtime,           // endtime (not used here)
				$feGroup,           // fe_group
				$debugOnly,         // debug only?
				$additionalFields   // additional fields added by hooks
			);

			$count++ ;

		}

		$debug .= ' ' . $record['uid'] . " ! ";
		$insertFields = [
			'action' => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => 0,
			'details' => 'Indexer forum entries',
			'tstamp' => time(),
			'type' => 1,
			'message' => $debug . ' part 2 : ' . $debug2 . ' | tags found: ' . $tagsFound,
		];

		DbUtility::writeToSyslog($insertFields);
		return $count;

	}

}