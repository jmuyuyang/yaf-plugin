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

    protected $_updatePackages = [];
    protected $_uninstallPackages = [];
    protected $_ignorePackages = [];

    const SELF_PLUGIN_NAME = "yuyang/yaf-plugin";

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->_initIgnorePackageList();
    }

    public static function getSubscribedEvents()
    {
        return array(
            "post-package-install"   => array(
                array('onPackageUpdate', 0),
            ),
            "post-package-update"    => array(
                array('onPackageUpdate', 0),
            ),
            "post-package-uninstall" => array(
                array("onPackageUninstall", 0),
            ),
            "post-install-cmd"       => array(
                array("onComposerUpdate", 0),
            ),
            "post-update-cmd"        => array(
                array("onComposerUpdate", 0),
            ),
        );
    }

    /**
     * 获取忽略关联的包
     */
    protected function _initIgnorePackageList()
    {
        $composeJsonFile = dirname(
                $this->composer->getConfig()->get("vendor-dir")
            )."/composer.json";
        $composerConfig = @file_get_contents($composeJsonFile);
        if ($composerConfig) {
            $composerConfig = json_decode($composerConfig, true);
            if ($composerConfig
                && isset($composerConfig["yaf_package_ignore"])
            ) {
                $this->_ignorePackages = $composerConfig["yaf_package_ignore"];
            }
        }
    }

    public function onPackageUpdate(PackageEvent $event)
    {
        $operation = $event->getOperation();
        $package = null;
        if ($operation instanceof
            \Composer\DependencyResolver\Operation\InstallOperation
        ) {
            $package = $operation->getPackage();
        }
        if ($operation instanceof
            \Composer\DependencyResolver\Operation\UpdateOperation
        ) {
            $package = $operation->getTargetPackage();
        }
        if ($package) {
            $packageName = $package->getName();
            if (in_array($packageName, $this->_ignorePackages)) {
                //忽略关联
                return;
            }
            if ($packageName != self::SELF_PLUGIN_NAME) {
                $autoload = $package->getAutoload();
                if (isset($autoload['psr-4'])) {
                    $this->_updatePackages[$packageName] = $autoload['psr-4'];
                }
            }
        }
    }

    public function onPackageUninstall(PackageEvent $event)
    {
        $operation = $event->getOperation();
        if ($operation instanceof
            \Composer\DependencyResolver\Operation\UninstallOperation
        ) {
            $package = $operation->getPackage();
            if ($package) {
                $packageName = $package->getName();
                if (in_array($packageName, $this->_ignorePackages)) {
                    //忽略关联
                    return;
                }
                if ($packageName != self::SELF_PLUGIN_NAME) {
                    $autoload = $package->getAutoload();
                    if (isset($autoload['psr-4'])) {
                        $this->_uninstallPackages[$packageName]
                            = $autoload['psr-4'];
                    }
                }
            }
        }
    }

    public function onComposerUpdate(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get("vendor-dir");
        $libraryDir = dirname($vendorDir)."/"."library";
        if (!is_dir($libraryDir)) {
            @mkdir($libraryDir, 0755);
        }
        if ($this->_updatePackages) {
            foreach ($this->_updatePackages as $packageName => $autoloadInfo) {
                $packageName = str_replace("\\", "/", $packageName);
                $basePackageDir = $vendorDir."/".$packageName;
                foreach ($autoloadInfo as $namespace => $dir) {
                    $namespace = str_replace("\\", "/", $namespace);
                    $packageDir = $basePackageDir."/".$dir;
                    if (is_dir($packageDir."/".$namespace)) {
                        $packageDir = $packageDir."/".$namespace;
                    }
                    $yafPackageDir = $libraryDir."/".$namespace;
                    $cmd = sprintf(
                        "rm -rf %s && mkdir %s && cp -r %s/* %s",
                        $yafPackageDir, $yafPackageDir, $packageDir,
                        $yafPackageDir
                    );
                    exec($cmd, $output, $return);
                    if ($return != 0) {
                        $this->io->writeError($output);
                        $this->io->writeError(
                            "failed to associate composer package "
                            .$packageName
                        );
                    } else {
                        $this->io->write(
                            "associate composer package ".$packageName
                            ." with yaf library"
                        );
                    }
                }
            }
        }
        if ($this->_uninstallPackages) {
            foreach ($this->_uninstallPackages as $packageName => $autoloadInfo) {
                foreach ($autoloadInfo as $namespace => $dir) {
                    $namespace = str_replace("\\", "/", $namespace);
                    $yafPackageDir = $libraryDir."/".$namespace;
                    $cmd = sprintf("rm -rf %s", $yafPackageDir);
                    exec($cmd, $output, $return);
                    if ($return != 0) {
                        $this->io->writeError($output);
                    } else {
                        $this->io->write(
                            "uninstall associate yaf library ".$packageName
                        );
                    }
                }
            }
        }
    }
}
