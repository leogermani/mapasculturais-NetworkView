<?php
namespace NetworkView;

use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin {
    
    public $nodesIds = [];
    public $edgesIds = [];
    public $nodes = [];
    public $edges = [];
    
    public function _init() {
        $app = App::i();
        
        // register translation text domain
        \MapasCulturais\i::load_textdomain( 'networkview', __DIR__ . "/translations" );
        
        $plugin = $this;
        
        $app->hook('ALL(site.network)', function () use($app){
        
            
            $agents = $app->repo('Agent')->findAll();
            $spaces = $app->repo('Space')->findAll();
            
            $nodes = [];
            $edges = [];
            
            foreach ($agents as $agent) {
                $nodes[] = [
                    'id' => 'agent-' . $agent->id,
                    //'label' => $agent->name,
                    'label' => $agent->id,
                    'shape' => 'circle',
                    //'color' => 'blue'
                ];
                
                if (is_object($agent->parent)) {
                    $edges[] = [
                        'from' => 'agent-' . $agent->parent->id,
                        'to' => 'agent-' . $agent->id,
                        //'color' => 'blue'
                    ];
                }
            }            
            
            $this->render('search-network');
        
        });

        function enqueueScriptsAndStyles() {
            $app = App::i();            
            
            $app->view->enqueueStyle('app', 'vis-css', 'css/vis.min.css');
            $app->view->enqueueStyle('app', 'network-view-css', 'css/network-view.css');

            $app->view->enqueueScript('app', 'vis-js', 'js/vis.min.js', array('mapasculturais'));
            $app->view->enqueueScript('app', 'network-view-js', 'js/network-view.js', array('mapasculturais'));            
        }

        $app->hook('template(<<agent>>.single.tabs):end', function() use($app){
            $this->part('networkview-tab');
        });
        
        $app->hook('template(<<agent>>.single.tabs-content):end', function() use($app, $plugin){
            enqueueScriptsAndStyles();
            $this->part('networkview-content');

            $center = $this->controller->requestedEntity;
            
            $type = str_replace('MapasCulturais\Entities\\', '', $center->getClassName()); 
            
            $plugin->addNewAgentNode($center);
            
            // agentes que controla
            $controlledAgents = $app->repo('AgentAgentRelation')->findBy(['agent' => $center->id, 'hasControl' => true, 'status' => 1]);
            foreach ($controlledAgents as $a) {
                $ag = $a->owner;
                $plugin->addNewAgentNode($ag);
                $plugin->addNewEdge('agent-' . $center->id, 'agent-' . $ag->id, 'controls');
                $plugin->exploreChildren($ag, false);
            }
            
            // espacos que controla
            $controlledSpaces = $app->repo('SpaceAgentRelation')->findBy(['agent' => $center->id, 'hasControl' => true, 'status' => 1]);
            foreach ($controlledSpaces as $s) {
                $sp = $s->owner;
                $plugin->addNewSpaceNode($sp);
                $plugin->addNewEdge('agent-' . $center->id, 'space-' . $sp->id, 'controls');
            }
            
            // agentes que me controlam
            $controlledAgents = $app->repo('AgentAgentRelation')->findBy(['owner' => $center->id, 'hasControl' => true, 'status' => 1]);
            foreach ($controlledAgents as $a) {
                $ag = $a->agent;
                $plugin->addNewAgentNode($ag);
                $plugin->addNewEdge('agent-' . $ag->id, 'agent-' . $center->id, 'controls');
            }
            
            // filhos e espaços
            $plugin->exploreChildren($center);
            
            // pais
            $parent = $plugin->exploreParents($center);            
            
            $app->view->jsObject['networkviewEdges'] = $plugin->edges;
            $app->view->jsObject['networkviewNodes'] = $plugin->nodes;            
        });
        
        
    }
    
    
    public function exploreChildren($entity, $exploreControlled = true) {
        $nodes = [];
        $edges = [];
        $app = App::i();

        $children = $entity->children;
        $spaces = $entity->spaces;
        
        if ($children || $spaces) {
            foreach ($entity->children as $c) {
                
                $this->addNewAgentNode($c);

                $this->addNewEdge('agent-' . $entity->id, 'agent-' . $c->id);
                
                $this->exploreChildren($c);
                
                // spaces
                foreach ($c->spaces as $space) {
                    
                    $this->addNewSpaceNode($space);

                    $this->addNewEdge('agent-' . $c->id, 'space-' . $space->id);
                    
                }
                
                if ($exploreControlled) {
                    
                    // agentes que controla
                    $controlledAgents = $app->repo('AgentAgentRelation')->findBy(['agent' => $c->id, 'hasControl' => true, 'status' => 1]);
                    
                    foreach ($controlledAgents as $a) {
                        $ag = $a->owner;
                        
                        $this->addNewAgentNode($ag);

                        $this->addNewEdge('agent-' . $c->id, 'agent-' . $ag->id, 'controls');
                        
                        $this->exploreChildren($ag, false);
                        
                    }
                    
                    
                    
                    // espacos que controla
                    $controlledSpaces = $app->repo('SpaceAgentRelation')->findBy(['agent' => $c->id, 'hasControl' => true, 'status' => 1]);
                    
                    
                    
                    foreach ($controlledSpaces as $s) {
                        $sp = $s->owner;
                        $this->addNewSpaceNode($sp);
                        $this->addNewEdge('agent-' . $c->id, 'space-' . $sp->id, 'controls');
                    }
                    
                }
                
            }
            
            // entity spaces
            foreach ($entity->spaces as $space) {
                //App::i()->log->debug($space);                
                $this->addNewSpaceNode($space);
                $this->addNewEdge('agent-' . $entity->id, 'space-' . $space->id);
                if ($space->parent) {                    
                    $this->addNewEdge('space-' . $space->parent->id, 'space-' . $space->id, 'subspace');
                }
            }
            
        } else {
            return false;
        }
        
        return [
            'edges' => $edges,
            'nodes' => $nodes
        ];
        
    }
    
    public function exploreParents($entity) {
        $nodes = [];
        $edges = [];
        
        $type = str_replace('MapasCulturais\Entities\\', '', $entity->getClassName()); 
        if (is_object($entity->parent)) {
            
            $this->addNewAgentNode($entity->parent);

            $this->addNewEdge('agent-' . $entity->parent->id, 'agent-' . $entity->id);
            
            $this->exploreParents($entity->parent);
            
        } else {
            return false;
        }
        
        return [
            'edges' => $edges,
            'nodes' => $nodes
        ];
        
    }
    
    function addNewAgentNode($agent) {
        
        $id = 'agent-' . $agent->id;        
        if (in_array($id, $this->nodesIds))
            return;
        
        $this->nodesIds[] = $id;
        $url = $agent->avatar == null ? App::i()->view->asset('img/avatar--agent.png',false) : $agent->avatar->transform('avatarSmall')->url;
        
        $this->nodes[] = [
            'id' => $id,
            'label' => (strlen($agent->name) > 13) ? substr($agent->name,0,10).'...' : $agent->name,
            'title' => $agent->name,
            'shape' => 'circularImage',
            'image' => $url,
            '_type' => 'agent',
            '_id' => $agent->id,
            'color' => 'rgb(29, 171, 198)'
        ];
        
    }
    
    function addNewSpaceNode($space) {
        
        $id = 'space-' . $space->id;
        if (in_array($id, $this->nodesIds))
            return;
        
        $this->nodesIds[] = $id;
        $url = $space->avatar == null ? App::i()->view->asset('img/avatar--space.png',false) : $space->avatar->transform('avatarSmall')->url;
        $this->nodes[] = [
            'id' => $id,
            'label' => (strlen($space->name) > 13) ? substr($space->name,0,10).'...' : $space->name,
            'title' => $space->name,
            'shape' => 'circularImage',
            'image' => $url,
            '_type' => 'space',
            '_id' => $space->id,
            'color' => 'rgb(232, 63, 150)'
        ];
    }
    
    function addNewEdge($from, $to, $type = 'default') {
        
        $check = $from . $to;
        
        if (in_array($check, $this->edgesIds))
            return;
        
        $this->edgesIds[] = $check;
        
        $config = [
                'from' => $from,
                'to' => $to,
                'arrows' => 'to'
            ];
        
        if ($type == 'controls') 
            $config['color'] = '#e74c3c';

        if ($type == 'subspace')
            $config['dashes'] = true;        
        
        $this->edges[] = $config;
    
    }
    
    public function register() {
        
    }
    
}
