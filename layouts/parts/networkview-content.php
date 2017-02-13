<div id="networkview" class="aba-content">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.18.1/vis.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.18.1/vis.min.js"></script>
    
    <style type="text/css">
        #network-canvas {
            width: 100%;
            height: 500px;
        }
    </style>

    <div id="network-canvas"></div>

    <script>

        var nodes = new vis.DataSet(<?php echo json_encode($nodes); ?>);
        var edges = new vis.DataSet(<?php echo json_encode($edges); ?>);


        // create a network
        var container = document.getElementById('network-canvas');
        var data = {
        nodes: nodes,
        edges: edges
        };
        
        var options = {
            layout: {
                hierarchical: {
                    enabled: false
                }
            },
            physics:{
                enabled: false
            }
        };

        var network = new vis.Network(container, data, options);

    </script>

    
    
    
</div>
