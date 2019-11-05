<?php
/*
 * This file is part of the PrestoPHP framework.
 *
 * (c) Gunnar Beushausen <gunnar@prestophp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PrestoPHP\Framework\CLI;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends ConsoleApplication {
	const VERSION = "0.1.0";
	const VERSION_URL = "http://version.prestophp.com";

	public function doRun(InputInterface $input, OutputInterface $output) {
		if (PHP_VERSION_ID < 70000) {
			file_put_contents('php://stderr', sprintf(
				"PrestoPHP requires PHP 7.0 version or higher and your system has \n".
				"PHP %s version installed.\n\n".
				"To solve this issue, upgrade your PHP Installation\n\n",
				PHP_VERSION
			));

			exit(1);
		}

		if (extension_loaded('suhosin')) {
			file_put_contents('php://stderr', sprintf(
				"PrestoPHP is not compatible with the 'suhosin' PHP extension \n".
				"Please disable this extension before running the cli command\n\n"
			));

			exit(1);
		}

		if (extension_loaded('xdebug')) {
			$output->writeln(
				'<info>You are running PrestoPHP with xdebug enabled. This has a major impact on runtime performance.</info>'."\n"
			);
		}

		return parent::doRun($input, $output);
	}

	public function getVersion() {
		return self::VERSION;
	}

}