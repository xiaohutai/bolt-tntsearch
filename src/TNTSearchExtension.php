<?php

namespace Bolt\Extension\TwoKings\TNTSearch;

use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Bolt\Extension\TwoKings\TNTSearch\Config\Config;
use Bolt\Extension\TwoKings\TNTSearch\Listener\KernelEventListener;
use Bolt\Extension\TwoKings\TNTSearch\Listener\StorageEventListener;
use Bolt\Extension\TwoKings\TNTSearch\Table\TNTSearchLookupTable;
use Bolt\Menu\MenuEntry;
use Bolt\Version as BoltVersion;
use Pimple as Container;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * TNTSearch class
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
class TNTSearchExtension extends SimpleExtension
{
    use DatabaseSchemaTrait;

    /** @var string $permission The permission a user needs for interaction with  the back-end */
    private $permission = 'settings';

    /**
     * {@inheritdoc}
     */
    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
        // https://docs.bolt.cm/extensions/essentials#adding-storage-events
        $storageEventListener = new StorageEventListener($this->getContainer());
        $dispatcher->addListener(StorageEvents::POST_SAVE, [$storageEventListener, 'onPostSave']);
        $dispatcher->addListener(StorageEvents::POST_DELETE, [$storageEventListener, 'onPostDelete']);

        $dispatcher->addSubscriber(new KernelEventListener($this->getContainer()['tntsearch.service']));
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        $prefix = '/extensions/';
        if (version_compare(BoltVersion::forComposer(), '3.3.0', '<')) {
            $prefix = '/extend/';
        }

        return [
            $prefix . 'tntsearch' => new Controller\TNTSearchController(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerMenuEntries()
    {
        $menuEntry = (new MenuEntry('tntsearch', 'tntsearch'))
            ->setLabel('TNTSearch')
            ->setIcon('fa:search')
            ->setPermission($this->permission)
        ;

        return [$menuEntry];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates' => [
                'position' => 'prepend',
                'namespace' => 'tntsearch'
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $this->extendDatabaseSchemaServices();

        $app['tntsearch.config'] = $app->share(function () { return new Config($this->getConfig()); });
    }

    /**
     * {@inheritdoc}
     */
    protected function registerExtensionTables()
    {
        return [
            'tntsearch_lookup' => TNTSearchLookupTable::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceProviders()
    {
        return [
            $this,
            new Provider\TNTSearchProvider(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerNutCommands(Container $container)
    {
        return [
            new Nut\IndexCommand($container),
            new Nut\SearchCommand($container),
        ];
    }
}
