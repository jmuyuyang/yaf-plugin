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
            "post-package-update" => array(
                array('onPackageUpdate', 0)
            ),
            "post-install-cmd" => array(
                array("onComposerUpdate",0)
            ),
            "post-update-cmd" => array(
                array("onComposerUpdate",0)
            )
        );
    }

    public function onPackageUpdate(PackageEvent $event)
    {
	    var_dump($event->getOperation()->getPackage());
    }


    public function onComposerUpdate(Event $event){
        $vendorDir = $event->getConfig()->getVendorDir();
        $libraryDir = "library";
        foreach($this->_updatePackages as $packageName => $autoloadInfo) {
            $packageName = str_replace("\\", "/", $packageName);
            $basePackageDir = $vendorDir . "/" . $packageName;
            if (!is_dir($libraryDir . "/" . $packageName)) {
                mkdir($libraryDir . "/" . $packageName, 0755, true);
            }
            foreach ($autoloadInfo as $namespace => $dir) {
                $namespace = str_replace("\\", "/", $namespace);
                $packageDir = $basePackageDir . "/" . $dir;
                if (is_dir($dir . "/" . $namespace)) {
                    $packageDir = $packageDir . "/" . $namespace;
                }
                $cmd = sprintf("cp -r %s/* %s",$packageDir,$libraryDir.$namespace);
                var_dump($cmd);
            }
        }
    }
}
