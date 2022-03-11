<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\LanguageUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3Fluid
 */

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

// Todo: check, if needed, when new faq indexer ready

class KeSearchDirectoryViewHelper extends AbstractViewHelper
{

	/**
	 *
	 * @var boolean
	 */
	protected bool $escapeOutput = false;

	/**
	 * Render
	 * @return string
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 */
	public function render(): string
	{
		try{
			$contentArray = $this->getCategories();
		}catch(DoctrineDBALDriverException $e){
			return 'Error: ' . $e;
		}
		return $contentArray['select1'] . $contentArray['select2'] . $contentArray['select3'] . '<br>';
	}

	/**
	 * Get the categories
	 * @return array
	 * @throws DoctrineDBALDriverException
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Pater Benke <pbenke@allplan.com>
	 */
	public function getCategories(): array
	{

		$contentSelect1 = '';
		$contentSelect2 = '';
		$contentSelect3 = '';

		$targetUrl = $this->getTargetUrl();
		$maxGroup = $this->getFeMaxGroup();

		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_index')->createQueryBuilder();

		/*
			we have entries in Database like :
			Allgemein
			Allgemeine Einstellungen\Architektur
			Allgemein\Projekt

			with  ->selectLiteral(' concat(replace(directory , "\\\\" , " \\\\") ,  " \\\\" ) as directory ')
			we get a correct sorted list (Allgemein and Allgemein\Projekt before   Allgemeine Einstellungen \Architektur
			Allgemein \
			Allgemein \Projekt
			Allgemeine Einstellungen \Architektur
		*/
		$rows = $queryBuilder
			->selectLiteral('CONCAT(REPLACE(directory , "\\\\" , " \\\\") ,  " \\\\" ) AS directory')
			->from('tx_kesearch_index')
			->where($queryBuilder->expr()->like('targetpid', $queryBuilder->createNamedParameter($targetUrl,Connection::PARAM_STR)))
			->andWhere($queryBuilder->expr()->neq('directory', $queryBuilder->createNamedParameter('',Connection::PARAM_STR)))
			->andWhere(
				$queryBuilder->expr()->orX(
					$queryBuilder->expr()->eq('fe_group', $queryBuilder->createNamedParameter('',Connection::PARAM_STR)),
					$queryBuilder->expr()->like('fe_group', $queryBuilder->createNamedParameter($maxGroup,Connection::PARAM_STR))
				)
			)
			->groupBy('directory')
			->orderBy('directory')
			->execute()->fetchAllAssociative();

		$directory = false;

		if($rows && count($rows) > 0){
			$params = GeneralUtility::_GET('tx_kesearch_pi1');
			if(is_array($params) && array_key_exists('directory', $params) && strlen($params['directory']) > 0){
				$directory = GeneralUtility::trimExplode("\\",trim(urldecode($params['directory'])));
			}
			$contentSelect1.= '<option></option>';
			$lastOption = '#-#';
			foreach ($rows as $row){
				$thisOption = $this->getOption($row['directory']);
				if($thisOption){
					if($thisOption != $lastOption){
						$selected = '';
						if($directory && $directory[0] == $thisOption){
							$selected = ' selected="selected" ';
						}
						$contentSelect1.= '<option value="' . urlencode($thisOption) . '" ' . $selected . '>' . $thisOption . ' </option>';
					}
					$lastOption =  $thisOption;
				}
			}

			// Todo simplify code with function

			if(is_array($directory) && count($directory) > 0){
				$lastOption = '#-#';
				foreach($rows as $row){
					if($directory && $directory[0]){
						$thisOption = $this->getOption($row['directory'],1, $directory);
						if($thisOption){
							if ($thisOption != $lastOption){
								$selected = '';
								if($directory[1] == $thisOption){
									$selected = ' selected="selected" ';
								}
								$contentSelect2 .= '<option value="' . urlencode($directory[0] . "\\" . $thisOption) . '" ' . $selected . '>' . $thisOption . ' </option>';
								$lastOption = $thisOption;
							}
						}
					}
				}
				if(is_array($directory) && count($directory) > 1){
					$lastOption = '#-#';
					foreach($rows as $row){
						if($directory && $directory[1]){
							$thisOption = $this->getOption($row['directory'],2, $directory);
							if($thisOption){
								if($thisOption != $lastOption){
									$selected = '';
									if($directory[2] == $thisOption){
										$selected = ' selected="selected" ';
									}
									$contentSelect3 .= '<option value="' . urlencode($directory[0] . "\\"  .$directory[1] . "\\" . $thisOption) . '" ' . $selected . '>' . $thisOption . ' </option>';
									$lastOption = $thisOption;
								}
							}
						}
					}
				}
			}
		}

		$lng = LanguageUtility::getSysLanguageUid();
		$pid = $GLOBALS['TSFE']->id;

		// Todo simplify code

		$content = [];
		if($contentSelect1){
			$content['select1'] = '<select name="tx_kesearch_pi1[directory1]" onchange="allplan_kesearch_change(this);return true;" data-pid="' . $pid . '" data-lng="' . $lng . '" data-level="1" data-cat="tx_kesearch_pi1[directory1]" class="form-control kesearch-directory kesearch-directory1">' . $contentSelect1 . '</select>';
		} else {
			$content['select1'] = '';
		}
		if($contentSelect2){
			$content['select2'] = '<select name="tx_kesearch_pi1[directory2]" onchange="allplan_kesearch_change(this);return true;" data-pid="' . $pid . '" data-lng="' . $lng . '" data-level="2" data-cat="tx_kesearch_pi1[directory2]" class="form-control kesearch-directory kesearch-directory2">'
				. "<option value=\"" . urlencode($directory[0] ) . "\"></option>"
				. $contentSelect2
				. '</select>';
		} else {
			$content['select2'] = '<br>';
		}
		if ($contentSelect3){
			$content['select3'] = '<select name="tx_kesearch_pi1[directory3]" onchange="allplan_kesearch_change(this);return true;" data-pid="' . $pid . '" data-lng="' . $lng . '" data-level="3" data-cat="tx_kesearch_pi1[directory3]" class="form-control kesearch-directory kesearch-directory3">'
				. "<option value=\"" . urlencode($directory[0] . "\\" . $directory[1] ) . "\"></option>"
				. $contentSelect3
				. '</select>';
		} else {
			$content['select3'] = '<br>';
		}

		return $content;

	}

