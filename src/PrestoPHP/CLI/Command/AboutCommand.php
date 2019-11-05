<?php
/*
 * This file is part of the PrestoPHP framework.
 *
 * (c) Gunnar Beushausen <gunnar@prestophp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PrestoPHP\Framework\CLI\Command;

use PrestoPHP\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AboutCommand extends AbstractCommand {
	private $appVersion;

	public function __construct() {
		parent::__construct();

		$this->appVersion = Application::VERSION;
	}

	protected function configure() {
		$this->setName("about")
			->setDescription("PrestoPHP CLI help");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$commandHelp = <<<COMMAND_HELP
PrestoPHP CLI %s
%s

This is the official CLI for the PrestoPHP Framework.

To create a new project called <info>shop</info> in the current directory using 
the <info>latest stable version</info>, execute the following command:

<comment>%s new shop</comment>

COMMAND_HELP;

		$commandUpdateHelp = <<<COMMAND_UPDATE_HELP

Updating the PrestoPHP CLI
-----------------------------------

New versions of the PrestoPHP CLI are released regularly. To <info>udate your
CLI</info> version, execute the following command

<comment>%3\$s self-update</comment>

COMMAND_UPDATE_HELP;

		$commandHelp .= $commandUpdateHelp;

		$output->writeln(sprintf($commandHelp,
			$this->appVersion,
			str_repeat('=', 23 + strlen($this->appVersion)),
			$this->getExecutableInfo()
		));

	}

	private function getExecutableInfo() {
		$pathDirs = explode(PATH_SEPARATOR, $_SERVER['PATH']);
		$executable = $_SERVER['PHP_SELF'];
		$executableDir = dirname($executable);

		if (in_array($executableDir, $pathDirs)) {
			$executable = basename($executable);
		}

		return $executable;
	}


}
