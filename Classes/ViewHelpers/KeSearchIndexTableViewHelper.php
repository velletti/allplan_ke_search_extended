<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;

/**
 * TYPO3Fluid
 */

use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class KeSearchUnlockViewHelper
 * @package Allplan\AllplanKeSearchExtended\ViewHelpers
 */
class KeSearchIndexTableViewHelper extends AbstractViewHelper
{

	/**
	 * @return string
	 * @throws RouteNotFoundException
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function render()
	{

		$table = 'tx_kesearch_index';

        // get table status
        $databaseConnection = Db::getDatabaseConnection($table);
        $tableStatusQuery = 'SHOW TABLE STATUS';
        $tableStatusRows = $databaseConnection->fetchAll($tableStatusQuery);
        $content = '';

        foreach ($tableStatusRows as $row) {
            if ($row['Name'] == $table) {
                $dataLength = $this->formatFilesize($row['Data_length']);
                $indexLength = $this->formatFilesize($row['Index_length']);
                $completeLength = $this->formatFilesize($row['Data_length'] + $row['Index_length']);

                $content .= '<h2>Allplan Ke Extended</h2>
					<table class="statistics">
						
						<tr>
							<td class="infolabel">Data size: </td>
							<td>' . $dataLength . '</td>
							<td></td>
						</tr>
						<tr>
							<td class="infolabel">Index size: </td>
							<td>' . $indexLength . '</td>
							<td></td>
						</tr>
						<tr>
							<td class="infolabel">Complete table size: </td>
							<td>' . $completeLength . '</td>
							<td></td>
							<tr>
							<td class="infolabel">Records: </td>
							<td>' . $row['Rows'] . '</td>
							<td>(Total)</td>
						</tr>
						</tr>';
            }
        }

        $results_per_type = $this->getNumberOfRecordsInIndexPerType();
        if (count($results_per_type)) {
            foreach ($results_per_type as $value) {
                $content .= '<tr><td>' . $value['type'] . '</td><td>' .  $value['count'] . '</td><td>' .  date( "d.m.Y H:i", $value['latest'] ) . '</td></tr>';
            }
        }
        $content .= '</table>';

		return $content ;

	}

    /**
     * returns number of records per type in an array
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 28.04.15
     * @return array
     */
    public function getNumberOfRecordsInIndexPerType()
    {
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $typeCount = $queryBuilder
            ->select('type')
            ->addSelectLiteral(
                $queryBuilder->expr()->count('tx_kesearch_index.uid', 'count')
            )
            ->addSelectLiteral(
                $queryBuilder->expr()->max('tx_kesearch_index.tstamp', 'latest')
            )
            ->from('tx_kesearch_index')
            ->groupBy('tx_kesearch_index.type')
            ->orderBy('tx_kesearch_index.type' )
            ->addOrderBy('tx_kesearch_index.tstamp' ,  'DESC')
            ->execute();

        return  $typeCount->fetchAllAssociative() ;
    }

    /**
     * format file size from bytes to human readable format
     */
    public function formatFilesize($size, $decimals = 0)
    {
        $sizes = array(" B", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        if ($size == 0) {
            return ('n/a');
        } else {
            return (round($size / pow(1024, ($i = floor(log($size, 1024)))), $decimals) . $sizes[$i]);
        }
    }

}