	/**
	 * Get option for select box
	 * @param string $option
	 * @param int $level
	 * @param array|bool $replace
	 * @return string
	 */
	private function getOption(string $option, int $level=0, $replace = false): string
	{
		$options= GeneralUtility::trimExplode("\\", $option);
		if( $level == 0){
			return $options[0];
		}
		if(count($options ) > 0){
			if($replace[0] == $options[0] && $level == 1){
				return $options[1];
			}
		}
		if(count($options) > 1 && $replace && count($replace) > 1){
			if($replace[0] == $options[0] && $replace[1] == $options[1] && $level == 2){
				return  $options[2];
			}
		}
		return '';
	}

	/**
	 * Get the maximal fe user groups for select from current user
	 * @return string
	 */
	private function getFeMaxGroup(): string
	{
		if($GLOBALS['TSFE']->fe_user && is_array($GLOBALS['TSFE']->fe_user->user)){
			$usergroups = GeneralUtility::trimExplode(',', $GLOBALS['TSFE']->fe_user->user['usergroup']);
			if(in_array('38', $usergroups)){
				return '38%';
			}
			if(in_array("7", $usergroups)){
				return '38,7%';
			}
			if(in_array("4", $usergroups)){
				return '38,7,4%';
			}
			if(in_array("3", $usergroups)){
				return '38,7,4,3';
			}
		}
		return '';
	}

	/**
	 * Get the target url for select
	 * @return string
	 */
	private function getTargetUrl(): string
	{

		$lng = 0 ;
		if (GeneralUtility::_GP("L") && intval(GeneralUtility::_GP("L") > 0)) {
			$lng = GeneralUtility::_GP("L") ;
		}

		try{
			$languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
			if($lng == 0){
				$lng =  $languageAspect->getId() ;
			}
		}catch(AspectNotFoundException $e){
			// Nothing here
		}

		switch ($lng) {
			case 1:
			case 6:
			case 7:
				return 'https://connect.allplan.com/de%' ;
			case 2:
				return 'https://connect.allplan.com/it%' ;
			case 3:
				return 'https://connect.allplan.com/cz%' ;
			case 4:
				return 'https://connect.allplan.com/fr%' ;
			case 18:
				return 'https://connect.allplan.com/es%' ;
			case 14:
			default:
				return 'https://connect.allplan.com/en%';
		}

	}

}