<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Provider;

use Bolt\Extension\TwoKings\TNTSearch\Service;
use Bolt\Version as BoltVersion;
use Silex\Application;
use Silex\ServiceProviderInterface;
use TeamTNT\TNTSearch\TNTSearch;

/**
 *
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
class TNTSearchProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        // TeamTNT TNTSearch -- eventually this should be wrapped by a Bolt\Search thing
        $app['tntsearch'] = $app->share(
            function (Application $app) {
                $tnt = new TNTSearch();
                $db = $app['config']->get('general/database');

                $tnt->loadConfig([
                    'driver'    => 'mysql', // $db['driver'], // HEH!?
                    'host'      => $db['host'],
                    'database'  => $db['dbname'],
                    'username'  => $db['username'],
                    'password'  => $db['password'],
                    'storage'   => $app['paths']['extensionsconfigpath'] . '/tntsearch/', // or via: 'config://extensions/tntsearch/'
                ]);

                // $config = $app['tntsearch.config']->get();

                return $tnt;
            }
        );

        // TwoKings TNTSearch Sync Service
        $app['tntsearch.sync'] = $app->share(
            function (Application $app) {
                return new Service\TNTSearchSyncService(
                    $app['tntsearch.config'],
                    $app['config'],
                    $app['tntsearch'],
                    $app['query'],
                    $app['logger.system']
                );
            }
        );

        // TwoKings TNTSearch Service
        $app['tntsearch.service'] = $app->share(
            function (Application $app) {
                return new Service\TNTSearchService(
                    $app['tntsearch.config'],
                    $app['config'],
                    $app['tntsearch'],
                    $app['tntsearch.sync'],
                    $app['query'],
                    $app['logger.system']
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
