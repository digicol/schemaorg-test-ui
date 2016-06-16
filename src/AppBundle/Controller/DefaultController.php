<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $utils = $this->get('app.utils');

        $content_sources = $this->getParameter('content_sources');

        foreach ($content_sources as $key => $content_source_params)
        {
            $content_source = $utils->newContentSource($content_source_params);

            $content_sources[ $key ][ 'search_actions' ] = $content_source->describeSearchActions();
        }

        $tpl_vars =
            [
                'content_sources' => $content_sources
            ];

        return $this->render('default/index.html.twig', $tpl_vars);
    }


    /**
     * @Route("/details/{content_source_key}/{thing_uri}", name="details")
     */
    public function detailsAction(Request $request, $content_source_key, $thing_uri)
    {
        $thing_uri = urldecode($thing_uri);
        
        $tpl_vars =
            [
                'content_source_key' => $content_source_key
            ];

        $utils = $this->get('app.utils');

        $content_sources = $this->getParameter('content_sources');

        if (! isset($content_sources[ $content_source_key ]))
        {
            throw $this->createNotFoundException('Content source does not exist.');
        }

        $tpl_vars[ 'content_source_params' ] = $content_sources[ $content_source_key ];

        $content_source = $utils->newContentSource($content_sources[ $content_source_key ]);

        $thing = $content_source->newThing($thing_uri);

        $tpl_vars[ 'thing' ] = $utils->getThingTemplateVars($thing);

        return $this->render('default/details.html.twig', $tpl_vars);
    }


    /**
     * @Route("/search/{content_source_key}/{search_action_key}", name="search")
     */
    public function searchAction(Request $request, $content_source_key, $search_action_key)
    {
        $query = $request->query->get('q', '');
        $page = $request->query->get('p', 1);

        $tpl_vars =
            [
                'content_source_key' => $content_source_key,
                'search_action_key' => $search_action_key,
                'q' => $query
            ];

        $utils = $this->get('app.utils');

        $content_sources = $this->getParameter('content_sources');
        
        if (! isset($content_sources[ $content_source_key ]))
        {
            throw $this->createNotFoundException('Content source does not exist.');
        }

        $tpl_vars[ 'content_source_params' ] = $content_sources[ $content_source_key ];

        $content_source = $utils->newContentSource($content_sources[ $content_source_key ]);

        $search_actions = $content_source->describeSearchActions();

        if (! isset($search_actions[ $search_action_key ]))
        {
            throw $this->createNotFoundException('Search action does not exist.');
        }

        $tpl_vars[ 'search_action_params' ] = $search_actions[ $search_action_key ];

        $search_action = $content_source->newSearchAction($search_actions[ $search_action_key ]);

        $search_action->setInputProperties
        (
            [
                'query' => $query,
                'opensearch:count' => 10,
                'opensearch:startPage' => $page
            ]
        );

        $search_action->execute();
        
        $search_result = $search_action->getProperties();

        // Convert objects in "item" into arrays
        
        foreach ($search_result[ 'result' ][ 'itemListElement' ] as $key => $list_item)
        {
            $thing = $list_item[ 'item' ];
            $search_result[ 'result' ][ 'itemListElement' ][ $key ][ 'item' ] = $utils->getThingTemplateVars($thing); 
        }
        
        $tpl_vars[ 'search_result' ] = $search_result;

        $this->addPaginationTemplateVars($tpl_vars);

        return $this->render('default/search.html.twig', $tpl_vars);
    }


    protected function addPaginationTemplateVars(&$tpl_vars)
    {
        $items_per_page = $tpl_vars[ 'search_result' ][ 'result' ][ 'opensearch:itemsPerPage' ];

        $current_page = ceil($tpl_vars[ 'search_result' ][ 'result' ][ 'opensearch:startIndex' ] / $items_per_page);
        $last_page = max(1, ceil($tpl_vars[ 'search_result' ][ 'result' ][ 'numberOfItems' ] / $items_per_page));
        $previous_page = max(1, ($current_page - 1));
        $next_page = min($last_page, ($current_page + 1));

        $pages = range(1, max($current_page, min(10, $last_page)));

        $tpl_vars[ 'pagination' ] =
            [
                'pages' => $pages,
                'current_page' => $current_page, 
                'next_page' => $next_page,
                'previous_page' => $previous_page
            ];
    }
}
