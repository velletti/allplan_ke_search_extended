<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\AllplanFaqIndexerUtility;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\JsonUtility;
use Allplan\AllplanKeSearchExtended\Utility\MailUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

class AllplanFaqIndexer
{

	/**
	 * Allplan Faq indexer
	 * @param array $indexerConfig
	 * @param AllplanKesearchIndexer $indexerObject
	 * @return bool|int
	 * @throws DoctrineDBALDriverException
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function main(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject)
	{

		// ToDo Put tags to Indexer object
		$indexerConfig['tags'] = '#allplanfaq#';

		$allplanFaqIndexerUtility = GeneralUtility::makeInstance(AllplanFaqIndexerUtility::class);
		// @extensionScannerIgnoreLine
		$allplanFaqIndexerUtility->init($indexerConfig ,$indexerObject);

		$url = trim( $indexerObject->externalUrl);
		$debug = 'Url to XML file in config: ' . ($url) . PHP_EOL . PHP_EOL;
		$debug .= 'Max entries in config: ' . intval($indexerObject->rowcount) . PHP_EOL . PHP_EOL;
		if ($url == '') {
			// until 12.10.2021
			// $url = "http://212.29.3.155/hotline/FAQ_HOTD.nsf/0/05421C80A7EB2CE2C1257480004DDA2E/\$FILE/FAQIDs.xml?OpenElement";
			$url = "http://212.29.3.155/hotline/FAQ_HOTD.nsf/0/05D90839947490F7C1258767003338AC/\$FILE/FAQIDs.xml?OpenElement";
			$debug = 'Url to XML file set to: ' . ($url) . PHP_EOL . PHP_EOL;
		}

		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index');
		$query = $queryBuilder
			->select('uid', 'sortdate', 'targetpid', 'tstamp', 'orig_uid')
			->from('tx_kesearch_index')
			->where($queryBuilder->expr()->like('type', $queryBuilder->createNamedParameter('supportfa%',Connection::PARAM_STR)))
			->andWhere($queryBuilder->expr()->gt('sortdate', $queryBuilder->createNamedParameter(0,Connection::PARAM_INT)))
			->orderBy('sortdate','DESC')
			->addOrderBy('tstamp','DESC')
			->setMaxResults(1)
			->execute()
		;

		$debug.='<hr> SQL : ' . $queryBuilder->getSQL() . PHP_EOL . '<br>' . PHP_EOL . '<br>';
		$latestIndexRows = $query->fetchAssociative();

		if(is_array($latestIndexRows)){
			$lastRun = date('Y-m-d H:i:s', ($latestIndexRows['sortdate']));
			$lastRunTStamp = $latestIndexRows['sortdate'];
			$lastRunDay = date( "d" , ( $latestIndexRows['sortdate']  ) ) ;
			$debug .= PHP_EOL . PHP_EOL . '<hr> Lastest FAQ Entry in DB = UID: ' . $latestIndexRows['uid'] . ' | lastRun : ' . $lastRun . ' | lastRun day ' .  $lastRunDay. ' | sort date: ' . $latestIndexRows['sortdate'] . PHP_EOL;
		} else {
			$lastRun = '2014-12-31 00:00:00';
			$lastRunTStamp = 1;
			$lastRunDay = '0';
			$debug .= ' <hr> Found no FAQ entry in DB = set lastRun to ' . $lastRun;

		}

		// For testing
		// $xmlFromUrl = $this->getExampleXml() ;
		$xmlFromUrl = JsonUtility::getJsonFile($url,'urlset', ['Accept: text/xml, Content-type:text/xml'],false);
		$xmlFromUrlDebug = strip_tags(str_replace(['<lastmod>', '</lastmod>', '<loc>', '</loc>'], ['LastMod: ', '', PHP_EOL . 'Url: ', ' '], $xmlFromUrl));
		$xml2 = simplexml_load_string($xmlFromUrl);
		$debug .= ' <hr> xlm2 from string:<br>' . substr(var_export($xml2,true),0,200) . ' ... ' . strlen($xml2) . ' chars ... <hr />';

		$debugLong = '';
		$error = 0;
		$count = 0;
		$numIndexed = 0;
		$forgottenIndexed = 0;
		$testedOldIndexed = 0;
		$maxIndex =  $indexerObject->rowcount;
		$lastModDate = '9999-99-99';
		$lastModDay  = '99';

		if($indexerObject->rowcount < 1){
			$maxIndex = 10000 ;
			$debug .= 'Max entries set to: ' . $maxIndex . PHP_EOL . PHP_EOL;
		}

		$errorDebug = '';
		if(is_object($xml2)){
			if(is_object($xml2->url)){

				MailUtility::debugMail(
					['jvelletti@allplan.com'],
					'[FAQ-Indexer] FAQ indexer will run with url: ' . $url . ' : entries : ' . count($xml2->url),
					substr( $xmlFromUrlDebug , 0 , 1500 )  . ' ... ' .  strlen($xmlFromUrlDebug) . ' chars ... ' . substr($xmlFromUrlDebug, -200, 200) . PHP_EOL
				);

				$debug .= 'Put to array, if newer than ' . $lastRun . ' tStamp : ' . $lastRunTStamp;
				$faq2beIndexed = [];

				foreach ($xml2->url as $url){

					$notesLastMod = $url->lastmod;

					if(count($faq2beIndexed) < 1){
						MailUtility::debugMail(
							['jvelletti@allplan.com'],
							'[FAQ-Indexer] testing notes last date ' . $notesLastMod . ' ' . $url->lastmod,
							'notesLastMod' . $notesLastMod . ' against date from XML; Url->lastmod '. $url->lastmod . ' and date when lastRun ' . $lastRun. ' with this FAQ : ' . $url->loc
						);
					}

					if(strlen(trim($url->lastmod)) == 10){
						$notesLastMod .= ' 23:59:59';
					}
					if ($notesLastMod > $lastRun ) {
						if (empty($debugLong)){
							$debug .= ' ******************************************* first added FAQ  ****************** ';
							$debug .= PHP_EOL . '<hr>url->loc: ' . $url->loc . ' : lastMod: ' . $notesLastMod;
						}

						$debugLong .= PHP_EOL . '<hr>url->loc: ' . $url->loc . ' : lastMod: ' . $notesLastMod ;
						$faq2beIndexed[] = $url;
						if(count($faq2beIndexed)/ 100 == round(count($faq2beIndexed)/100)){
							MailUtility::debugMail(
								['jvelletti@allplan.com'],
								'[FAQ-Indexer] Get current FAQs ... found already ' . count($faq2beIndexed),
								$debug
							);
						}
					}else{

						if($maxIndex < 1000) {
							$debug .= ' ******************************************* latest already indexed FAQ  ****************** ';
							$debug .= PHP_EOL . '<hr>url->loc: ' . $url->loc . ' : lastmod: ' . $url->lastmod;
							break;
						}
						$testedOldIndexed++;
						$currentRow = $allplanFaqIndexerUtility->getIndexerRowFromPath($url->loc);
						if($currentRow){
							if(date('Y-m-d H:i:s', $currentRow['sortdate']) < $url->lastmod){
								$faq2beIndexed[] = $url;
								$forgottenIndexed++;
								if($forgottenIndexed < 3){
									MailUtility::debugMail(
										['jvelletti@allplan.com'],
										'[FAQ-Indexer] Get older FAQs by url',
										date( 'Y-m-d H:i:s', $currentRow['sortdate']) . ' < ' . $url->lastmod
									);
								}
							}
						}
						// todo : maybe we need to check  max-index * 3 or higher
						if($forgottenIndexed > ($maxIndex / 10) || $testedOldIndexed > ($maxIndex * 2)){
							MailUtility::debugMail(
								['jvelletti@allplan.com'],
								'[FAQ-Indexer] Get forgotten indexed FAQs ... found already : ' . $forgottenIndexed,
								$debug
							);
							break;
						}
					}
				}

				$reversed = array_reverse($faq2beIndexed);
				$debug .= PHP_EOL . '******************************************* found ' . count($reversed) . ' ****************** ';
				$debug .= PHP_EOL . '********* included older indexed: ' . $forgottenIndexed . ' ****************** ';
				if(count($reversed) > 0){
					MailUtility::debugMail(
						['jvelletti@allplan.com'],
						'[FAQ-Indexer] FAQ indexer found ' . count($reversed),
						$debug
					);

					foreach($reversed as $url){

						$debugLong .= '<hr>url->loc: ' . $url->loc . ' : lastmod: ' . $url->lastmod . '(' . $lastModDate . ') (' . $numIndexed . ' / ' . $maxIndex . ') ';
						$numIndexed ++;
						if($numIndexed >= ($maxIndex *.9) && $lastModDate == '9999-99-99'){
							$lastModDate = substr( trim($url->lastmod),0,10);
							$lastModDay = substr($lastModDate,8,2);
							$debug .= '<hr> Changed last mod date to: ' . $lastModDate . ' and lastModDay to ' . $lastModDay;
						}
						if (substr(trim($url->lastmod),0,10) == $lastModDate || $lastRunDay == $lastModDay){

							// if e.g. max index is configured as 100 and the first 90 FAQ are changed on the SAME day, we will index 190.
							// if the first 200 have the same date, it will continue until date changes and indexer will index 100 (configured number) FAQs more
							// and to be sure: if we get for all FAQs the same last mod date, this would lead to a deadlock => max should  3 times of config
							if($indexerObject->rowcount > 1){
								if ($maxIndex < ($indexerObject->rowcount * 2)){
									$debugLong .= ' - LINE: ' . __LINE__ . ' (restricted maxIndex ++)';
									$maxIndex ++;
								}
							} else {
								$debugLong .= ' - LINE: ' . __LINE__ . '  (unrestricted maxIndex ++) ';
								$maxIndex ++;
							}

						}
						if($numIndexed <= $maxIndex){
							if($allplanFaqIndexerUtility->indexSingleFAQ($url->loc, $url->lastmod)){
								$debugLong .= ' ... indexed ';
								$count++;
							} else {
								$debugLong .= ' Error! ';
								$error++;
								$errorDebug .= '<hr>Error on: url->loc: ' . $url->loc . ' : last mod: ' . $url->lastmod . '(' . $lastModDate . ')';
							}
						}
						$debug .= PHP_EOL;
					}
				}
			}
		}
		// var_dump($debug);
		if($error > 0){
			$error = true;
			$introTag = '[FAQ-Indexer-ERROR]';
			$errorDebug = '<hr>See FAQs with errors on Index<hr>' . PHP_EOL . '<hr>' . PHP_EOL . $errorDebug;
		} else {
			$introTag = '[FAQ-Indexer]';
		}

		$details = 'Allplan FAQ indexer: got ' . $numIndexed  . ' entries, got ' . $error . ' errors and had updated / inserted: ' . $count . ' entries. Crawled: ' . $url
			. ' and got xlm2 from string: ' . substr(var_export($xml2,true),0,500) . ' ... total: ' . strlen($xml2) . ' chars';

		if(strlen($debugLong) > 4000){
			$debugLong = substr($debugLong,0,1000) . PHP_EOL . substr($debugLong, -1000,1000);
		}
		MailUtility::debugMail(
			['jvelletti@allplan.com', 'slorenz@allplan.com'],
			$introTag . ' FAQ Indexer has run on ' . $count . ' objects ',$details . PHP_EOL
			. $errorDebug . PHP_EOL
			. $debug . PHP_EOL . PHP_EOL
			. $debugLong
		);

		// take storage pid from indexer configuration or overwrite it with storage pid from indexer task
		$pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'];

		$insertFields = array(
			'action' => 1 ,
			'tablename' => 'tx_kesearch_index',
			'error' => $error > 0,
			'event_pid' => $pid,
			'details' => $details,
			'tstamp' => time(),
			'type' => 1,
			'message' => $debug,
		);
		DbUtility::writeToSyslog($insertFields);

		if($error){
			return false;
		}
		if( $count > 0 ){
			return $count;
		}
		return true;

	}

	/**
	 * Get an example xml, used for local testing
	 * @return string
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function getExampleXml(): string
	{
		$exampleXml ='<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$exampleXml .='<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
		$exampleXml .='   <url>' . PHP_EOL;
		$exampleXml .='   <loc>https://connect.allplan.com/de/faqid/20200820142654.html</loc>' . PHP_EOL;
		$exampleXml .='   <lastmod>2017-05-29</lastmod>' . PHP_EOL;
		$exampleXml .='</url>' . PHP_EOL;
		$exampleXml .='</urlset>';
		return $exampleXml;
	}

}