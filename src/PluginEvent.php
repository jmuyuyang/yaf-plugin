<?php
namespace yuyang\yafPlugin;

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

class PluginEvent implements PluginInterface,EventSubscriberInterface{
	protected $composer;
	protected $io;

	public function acctivate(Composer $composer,IOInterface $io){
		$this->composer = $composer;
		$this->io = $io;
	}

	 public static function getSubscribedEvents()
  {
      return array(
          "post-package-install" => array(
              array('onPackageInstall', 0)
          ),
          "post-package-update" => array(
              array('onPackageUpdate', 0)
          ),
      );
  }
	public function onPackageInstall(PackageEvent $event){
		var_dump($event);
	}

	public function onPackageUpdate(PackageEvent $event){
		var_dump($event);
	}
}
