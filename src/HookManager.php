<?php


namespace Smoren\Composer\Mushroom;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Exception;

class HookManager implements PluginInterface, EventSubscriberInterface
{
    const KEY_USE_HOOKS = 'mushroom-use-hooks';
    const KEY_HOOKS_LIST = 'mushroom-hooks';
    const KEY_HOOKS_PARAMS_LIST = 'mushroom-hooks-params';

    const PATH_PROJECT = __DIR__.'/../../../..';
    const PATH_AUTOLOAD = self::PATH_PROJECT.'/vendor/autoload.php';

    /**
     * @var Composer
     */
    protected $composer;
    /**
     * @var IOInterface
     */
    protected $io;
    /**
     * @var Package
     */
    protected $rootPackage;
    /**
     * @var array
     */
    protected $hooksToRun;

    /**
     * @return array[][]
     */
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => [
                ['onAfterPackageInstall', 0],
            ],
            PackageEvents::POST_PACKAGE_UPDATE => [
                ['onAfterPackageUpdate', 0],
            ],
            ScriptEvents::POST_INSTALL_CMD => [
                ['onFinish', 0]
            ],
            ScriptEvents::POST_UPDATE_CMD  => [
                ['onFinish', 0]
            ],
        ];
    }

    /**
     * @param PackageEvent $event
     * @throws Exception
     */
    public function onAfterPackageInstall(PackageEvent $event) {
        $this->collectHooks('after-install', $event->getOperation()->getPackage());
    }

    /**
     * @param PackageEvent $event
     * @throws Exception
     */
    public function onAfterPackageUpdate(PackageEvent $event) {
        $this->collectHooks('after-update', $event->getOperation()->getTargetPackage());
    }

    /**
     * @param Event $event
     * @throws Exception
     */
    public function onFinish(Event $event) {
        $hooksCount = count($this->hooksToRun);

        if(!(int)$hooksCount) {
            $this->printMessage("TOTAL no hooks found to execute.");
            return;
        }

        require self::PATH_AUTOLOAD;
        $this->printMessage("TOTAL {$hooksCount} hooks found! Now execute...");
        $this->runHooks();
        $this->printMessage("done!");
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->rootPackage = $composer->getPackage();
        $this->hooksToRun = [];
        $this->printMessage('activated');
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        $this->printMessage('deactivated');
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        $this->printMessage('uninstalled');
    }

    /**
     * @param string $eventName
     * @param Package $depPackage
     */
    protected function collectHooks(string $eventName, Package $depPackage)
    {
        $depPackageName = $depPackage->getName();
        $depPackageExtra = $depPackage->getExtra();
        $rootPackageExtra = $this->rootPackage->getExtra();

        if(!isset($depPackageExtra[self::KEY_USE_HOOKS]) || !$depPackageExtra[self::KEY_USE_HOOKS]) {
            return;
        }

        $hooks = $depPackageExtra[self::KEY_HOOKS_LIST][$eventName] ?? [];
        $scriptsCount = count($hooks);
        $this->printMessage("{$scriptsCount} hooks found!");

        foreach($hooks as $hook) {
            $params = $rootPackageExtra[self::KEY_HOOKS_PARAMS_LIST][$depPackageName][$eventName] ?? null;
            $this->hooksToRun[] = [$hook, $params];
        }
    }

    /**
     * @throws Exception
     */
    protected function runHooks()
    {
        foreach($this->hooksToRun as [$hook, $params]) {
            if(!is_callable($hook)) {
                $this->printMessage("\e[31m'{$hook}' is not callable!\e[39m");
                throw new Exception("mushroom hook manager error");
            }

            $hook($params);
        }
    }

    /**
     * @param string $message
     */
    protected function printMessage(string $message)
    {
        echo "\e[34m[ MushroomHookManager ]\e[39m {$message}\n";
    }
}
