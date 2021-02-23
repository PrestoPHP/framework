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

use Distill\Distill;
use Distill\Exception\CorruptedFileException;
use Distill\Exception\IO\Input\FileCorruptedException;
use Distill\Exception\IO\Input\FileEmptyException;
use Distill\Exception\IO\Output\TargetDirectoryNotWritableException;
use Distill\Strategy\MinimumSize;
use GuzzleHttp\Client;
use GuzzleHttp\Event\ProgressEvent;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Utils;
use PrestoPHP\Framework\CLI\Application;
use PrestoPHP\Framework\CLI\Exception\AbortException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

abstract class DownloadCommand extends AbstractCommand
{
    protected $output;
    protected $composerManager;
    protected $version = 'latest';
    protected $latestVersion;
    protected $localVersion;
    protected $downloadedFilePath;
    protected $projectDir;
    protected $projectName;

    abstract protected function getRemoteFileUrl();

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->output = $output;

        $this->latestVersion = $this->getUrlContents(Application::VERSION_URL);
        $this->localVersion = $this->getApplication()->getVersion();

        $this->enableSignalHandler();
    }

    protected function download()
    {
        $this->output->writeln(sprintf("\n Downloading InnoCommerce Framework...\n"));
        $distill = new Distill();
        $icArchiveFile = $distill
            ->getChooser()
            ->setStrategy(new MinimumSize())
            ->addFilesWithDifferentExtensions($this->getRemoteFileUrl(), ['zip'])
            ->getPreferredFile();

        $progressBar = null;
        $downloadCallback = function (ProgressEvent $event) use (&$progressBar) {
            $downloadSize = $event->downloadSize;
            $downloaded = $event->downloaded;

            if ($downloadSize < 1 * 1024 * 1024) { return;
            }

            if ($progressBar === null) {
                ProgressBar::setPlaceholderFormatterDefinition(
                    'max', function (ProgressBar $bar) {
                        return Helper::formatMemory($bar->getMaxSteps());
                    }
                );
                ProgressBar::setPlaceholderFormatterDefinition(
                    'current', function (ProgressBar $bar) {
                        return str_pad(Helper::formatMemory($bar->getProgress()), 11, ' ', STR_PAD_LEFT);
                    }
                );

                $progressBar = new ProgressBar($this->output, $downloadSize);
                $progressBar->setFormat("%current%/%max% %bar% %percent:3s%%");
                $progressBar->setRedrawFrequency(max(1, floor($downloadSize / 1000)));
                $progressBar->setBarWidth(60);

                if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
                           $progressBar->setEmptyBarCharacter('░');
                           $progressBar->setProgressCharacter('');
                           $progressBar->setBarCharacter('▓');
                }

                $progressBar->start();
            }
            $progressBar->setProgress($downloaded);
        };

        $client = $this->getGuzzleClient();

        $this->downloadedFilePath = rtrim(getcwd(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.' . uniqid(time()) . DIRECTORY_SEPARATOR . 'innocommerce.' . pathinfo($icArchiveFile, PATHINFO_EXTENSION);

        try {
            $request = $client->createRequest('GET', $icArchiveFile);
            $request->getEmitter()->on('progress', $downloadCallback);
            $response = $client->send($request);
        } catch (ClientException $e) {
            if ($this->getName() === 'new' && ($e->getCode() === 403 || $e->getCode() === 404)) {
                throw new \RuntimeException(
                    sprintf(
                        "The selected version (%s) cannot be installed because it does not exist.\n" .
                        "Ececute the following command to install the latest stable release\n" .
                        "%s new %s",
                        $this->version,
                        $_SERVER['PHP_SELF'],
                        str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $this->projectDir)
                    )
                );
            } else {
                throw new \RuntimeException(
                    sprintf(
                        "There was an error downloading InnoCommerce Framework:\n%s",
                        $e->getMessage()
                    ), null, $e
                );
            }
        }

        $this->fs->dumpFile($this->downloadedFilePath, $response->getBody());

        if (null !== $progressBar) {
            $progressBar->finish();
            $this->output->writeln("\n");
        }

        return $this;
    }

    protected function checkInstallerVersion()
    {
        if (substr(__DIR__, 0, 7) !== 'phar://') { return $this;
        }

        if (!$this->isInstallerUpToDate()) {
            $this->output->writeln(
                sprintf(
                    "\n <error>WARNING</error> Your InnoCommerce Installer version (%s) is outdated. \n" .
                    " Execute the command \"%s selfupdate\" to get the latest version (%s). ",
                    $this->localVersion, $_SERVER['PHP_SELF'], $this->latestVersion
                )
            );
        }

        return $this;
    }

    protected function isInstallerUpToDate()
    {
        return version_compare($this->localVersion, $this->latestVersion, '>=');
    }

    protected function checkProjectName()
    {
        if (is_dir($this->projectDir) && !$this->isDirectoryEmtpy($this->projectDir)) {
            throw new \RuntimeException(
                sprintf(
                    "There is already a '%s' project int this directory (%s).\n" .
                    "Change your project name or create it in another directory.",
                    $this->projectName, $this->projectDir
                )
            );
        }

        return $this;
    }

    protected function isDirectoryEmtpy($directory)
    {
        return 2 === count(scandir($directory . '/'));
    }

    protected function checkPermissions()
    {
        $projectRootDirectory = dirname($this->projectDir);

        if (!is_writable($projectRootDirectory)) {
            throw new IOException(sprintf("The Installer does not have enough permissions to write to the \"%s\" directory!", $projectRootDirectory));
        }

        return $this;
    }

    protected function extract()
    {
        $this->output->writeln(" Preparing project...\n");

        try {
            $distill = new Distill();
            $extractionSucceeded = $distill->extractWithoutRootDirectory($this->downloadedFilePath, $this->projectDir);
        } catch (FileCorruptedException $e) {
            throw new \RuntimeException(
                sprintf(
                    "InnoCommerce can't be installed because the downloaded package is corrupted.\n" .
                    "To solve this issue, try executing the command again:\n"
                )
            );
        } catch (FileEmptyException $e) {
            throw new \RuntimeException(
                sprintf(
                    "InnoCommerce can't be installed because the downloaded package is empty.\n" .
                    "To solve this issue, try executing the command again:\n"
                )
            );
        } catch (TargetDirectoryNotWritableException $e) {
            throw new \RuntimeException(
                sprintf(
                    "InnoCommerce can't be installed because the Installer doesn't have enough.\n" .
                    "permissions to uncompress and rename the package contents.\n" .
                    "To solve this issue, check the permissions and try executing the command again:\n"
                )
            );
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf(
                    "InnoCommerce can't be installed because the an error occured.\n" .
                    "Please check permissions. Original error message was:\n\n%s", $e->getMessage()
                ), null, $e
            );
        }

        if (!$extractionSucceeded) {
            throw new \RuntimeException(
                sprintf(
                    "InnoCommerce can't be installed because the downloaded package is corrupted\n" .
                    "or because the uncompress commands of your operating system didn't work\n"
                )
            );
        }

        return $this;
    }

    protected function updateComposerConfig()
    {
        $this->composerManager->initializeProjectConfig();

        return $this;
    }

    protected function getGuzzleClient()
    {
        $defaults = [];

        //See if client is behind a proxy
        if (!empty($_SERVER['HTTP_PROXY']) || !empty($_SERVER['http_proxy'])) {
            $defaults['proxy'] = !empty($_SERVER['http_proxy']) ? $_SERVER['http_proxy'] : $_SERVER['HTTP_PROXY'];
        }

        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
            $defaults['debug'] = true;
        }

        try {
            $handler = Utils::getDefaultHandler();
        } catch (\RuntimeException $e) {
            throw new \RuntimeException("The InnoCommerce installer requires the php-curl extension or the allow_url_fopen ini setting to be turned on");
        }

        return new Client(['defaults' => $defaults, 'handler' => $handler]);

    }

    protected function getUrlContents($url)
    {
        $client = $this->getGuzzleClient();

        return json_decode($client->get($url)->getBody()->getContents());
    }

    protected function enableSignalHandler()
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        declare(ticks=1);

        pcntl_signal(
            SIGINT, function () {
                error_reporting(0);

                throw new AbortException();
            }
        );
    }

}