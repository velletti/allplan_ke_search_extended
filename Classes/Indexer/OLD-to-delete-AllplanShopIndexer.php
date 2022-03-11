<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\JsonUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * Lib
 */
use simple_html_dom;

// Todo: delete this file

class AllplanShopIndexer
{

	/**
	 * @param array $indexerConfig
	 * @param AllplanKesearchIndexer $indexerObject
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function main(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject): ?int
	{
		//
		// https://shop.allplan.com/export/sitemap1.xml
		// or
		// http://shop.allplan.com/export/sitemap2.xml and so on ...

		$url = $indexerObject->externalUrl;
		$debug = "url: " . ($url) ;
		// ToDo Put tags to Indexer object
		$indexerConfig['tags'] = "#shop#" ;

		// For testing
		/*
		$xmlFromUrl = '<?xml version="1.0" encoding="UTF-8"?>
			<urlset>
				<url>
					<loc>https://shop.allplan.com/Fuer-Allplan-Mitarbeiter/Allplan-Share-INTL.html</loc>
					<priority>1.0</priority>
					<lastmod>2017-06-21T11:27:46+00:00</lastmod>
					<changefreq>daily</changefreq>
				</url>
				<url>
					<loc>https://shop.allplan.com/Fuer-Allplan-Mitarbeiter/Bimplus-Intern.html</loc>
					<priority>1.0</priority>
					<lastmod>2017-06-21T11:27:46+00:00</lastmod>
					<changefreq>daily</changefreq>
				</url>
			<urlset>
        ' ;
		*/

		$xmlFromUrl = JsonUtility::getJsonFile($url,'urlset', ['Accept: text/xml, Content-type:text/xml'],false);
		$xml2 = simplexml_load_string($xmlFromUrl);

		// Todo => composer
		include_once(__DIR__ . '/simple_html_dom.php');
		$htmlParser = new simple_html_dom();
		$debug .= '<hr>xlm2 from string:<br>' . substr(var_export($xml2,true),0,200) . " ... " . strlen($xml2) . ' chars<hr />';
		$count = 0;
		$lastRunRow = DbUtility::getRawRecord('tx_kesearch_index',"`type` = 'shop' ORDER BY starttime DESC");

