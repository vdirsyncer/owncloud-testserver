<?php
/**
 * @author Thomas Tanghus
 * @copyright 2013-2014 Thomas Tanghus (thomas@tanghus.net)
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts\Controller;

use OCA\Contacts\App,
	OCP\AppFramework\Controller,
	OCA\Contacts\Utils\Properties,
	OCA\Contacts\ImportManager,
	OCP\AppFramework\Http\TemplateResponse;


/**
 * Controller class for groups/categories
 */
class PageController extends Controller {

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		\OC::$server->getNavigationManager()->setActiveEntry($this->appName);

		$importManager = new ImportManager();
		$imppTypes = Properties::getTypesForProperty('IMPP');
		$adrTypes = Properties::getTypesForProperty('ADR');
		$phoneTypes = Properties::getTypesForProperty('TEL');
		$emailTypes = Properties::getTypesForProperty('EMAIL');
		$cloudTypes = Properties::getTypesForProperty('CLOUD');
		$ims = Properties::getIMOptions();
		$imProtocols = array();
		foreach($ims as $name => $values) {
			$imProtocols[$name] = $values['displayname'];
		}

		$maxUploadFilesize = \OCP\Util::maxUploadFilesize('/');

		\OCP\Util::addScript('placeholder', null);
		\OCP\Util::addScript('../vendor/blueimp-md5/js/md5', null);
		\OCP\Util::addScript('jquery.avatar', null);
		\OCP\Util::addScript('avatar', null);

		$response = new TemplateResponse($this->appName, 'contacts');
		$response->setParams(array(
			'uploadMaxFilesize' => $maxUploadFilesize,
			'uploadMaxHumanFilesize' => \OCP\Util::humanFileSize($maxUploadFilesize),
			'phoneTypes' => $phoneTypes,
			'emailTypes' => $emailTypes,
			'cloudTypes' => $cloudTypes,
			'adrTypes' => $adrTypes,
			'imppTypes' => $imppTypes,
			'imProtocols' => $imProtocols,
			'importManager' => $importManager,
		));

		return $response;
	}
}
