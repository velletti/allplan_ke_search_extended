<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Lib\Pluginbase;

class PageBrowserHook
{

	/**
	 * Modify page browser (not in use at the moment)
	 * @param array $markerArray
	 * @param Pluginbase $params
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function pagebrowseAdditionalMarker(array &$markerArray, Pluginbase &$params)
	{

		$pageBrowser = '';

		$li = [];
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

			$pageBrowser.= implode(PHP_EOL, $li);

			$pageBrowser.= $liNext . PHP_EOL;
			$pageBrowser.= '</ul>' . PHP_EOL;
		}

		$markerArray['pages_list'] = $pageBrowser;

	}

}