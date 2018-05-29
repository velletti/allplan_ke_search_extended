<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;


class KeSearchPageBrowserHook{

	public function OLD_XXX_pagebrowseAdditionalMarker(&$markerArray, &$params){

		// print_r($markerArray);
		// die();

		$pageBrowser = '';

		$li = array();
		$liPrevious = '';
		$liNext = '';

		if(isset($markerArray['links']) && is_array($markerArray['links'])){

			foreach($markerArray['links'] as $key => $link){

				// « Previous
				if($key === 'previous'){

					// Replace "previous" word with «
					$link = preg_replace('#<a href="(.*)"(.*)>(.*)</a>#siU', '<a href="$1">«</a>', $link);
					$liPrevious = '<li class="previous">' . $link . '</li>';

					// Next »
				}elseif($key === 'next'){

					// Replace word "next" with »
					$link = preg_replace('#<a href="(.*)"(.*)>(.*)</a>#siU', '<a href="$1">»</a>', $link);
					$liNext = '<li class="next">' . $link . '</li>';

					// Default
				} else{

					$class = '';
					if(preg_match('#class=\"current\"#siU', $link)){
						$class = ' class="active current"';
					}

					$li[] = '<li' . $class . '>' . $link . '</li>';


				}


			}

		}


		if(!empty($li)){

			$pageBrowser.= '<ul class="pagination pull-right">' . PHP_EOL;
			$pageBrowser.= $liPrevious . PHP_EOL;

			// If the current page is higher than the first one, show a link to the first page (after previous link)
			#if(isset($markerArray['current']) && intval($markerArray['current']) > 1){
			#	$pageBrowser.= '<li><a href="' . $this->getLinkForPage(1) . '">1 XXXXXXXXXXXXXXX</a></li>' . PHP_EOL;
			#}

			$pageBrowser.= implode(PHP_EOL, $li);

			$pageBrowser.= $liNext . PHP_EOL;
			$pageBrowser.= '</ul>' . PHP_EOL;
		}

		$markerArray['pages_list'] = $pageBrowser;


	}


	private function XXXXXgetLinkForPage($page){

		/**
		 * @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager
		 * @var $uriBuilder \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
		 */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$uriBuilder = $objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);

		$gp = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_kesearch_pi1');
		$sword = $gp['sword'];
		$sword = str_replace('"', '', $sword);
		$sword = str_replace("'", '', $sword);
		$sword = addslashes($sword);

		$uriBuilder->reset();
		$uriBuilder->setArguments([
				'tx_kesearch_pi1' => [
					'page' => $page,
					'sword' => $sword
				]
			]
		);


		return $uriBuilder->build();


	}


}