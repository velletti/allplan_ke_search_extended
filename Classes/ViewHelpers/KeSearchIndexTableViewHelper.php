<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\FormatUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Lib\Db;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3Fluid
 */
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

// Todo cleanup
class KeSearchIndexTableViewHelper extends AbstractViewHelper
{

	/**
	 * Render
	 * @return string
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function render(): string
	{

		$table = 'tx_kesearch_index';

        // get table status
        $databaseConnection = Db::getDatabaseConnection($table);
        $tableStatusQuery = 'SHOW TABLE STATUS';
        $tableStatusRows = $databaseConnection->fetchAll($tableStatusQuery);

        ########### print_r($tableStatusRows);


        $content = '';

        foreach ($tableStatusRows as $row) {
            if ($row['Name'] == $table) {
                $dataLength = FormatUtility::formatFilesize($row['Data_length']);
                $indexLength = FormatUtility::formatFilesize($row['Index_length']);
                $completeLength = FormatUtility::formatFilesize($row['Data_length'] + $row['Index_length']);

                $content .= '<h2>Allplan Ke-Search extended</h2>
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
     * Returns the number of records per type in an array
     * @return array
     */
    public function getNumberOfRecordsInIndexPerType(): array
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

        try{
			return $typeCount->fetchAllAssociative();
		}catch(DoctrineDBALDriverException $e){
        	return [];
		}
    }

}