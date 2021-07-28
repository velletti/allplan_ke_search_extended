<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;


use Doctrine\DBAL\FetchMode;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GenericRepositoryTablenameHook{

    /*
     * check if  tablename by given type exists
     * NEEDED for news, as on Connect this table does not exist
     */
	public function getTableName(string $type): string
    {

        switch ($type) {
            case 'page':
                return 'pages';
                break;
            case 'news':
                $tableNameToCheck = 'tx_news_domain_model_news';
                break;
            case 'file':
                return 'sys_file';
                break;
            default:
                // check if a table exists that matches the type name
                $tableNameToCheck = strip_tags(htmlentities($type));
        }

        // check if a table exists that matches the type name
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($tableNameToCheck);
        $statement = $connection->prepare('SHOW TABLES LIKE "' . $tableNameToCheck . '"');
        $statement->execute();
        if ($statement->fetch(FetchMode::ASSOCIATIVE)) {
            return $tableNameToCheck;
        }
        return "" ;

	}



}