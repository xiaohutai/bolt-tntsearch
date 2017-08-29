<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Listener;

use Bolt\Events\StorageEvent;
use Bolt\Extension\TwoKings\TNTSearch\Service\TNTSearchService;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event class to handle storage related events.
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
class StorageEventListener implements EventSubscriberInterface
{
    /** @var Application $app */
    private $app;

    /** @var TNTSearchService $service */
    private $service;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->service = $app['tntsearch.service'];
    }

    /**
     * Handles POST_SAVE storage event
     *
     * @param StorageEvent $event
     */
    public function onPostSave(StorageEvent $event)
    {
        // $this->service
    }

    /**
     * Handles POST_DELETE storage event
     *
     * @param StorageEvent $event
     */
    public function onPostDelete(StorageEvent $event)
    {
        // $this->service
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [];
    }
}
