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

use PrestoPHP\Framework\CLI\Command\AbstractCommand;
use PrestoPHP\Framework\CLI\Manager\ComposerManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class GenerateCommand extends AbstractCommand
{
    protected $basePath;
    protected $twig;

    protected function configure()
    {
        $this->setName("generate")
            ->setAliases(["g"])
            ->setDescription("Generate code")
            ->addArgument("type", InputArgument::REQUIRED, "What to generate (module, controller, factory) etc.")
            ->addArgument("name", InputArgument::REQUIRED, "Name of the generated entity")
            ->addArgument("module", InputArgument::OPTIONAL, "Name of the generated entity")
            ->addArgument("bundle", InputArgument::OPTIONAL, "Name of the generated entity");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->basePath = $this->getBasePath();
        $loader = new FilesystemLoader(realpath(__DIR__) . '/templates');
        $this->twig = new Environment(
            $loader, array(
            'cache' => false,
            )
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument("type");
        $name = $input->getArgument("name");
        $bundle = $input->getArgument("bundle");
        $module = $input->getArgument("module");

        switch ($type) {
        case 'module':
            $this->generateModule($name);
            break;
        case 'bundle':
            $this->generateBundle($module, $name);
            break;
        case 'controller':
            $this->generateController($module, $bundle, $name);
            break;
        default:
            $output->writeln("Please enter one of the following tyes: module, bundle, controller");
        }
    }

    protected function generateModule($name)
    {
        $this->fs->mkdir("$this->basePath/src/Code/" . ucfirst($name));
        $this->fs->mkdir("$this->basePath/src/Code/" . ucfirst($name) . "/Plugin");
        $this->fs->mkdir("$this->basePath/src/Code/" . ucfirst($name) . "/Plugin/Provider");
        $namespace = $this->getNamespace();
        $controllerProvider = $this->twig->render('module/ControllerProvider.php.twig', array('name' => $name, 'namespace' => $namespace, "module" => $name));
        $this->fs->dumpFile("$this->basePath/src/Code/" . ucfirst($name) . "/Plugin/Provider/" . ucfirst($name) . "ControllerProvider.php", $controllerProvider);
        $this->generateBundle($name, "Index");
        $this->generateController($name, "Index", "Index");
    }

    protected function generateBundle($module, $name)
    {
        $this->fs->mkdir("$this->basePath/src/Code/" . ucfirst($module) . "/" . ucfirst($name));
        $this->fs->mkdir("$this->basePath/src/views/" . ucfirst($name));

    }

    protected function generateController($module, $bundle, $name)
    {
        $namespace = $this->getNamespace();
        $this->fs->mkdir("$this->basePath/src/Code/" . ucfirst($module) . "/" . ucfirst($bundle) . "/Controller");
        $this->fs->mkdir("$this->basePath/src/views/" . ucfirst($bundle) . "/" . ucfirst($name));
        $controller = $this->twig->render('controller/Controller.php.twig', array('name' => $name, 'namespace' => $namespace, "module" => $module, "bundle" => $bundle));
        $this->fs->dumpFile("$this->basePath/src/Code/" . ucfirst($module) . "/" . ucfirst($bundle) . "/Controller/" . ucfirst($name) . "Controller.php", $controller);
        $this->fs->dumpFile("$this->basePath/src/views/" . ucfirst($bundle) . "/" . lcfirst($name) . "/index.twig", "");

    }

    protected function getNamespace()
    {
        $cm = new ComposerManager($this->basePath);
        $composerConfig = $cm->getProjectConfig();
        foreach ($composerConfig['autoload']['psr-4'] as $key => $value) {
            if ($value == "src/") { return $key;
            }
        }
        return false;
    }

    protected function getBasePath()
    {
        $path = getcwd();
        $path = explode('/', $path);
        $pos = array_search('www', $path) + 1;
        $result = [];
        for ($i = 0; $i <= $pos; $i++) {
            $result[] = $path[$i];
        }


        return implode('/', $result);
    }

}