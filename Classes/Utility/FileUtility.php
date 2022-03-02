<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\Filetypes\Pdf as KeSearchFiletypePdf;
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileUtility
{

	/**
	 * Get the content of a pdf file, given by a sys_file.uid
	 * @param IndexerRunner|KeSearchIndexerRunner $indexerRunner
	 * @param string|int $sysFileUid
	 * @return string
	 * @throws FileDoesNotExistException
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getPdfFileContent($indexerRunner, $sysFileUid):string
	{

		$resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
		$file = $resourceFactory->getFileObject($sysFileUid);
		$filePath = Environment::getPublicPath() . DIRECTORY_SEPARATOR . $file->getPublicUrl();

		if(!file_exists($filePath)){
			throw new FileDoesNotExistException('No file found in filesystem for sys_file.uid: ' . $sysFileUid);
		}

		// Use the get content function from ke_search
		$KeSearchFiletypePdf = GeneralUtility::makeInstance(KeSearchFiletypePdf::class, $indexerRunner);
		return $KeSearchFiletypePdf->getContent($filePath);

	}

}