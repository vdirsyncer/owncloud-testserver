<?php

/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Owncloud\Updater\Controller;

use League\Plates\Extension\URI;
use Owncloud\Updater\Utils\Checkpoint;
use Owncloud\Updater\Utils\ConfigReader;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Owncloud\Updater\Formatter\HtmlOutputFormatter;
use Owncloud\Updater\Http\Request;
use League\Plates\Engine;
use League\Plates\Extension\Asset;

class IndexController {

	/** @var \Pimple\Container */
	protected $container;

	/** @var Request */
	protected $request;

	/** @var string $command */
	protected $command;

	/**
	 * @param \Pimple\Container $container
	 * @param Request|null $request
	 */
	public function __construct(\Pimple\Container $container,
								Request $request = null) {
		$this->container = $container;
		if (is_null($request)){
			$this->request = new Request(['post' => $_POST, 'headers' => $_SERVER]);
		} else {
			$this->request = $request;
		}

		$this->command = $this->request->postParameter('command');
	}

	public function dispatch() {
		// strip index.php and query string (if any) to get a real base url
		$baseUrl = preg_replace('/(index\.php.*|\?.*)$/', '', $_SERVER['REQUEST_URI']);
		$templates = new Engine(CURRENT_DIR . '/src/Resources/views/');
		$templates->loadExtension(new Asset(CURRENT_DIR . '/pub/', false));
		$templates->loadExtension(new URI($baseUrl));

		// Check if the user is logged-in
		if(!$this->isLoggedIn()) {
			return $this->showLogin($templates);
		}

		if (is_null($this->command)){
			/** @var Checkpoint $checkpoint */
			$checkpoint = $this->container['utils.checkpoint'];
			$checkpoints = $checkpoint->getAll();
			$content = $templates->render(
					'partials/inner',
					[
						'title' => 'Updater',
						'version' => $this->container['application']->getVersion(),
						'checkpoints' => $checkpoints
					]
			);
		} else {
			header('Content-Type: application/json');
			$content = json_encode($this->ajaxAction(), JSON_UNESCAPED_SLASHES);
		}
		return $content;
	}

	protected function isLoggedIn() {
		/** @var ConfigReader $configReader */
		$configReader = $this->container['utils.configReader'];
		$configReader->init();
		$storedSecret = isset($configReader->get(['system'])['updater.secret']) ? $configReader->get(['system'])['updater.secret'] : null;
		if(is_null($storedSecret)) {
			die('updater.secret is undefined in config/config.php. Either browse the admin settings in your ownCloud and click "Open updater" or define a strong secret using <pre>php -r \'echo password_hash("MyStrongSecretDoUseYourOwn!", PASSWORD_DEFAULT)."\n";\'</pre> and set this in the config.php.');
		}
		$sentAuthHeader = ($this->request->header('X_Updater_Auth') !== null) ? $this->request->header('X_Updater_Auth') : '';

		if(password_verify($sentAuthHeader, $storedSecret)) {
			return true;
		}

		return false;
	}

	public function showLogin(Engine $templates) {
		// If it is a request with invalid token just return "false" so that we can catch this
		$token = ($this->request->header('X_Updater_Auth') !== null) ? $this->request->header('X_Updater_Auth') : '';
		if($token !== '') {
			return 'false';
		}

		$content = $templates->render(
			'partials/login',
			[
				'title' => 'Login Required',
			]
		);
		return $content;
	}

	public function ajaxAction() {
		$application = $this->container['application'];

		$input = new StringInput($this->command);
		$input->setInteractive(false);

		$output = new BufferedOutput();
		$formatter = $output->getFormatter();
		$formatter->setDecorated(true);
		$output->setFormatter(new HtmlOutputFormatter($formatter));

		$application->setAutoExit(false);
		// Some commands  dump things out instead of returning a value
		ob_start();
		$errorCode = $application->run($input, $output);
		if (!$result = $output->fetch()){
			$result = ob_get_contents(); // If empty, replace it by the catched output
		}
		ob_end_clean();
		$result = nl2br($result);
		$result = preg_replace('|<br />\r.*<br />(\r.*?)<br />|', '$1<br />', $result);

		return [
			'input' => $this->command,
			'output' => $result,
			'environment' => '',
			'error_code' => $errorCode
		];
	}

}
