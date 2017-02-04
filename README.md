# Network View plugin para Mapas Cuturais

Visualização em rede para Mapas Culturais

# Installation and activation

Download and place this plugin inside you plugins folder located at protected/application/plugins and rename its folder to NetworkView.

Edit your config.php to activate the plugin and inform you Tracking ID: 

```PHP

'plugins' => [

    //... other plugin you may have...
    'NetworkView' => [
        'namespace' => 'NetworkView'
    ]
],

```

Access http://yoursite.com/site/network

Access the agents profiles and click on the "Rede" tab
