<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\Connect\MmForumIndexerTypes;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Php
 */
use Exception;


class IndexerUtility
{

	/**
	 * Gets the storage pid, where the index record should be stored
	 * If the pid was defined in scheduler task, it will be preferred, otherwise the pid from indexer configuration
	 * will be taken
	 * @param IndexerRunner $indexerRunner
	 * @param array $indexerConfig
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getStoragePid(IndexerRunner $indexerRunner, array $indexerConfig): string
	{

		$pid = $indexerConfig['pid'];
		$taskConfiguration = $indexerRunner->getTaskConfiguration();

		if(!empty($taskConfiguration->getStoragePid())){
			$pid = $taskConfiguration->getStoragePid();
		}

		return (string)$pid;

	}

	/**
	 * Gets the language, which should be written to tx_kesearch_index.language
	 * There are the following possibilities from scheduler task configuration:
	 * - '':   empty => language from indexed record should be taken
	 * - '-1': All languages
	 * - '0':  Default language
	 * - '1':  Germany
	 * - ...
	 * If the language is not set in indexer runner (''), the language of the indexed record has to be set,
	 * otherwise Exception will be thrown here
	 * @param IndexerRunner $indexerRunner
	 * @param int|string|null $recordLanguage
	 * @return int|string
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getLanguage(IndexerRunner $indexerRunner, $recordLanguage = null)
	{

		$schedulerLanguage = $indexerRunner->getTaskConfiguration()->getSysLanguageUid();

		// If scheduler language is empty => take the language of the record
		if($schedulerLanguage == ''){

			if(is_null($recordLanguage)){
				throw new Exception('No language is set in indexer runner => language hast to be set by indexed record, but it is null');
			}

			return $recordLanguage;

		}

		return $schedulerLanguage;

	}

	/**
	 * Checks, if a given type is a forum indexer type
	 * (for forum, we have multiple indexer types)
	 * @param string $type
	 * @return bool
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function isForumIndexerType(string $type): bool
	{
		$mmForumIndexerTypes = GeneralUtility::makeInstance(MmForumIndexerTypes::class);
		if(in_array($type, [
			$mmForumIndexerTypes::FORUM_INDEXER_TYPE_DEFAULT,
			$mmForumIndexerTypes::FORUM_INDEXER_TYPE_SP,
			$mmForumIndexerTypes::FORUM_INDEXER_TYPE_LOCKED
		])){
			return true;
		}
		return false;
	}

}