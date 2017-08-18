<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Controller;

use Bolt\Extension\TwigTrait;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
class TNTSearchController implements ControllerProviderInterface
{
    private $permission = 'settings';

    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        /** @var $ctr \Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        $ctr
            ->match("/", [$this, 'home'])
            ->before([$this, 'before'])
            ->bind('tntsearch.home')
        ;

        $ctr
            ->match("/index", [$this, 'index'])
            ->before([$this, 'before'])
            ->bind('tntsearch.index')
        ;

        $ctr
            ->match("/search", [$this, 'search'])
            // ->before([$this, 'before'])
            ->bind('tntsearch.search')
        ;

        return $ctr;
    }

    /**
     *
     */
    public function before(Request $request, Application $app)
    {
        if (!$app['users']->isAllowed($this->permission)) {
            throw new AccessDeniedException('Logged in user does not have the correct rights to use this route.');
        }
    }

    /**
     *
     */
    public function home(Application $app, Request $request)
    {
        $html = $app['twig']->render("@tntsearch/home.twig", [
            'title' => "TNTSearch",
        ]);

        return new Response($html);
    }

    /**
     *
     */
    public function index(Application $app, Request $request)
    {
        $contenttypes = $request->request->get('contenttypes', []);

        if (!empty($contenttypes)) {
            // allow
            foreach ($contenttypes as $contenttype) {
                $app['tntsearch.sync']->sync($contenttype);
                $app['tntsearch.sync']->index($contenttype);
            }

            $app['logger.flash']->success('Ok! Indexing '. $contenttype);
        } else {
            $app['tntsearch.sync']->sync();
            $app['tntsearch.sync']->index();

            $app['logger.flash']->success('Ok! Indexing ALL');
        }

        return $app->redirect(
            $app['url_generator']->generate('tntsearch.home')
        );
    }

    /**
     *
     */
    public function search(Application $app, Request $request)
    {
        // $app['tntsearch.service']->search('igitur', null);

        // $app['logger.flash']->success('Ok!');

        return $app->redirect(
            $app['url_generator']->generate('tntsearch.home')
        );
    }

}