		if(is_array($lastRunRow)){
			$lastRun = date( 'Y-m-d', $lastRunRow['sorttime']);
			$debug .= '<hr>last run from DB = ' . $lastRun;
		}
		if($indexerObject->period > 365){
			$lastRun = date('Y-m-d',time() - (60 * 60 * 24 * ($indexerObject->period - 365)));
			$debug .= '<hr>last run from indexer config field period  = ' . $lastRun;
		}
		if(is_object($xml2)){

			$debug .= '<hr> xml2 is object';

			if(is_object($xml2->url)){

				$debug .= '<hr> xml2->url is array';
				$i = 0;

				foreach($xml2->url as $url){

					$debug .= '<hr>url loc: ' . $url->loc . ' : last mod: ' . $url->lastmod;

					if($url->lastmod > $lastRun){
						$i++;
						$urlSingleArray = parse_url($url->loc);

						// Todo: adjust pid for the languages
						$indexLang = 0;
						switch(substr($urlSingleArray['path'], 1,2)){
							case 'en':
								$lang = 0;
								$indexLang = -1;
								$indexerConfig['pid'] = 5091;
								break;
							case 'it':
								$lang = 2;
								$indexerConfig['pid'] = 5091;
								break ;
							case 'cz':
								$lang = 3;
								$indexerConfig['pid'] = 5091;
								break ;
							case 'fr':
								$lang = 4;
								$indexerConfig['pid'] = 5091;
								break ;
							case 'es':
								$lang = 18;
								$indexerConfig['pid'] = 5091;
								break ;
							case 'ru':
								$lang = 14;
								$indexerConfig['pid'] = 5091 ;
								break ;
							default:
								$lang = 1;
								$indexLang = -1;
								$indexerConfig['pid'] = 5090;
								break;
						}
						if ($indexLang == 0){
							$indexLang = $lang;
						}
						$urlSingle = $url->loc;
						$debug .= '<hr>url loc: ' . $urlSingle;
						$singlepage = JsonUtility::getJsonFile(
							$urlSingle,'',
							['Accept: text/html, Content-type:text/html'],
							false
						);

						// Todo: convert single page to object and strip the HTML Text
						if(strlen($singlepage) > 200){

							// # Todo Own function, WTF???
							$single['uid'] = $this->convertIdToINT($url->loc, $indexLang);
							$htmlParser->load($singlepage);

							$ret = $htmlParser->find('title');
							$first = $ret[0];
							$single['title'] = strip_tags(str_replace('Allplan Shop | ','', $first->plaintext));
							$debug .= '<hr>ID: ' . $single['uid'] . ' - ' . $single['title'];

							$ret = $htmlParser->find('meta[name="description"]');
							$first = $ret[0];
							$meta = $first->attr;
							$single['abstract']  = $meta['content'];
							$debug .= '<br>Abstract: ' . $single['abstract'];

							$findContentMarkers = ['.tabbedWidgetBox', '.infogridView'];

							$text = '';
							foreach($findContentMarkers as $marker){
								$ret = $htmlParser->find($marker);
								if(is_array($ret)){
									if(count($ret) > 0){
										$first = $ret[0];
										$text.= str_replace("     "," ",$first->plaintext);
									}
								}
							}

							$text = str_replace("   "," ", $text);
							$text = str_replace("  "," ", $text);

							$debug.= '<hr>Text: ' . $text;

							// =  $url->STRSUBJECT  ;
							$single['text'] = $single['abstract'] . ' ' . $text;
							$single['language'] =  $indexLang;
							$single['sortdate'] = mktime(
								0,0,0,
								substr($url->lastmod,8,2),
								substr($url->lastmod,5,2),
								substr($url->lastmod,0,4)
							);
							$single['url'] = $url->loc;
							if($this->putToIndex($single, $indexerObject, $indexerConfig)){
								$debug.= '<hr>' . var_export($single,true);
								$count++;
							}
						}
						$htmlParser->clear();
						unset($single) ;
						unset($singlepage);
						unset($singlepageContent);
					}
				}
			}
		}
		$pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid : $indexerConfig['pid'];
		$insertFields = [
			'action' => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => $pid,
			'details' => 'Shop Indexer had updated / inserted ' . $i . ' entries',
			'tstamp' => time(),
			'type' => 1,
			'message' => $debug,
		];
		DbUtility::writeToSyslog($insertFields);
		return $count;
	}

	/**
	 * @param array $single
	 * @param AllplanKesearchIndexer $indexerObject
	 * @param array $indexerConfig
	 * @return bool|int
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	protected function putToIndex(array $single, AllplanKesearchIndexer $indexerObject, array $indexerConfig)
	{

		// take storage pid form indexer configuration - hard coded by language
		$pid = $indexerConfig['pid'];

		return $indexerObject->storeInIndex(
			$pid, // folder, where the indexer data should be stored (not where the data records are stored!)
			$single['title'], // title in the result list
			'shop', // content type ( useful, if you want to use additionalResultMarker)
			$single['url'], // uid of the target page (see indexer-config in the backend)
			strip_tags($single['text']), // below the title in the result list
			$indexerConfig['tags'] . $single['tags'], // tags
			'_blank', // additional params for the link
			substr(strip_tags($single['text']),0,200), // abstract
			$single['language'], // sys_language_uid
			0, // starttime (not used here)
			0, // endtime (not used here)
			'', // fe_group (not used here)
			false, // debug only
			array('sortdate' => $single['sortdate'], 'orig_uid' => $single['uid'], 'servername' => EnvironmentUtility::getServerName()) // additional fields added by hooks
		);

	}

}