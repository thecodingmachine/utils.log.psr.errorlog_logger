<?php
/*
 * Copyright (c) 2013 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Utils\Log\Psr;

use Mouf\Installer\PackageInstallerInterface;
use Mouf\MoufManager;

/**
 * A logger class that writes messages into the php error_log.
 */
class ErrorLogLoggerInstaller implements PackageInstallerInterface {

	/**
	 * (non-PHPdoc)
	 * @see \Mouf\Installer\PackageInstallerInterface::install()
	 */
	public static function install(MoufManager $moufManager) {
		if (!$moufManager->instanceExists("psr.errorLogLogger")) {
		
			$errorLogLogger = $moufManager->createInstance("Mouf\\Utils\\Log\\Psr\\ErrorLogLogger");
			// Let's set a name for this instance (otherwise, it would be anonymous)
			$errorLogLogger->setName("psr.errorLogLogger");
			$errorLogLogger->getProperty("level")->setValue('warning');
		}
		
		// Let's rewrite the MoufComponents.php file to save the component
		$moufManager->rewriteMouf();
	}
}
