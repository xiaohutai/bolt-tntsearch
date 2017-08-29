<?php

namespace Bolt\Extension\TwoKings\TNTSearch\Controller;

use Bolt\Controller\Backend\BackendBase;
use Bolt\Extension\TwoKings\TNTSearch\Service\TNTSearchSyncService;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
class TNTSearchController extends BackendBase
{
    const PERMISSION = 'settings';

    /**
     * {@inheritdoc}
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/', [$this, 'home'])
            ->before([$this, 'before'])
            ->bind('tntsearch.home')
        ;

        $c->match('/index', [$this, 'index'])
            ->before([$this, 'before'])
            ->bind('tntsearch.index')
        ;

        $c->match('/search', [$this, 'search'])
            // ->before([$this, 'before'])
            ->bind('tntsearch.search')
        ;

        return $c;
    }

    /**
     * {@inheritdoc}
     */
    public function before(Request $request, Application $app, $roleRoute = null)
    {
        return parent::before($request, $app, self::PERMISSION);
    }

    /**
     * @return Response
     */
    public function home()
    {
        $html = $this->render('@tntsearch/home.twig', [
            'title' => 'TNTSearch',
        ]);

        return new Response($html);
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function index(Request $request)
    {
        /** @var TNTSearchSyncService $tntSearch */
        $tntSearch = $this->app['tntsearch.sync'];
        $contentTypes = $request->request->get('contenttypes');

        if ($contentTypes === null) {
            $tntSearch->sync();
            $tntSearch->index();

            $this->flashes()->success('Ok! Indexing ALL');

            return $this->redirect($this->generateUrl('tntsearch.home'));
        }

        // allow
        foreach ($contentTypes as $contentType) {
            /** @var string $contentType */
            $tntSearch->sync($contentType);
            $tntSearch->index($contentType);

            $this->flashes()->success('Ok! Indexing ' . $contentType);
        }

        return $this->redirect($this->generateUrl('tntsearch.home'));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function search(Request $request)
    {
        $results = $this->app['tntsearch.service']->search(
            $request->query->getAlpha('query'),
            $request->query->getAlpha('contenttype')
        );

        $html = $this->render('@tntsearch/home.twig', [
            'title'   => 'TNTSearch',
            'results' => $results,
        ]);

        return new Response($html);
    }
}
