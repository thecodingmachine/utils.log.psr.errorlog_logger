<?php
/*
 * Copyright (c) 2012 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */

require_once __DIR__."/../../../autoload.php";

use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();
if (!$moufManager->instanceExists("psr.errorLogLogger")) {
	
	$errorLogLogger = $moufManager->createInstance("Mouf\\Utils\\Log\\Psr\\ErrorLogLogger");
	// Let's set a name for this instance (otherwise, it would be anonymous)
	$errorLogLogger->setName("psr.errorLogLogger");
	$errorLogLogger->getProperty("level")->setValue('warning');
}

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall();
