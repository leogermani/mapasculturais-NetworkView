<?php
namespace NetworkView;

use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin {
    
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
            //\dump($spaces); die;
            #foreach ($spaces as $space) {
            #    $nodes[] = [
            #        'id' => 'space-' . $space->id,
            #        //'label' => $agent->name,
            #        'label' => $space->id,
            #        'shape' => 'square',
            #        //'color' => 'blue'
            #    ];
            #    //\dump($space->owner->id); die;
            #    $edges[] = [
            #        'from' => 'agent-' . $space->owner->id,
            #        'to' => 'space-' . $space->id,
            #        //'color' => 'blue'
            #    ];
            #    
            #    #if (is_object($agent->parent)) {
            #    #    $edges[] = [
            #    #        'from' => 'space-' . $space->parent->id,
            #    #        'to' => 'space-' . $space->id,
            #    #        //'color' => 'blue'
            #    #    ];
            #    #}
            #    
            #}
            
            $this->render('search-network', [
                'edges' => $edges,
                'nodes' => $nodes
            ]);
        
        });
        
        
        $app->hook('template(<<agent|space>>.single.tabs):end', function() use($app){
            $this->part('networkview-tab');
        });
        
        $app->hook('template(<<agent|space>>.single.tabs-content):end', function() use($app, $plugin){

            $nodes = [];
            $edges = [];
            
            $center = $this->controller->requestedEntity;
            
            $type = str_replace('MapasCulturais\Entities\\', '', $center->getClassName()); 
            
            $nodes[] = [
                'id' => $type . '-' . $center->id,
                'label' => $center->name,
                //'label' => $center->id,
                //'shape' => 'circle',
                //'color' => 'blue'
            ];
            
            $children = $plugin->exploreChildren($center);
            
            if (false !== $children) {
                $nodes = array_merge($nodes, $children['nodes']);
                $edges = array_merge($edges, $children['edges']);
            }
            
            $parent = $plugin->exploreParents($center);
            
            if (false !== $parent) {
                $nodes = array_merge($nodes, $parent['nodes']);
                $edges = array_merge($edges, $parent['edges']);
            }
            
            $this->part('networkview-content', [
                'edges' => $edges,
                'nodes' => $nodes
            ]);
            
        });
        
        
    }
    
    public function exploreChildren($entity) {
        $nodes = [];
        $edges = [];
        
        $type = str_replace('MapasCulturais\Entities\\', '', $entity->getClassName()); 
        if ($entity->children) {
            foreach ($entity->children as $c) {
                $nodes[] = [
                    'id' => $type . '-' . $c->id,
                    //'label' => $agent->name,
                    'label' => $c->name,
                    //'shape' => 'circle',
                    //'color' => 'blue'
                ];
                
                $edges[] = [
                    'from' => $type . '-' . $entity->id,
                    'to' => $type . '-' . $c->id,
                ];
                
                $children = $this->exploreChildren($c);
                
                if (false !== $children) {
                    $nodes = array_merge($nodes, $children['nodes']);
                    $edges = array_merge($edges, $children['edges']);
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
            $nodes[] = [
                'id' => $type . '-' . $entity->parent->id,
                //'label' => $agent->name,
                'label' => $entity->parent->name,
                //'shape' => 'circle',
                //'color' => 'blue'
            ];
            
            $edges[] = [
                'from' => $type . '-' . $entity->parent->id,
                'to' => $type . '-' . $entity->id,
            ];
            
            $parent = $this->exploreParents($entity->parent);
            
            if (false !== $parent) {
                $nodes = array_merge($nodes, $parent['nodes']);
                $edges = array_merge($edges, $parent['edges']);
            }
        } else {
            return false;
        }
        
        return [
            'edges' => $edges,
            'nodes' => $nodes
        ];
        
    }
    
    public function register() {
        
    }
    
}
