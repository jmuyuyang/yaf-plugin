<?php
namespace yuyang\yafplugin;

use Composer\Script\Event;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
#Subscribe to Package events
use Composer\Installer\PackageEvent;
#For asking package about it's details
use Composer\Package\PackageInterface;
#Default installer
use Composer\Installer\LibraryInstaller;
#Hook in composer/installers for asking custom paths
use Composer\Installers\Installer;

class PluginEvent implements PluginInterface, EventSubscriberInterface
{
    protected $composer;
    protected $io;

    protected $_updatePackages = array();

    const SELF_PLUGIN_NAME = "yuyang/yaf-plugin";

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            "post-package-install" => array(
                array('onPackageUpdate', 0)
            ),
            "post-package-update"  => array(
                array('onPackageUpdate', 0)
            ),
            "post-install-cmd"     => array(
                array("onComposerUpdate", 0)
            ),
            "post-update-cmd"      => array(
                array("onComposerUpdate", 0)
            )
        );
    }

    public function onPackageUpdate(PackageEvent $event)
    {
        $operation = $event->getOperation();
        $package = null;
        if ($operation instanceof \Composer\DependencyResolver\Operation\InstallOperation) {
            $package = $operation->getPackage();
        }
        if ($operation instanceof \Composer\DependencyResolver\Operation\UpdateOperation) {
            $package = $operation->getTargetPackage();
        }
        if($package) {
            $packageName = $package->getName();
            if($packageName != self::SELF_PLUGIN_NAME) {
                $autoload = $package->getAutoload();
                if (isset($autoload['psr-4'])) {
                    $this->_updatePackages[$packageName] = $autoload['psr-4'];
                }
            }
        }
    }


    public function onComposerUpdate(Event $event)
    {
        if ($this->_updatePackages) {
            $vendorDir = $event->getComposer()->getConfig()->get("vendor-dir");
            $libraryDir = dirname($vendorDir) . "/" . "library";
            foreach ($this->_updatePackages as $packageName => $autoloadInfo) {
                $packageName = str_replace("\\", "/", $packageName);
                $basePackageDir = $vendorDir . "/" . $packageName;
                foreach ($autoloadInfo as $namespace => $dir) {
                    $namespace = str_replace("\\", "/", $namespace);
                    $packageDir = $basePackageDir . "/" . $dir;
                    if (is_dir($dir . "/" . $namespace)) {
                        $packageDir = $packageDir . "/" . $namespace;
                    }
                    if (!is_dir($libraryDir . "/" . $namespace)) {
                        mkdir($libraryDir . "/" . $namespace, 0755, true);
                    }
                    $cmd = sprintf("cp -r %s/* %s", $packageDir, $libraryDir . "/" . $namespace);
                    exec($cmd);
                    $this->io->write("associate composer package " . $packageName . " with yaf library");
                }
            }
        }
    }
}
