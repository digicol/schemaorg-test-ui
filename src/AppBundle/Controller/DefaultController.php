<?php

namespace AppBundle\Controller;

use Digicol\SchemaOrg\Dcx\DcxAdapter;
use Digicol\SchemaOrg\Sdk\AdapterInterface;
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
            /** @var AdapterInterface $content_source */
            $content_source = $utils->newContentSource($content_source_params);

            $content_sources[ $key ][ 'search_actions' ] = [ ];
            
            foreach ($content_source->getPotentialSearchActions() as $key2 => $potentialSearchAction)
            {
                $content_sources[ $key ][ 'search_actions' ][ $key2 ] =
                    [
                        'name' => $potentialSearchAction->getName(),
                        'description' => $potentialSearchAction->getDescription()
                    ];
            }
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

        /** @var AdapterInterface $content_source */
        $content_source = $utils->newContentSource($content_sources[ $content_source_key ]);

        $thing = $content_source->newThing($thing_uri);

        $tpl_vars[ 'thing' ] = $utils->getThingTemplateVars($thing);
        
        $tpl_vars[ 'thing_data_dump' ] = print_r($tpl_vars[ 'thing' ], true);

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

        /** @var AdapterInterface $content_source */
        $content_source = $utils->newContentSource($content_sources[ $content_source_key ]);

        $potential_search_actions = $content_source->getPotentialSearchActions();

        if (! isset($potential_search_actions[ $search_action_key ]))
        {
            throw $this->createNotFoundException('Potential search action does not exist.');
        }

        $potential_search_action = $potential_search_actions[ $search_action_key ];
        
        $tpl_vars[ 'search_action_params' ] = $potential_search_action->getParams();

        $search_action = $potential_search_action->newSearchAction();

        $search_action->setQuery($query);
        $search_action->setItemsPerPage(10);
        $search_action->setStartIndex((($page - 1) * 10) + 1);

        $search_result = $search_action->getResult();

        $tpl_vars[ 'search_result' ][ 'result' ] = $search_result->getOutputProperties();
        $tpl_vars[ 'search_result' ][ 'items' ] = [ ];
        
        foreach ($search_result as $thing)
        {
            $tpl_vars[ 'search_result' ][ 'items' ][ ] = $utils->getThingTemplateVars($thing);
        }

        $tpl_vars[ 'search_result_data_dump' ] = print_r($tpl_vars[ 'search_result' ], true);
        
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
