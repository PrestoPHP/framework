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

use PrestoPHP\Framework\CLI\Exception\AbortException;
use PrestoPHP\Framework\CLI\Manager\ComposerManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends DownloadCommand
{

    protected function configure()
    {
        $this->setName("new")
            ->setDescription("Creates a new InnoCommerce project.")
            ->addArgument("directory", InputArgument::REQUIRED, "Directory where the new project will be created.")
            ->addArgument("version", InputArgument::OPTIONAL, "The InnoCommerce version to be installed (default is 'latest')");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $directory = rtrim(trim($input->getArgument('directory')), DIRECTORY_SEPARATOR);
        $this->version = trim($input->getArgument('version'));
        if ($this->version === '') { $this->version = "latest";
        }
        $this->projectDir = $this->fs->isAbsolutePath($directory) ? $directory : getcwd() . DIRECTORY_SEPARATOR . $directory;
        $this->projectName = basename($directory);

        $this->composerManager = new ComposerManager($this->projectDir);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this
                ->checkInstallerVersion()
                ->checkProjectName()
                ->checkPermissions()
                ->download()
                ->extract()
                ->cleanUp()
                ->dumpReadmeFile()
                ->updateComposerConfig()
                ->displayInstallationResult();
        } catch (AbortException $e) {
            aborted:

            $output->writeln('\n');
            $output->writeln("<error>Aborting download and cleaning up temorary directories.</error>");

            $this->cleanUp();
        } catch (\Exception $e) {
            if ($e->getPrevious() instanceof AbortException) { goto aborted;
            }
            $this->cleanUp();

            throw $e;
        }
    }

    protected function cleanUp()
    {
        $this->fs->remove(dirname($this->downloadedFilePath));

        try {
            $licenseFile = array($this->projectDir . '/LICENSE');
            $upgradeFiles = glob($this->projectDir . '/UPGRADE*.md');
            $changelogFiles = glob($this->projectDir . '/CHANGELOG*.md');

            $filesToRemove = array_merge($licenseFile, $upgradeFiles, $changelogFiles);
            $this->fs->remove($filesToRemove);
        } catch (\Exception $e) {

        }

        return $this;
    }

    protected function dumpReadmeFile()
    {
        $readmeContents = sprintf("%s\n%s\n\nAn InnoCommerce eCommerce project created on %s.\n", $this->projectName, str_repeat('=', strlen($this->projectName)), date('F j, Y, g:i a'));
        try {
            $this->fs->dumpFile($this->projectDir . '/README.md', $readmeContents);
        } catch (\Exception $exception) {
        }

        return $this;
    }

    protected function updateComposerConfig()
    {
        parent::updateComposerConfig();
        $this->composerManager->updateProjectConfig(
            [
            'name' => $this->composerManager->createPackageName($this->projectName),
            'license' => 'proprietary',
            'description' => null,
            'extra' => ['branch-alias' => null],
            ]
        );

        return $this;
    }

    protected function displayInstallationResult()
    {
        $this->output->writeln(
            sprintf(
                "<info>InnoCommerce eCommerce Framework %s was successfully installed</info>\n",
                $this->latestVersion
            )
        );
    }

    protected function getRemoteFileUrl()
    {
        $version = $this->version == "latest" ? $this->latestVersion : $this->version;
        return "http://get.innocommerce.io/innocommerce-{$version}";
    }

}