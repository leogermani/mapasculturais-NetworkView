<div id="networkview" class="aba-content">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.18.1/vis.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.18.1/vis.min.js"></script>
    
    <style type="text/css">
        #network{
            position: relative;
        }
        #network-canvas {
            width: 100%;
            height: 500px;
        }
        #node-details {
            position: absolute;
            z-index:999;
        }
        .card {
            background: #FFF;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
            transition: 0.3s;
            border: 1px solid #ccc;
            padding: 5px;
            text-align: center;
            width: 160px;
        }
        .card:hover {
            box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2);
        }
        .container {
            background: #FFF;
            padding: 2px 16px;
        }
        .container h5 {
            text-overflow: ellipsis;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
        }
    </style>

    <div id="network">
        <div id="node-details"></div>
        <div id="network-canvas"></div>
    </div>
    

    <script>

        // legend
        var mynetwork = document.getElementById('network-canvas');
        var x = - mynetwork.clientWidth / 2 + 50;
        var y = - mynetwork.clientHeight / 2 + 50;
        var step = 70;
        dataNodes = <?php echo json_encode($nodes); ?>;
        dataedges = <?php echo json_encode($edges); ?>;

        dataNodes.push({id: 0, x: x, y: y,          label: 'Agente', 'shape' : 'circularImage', 'image':MapasCulturais.assets.avatarAgent, 'size':20,  fixed: true, physics:false});
        dataNodes.push({id: 1, x: x, y: y + step,   label: 'Espaço', 'shape' : 'image',         'image':MapasCulturais.assets.avatarSpace, 'size':20,  fixed: true, physics:false});


        //n.push({id: 1003, x: x, y: y + 3 * step, label: 'Computer', group: 'desktop', value: 1, fixed: true,  physics:false});
        //n.push({id: 1004, x: x, y: y + 4 * step, label: 'Smartphone', group: 'mobile', value: 1, fixed: true,  physics:false});

        //var nodes = new vis.DataSet(<?php echo json_encode($nodes); ?>);
        //var edges = new vis.DataSet(<?php echo json_encode($edges); ?>);

        var nodes = new vis.DataSet(dataNodes);
        var edges = new vis.DataSet(dataedges);
        
        // create a network
        var container = document.getElementById('network-canvas');

        var data = {
            nodes: nodes,
            edges: edges
        };
        
        var options = {            
            interaction:{hover:true},
            layout: {
                hierarchical: {
                    enabled: false
                }
            },
            physics:{
                enabled: false
            },            
            nodes: {
                borderWidth: 4,                
                color: {background:'#2980b9', border:'#2980b9'},
                shadow:true,
                shapeProperties: {
                    useBorderWithImage:true,
                    useImageSize:false
                }
            },
            edges: {
                width: 2,
                smooth: {
                    type: 'continuous',
                    forceDirection: 'none'
                },
                color: '#2980b9'
            }
        };
        
        var network = new vis.Network(container, data, options);

        network.on("zoom", function (params) {
            document.getElementById('node-details').innerHTML = "";
        });

        network.on("dragStart", function (params) {
            document.getElementById('node-details').innerHTML = "";
        });

        network.on("click", function (params) {
            if(params.nodes.length == 0) 
                document.getElementById('node-details').innerHTML = "";
            else 
                showCard(params.nodes[0]);
        });

        //network.on("hoverNode", function (params) {            
        //    showCard(params.node);
        //});

        var image_url = "";
        showCard = function(idNode) {
            pos = network.canvasToDOM(network.getPositions(idNode)[idNode]);
            var node = nodes.get(idNode);            
            if (node) {
                image_url = MapasCulturais.assets.avatarAgent;
                var url = `${MapasCulturais.baseURL}/api/agent/findOne/?&id=EQ(${node._id})&@select=id,singleUrl,name,subTitle,type,shortDescription,terms,project.name,project.singleUrl&@files=(avatar.avatarSmall):url`;                    
                if (node._type == 'space') {
                    image_url = MapasCulturais.assets.avatarSpace;
                    var url = `${MapasCulturais.baseURL}/api/space/findOne/?&id=EQ(${node._id})&@select=id,singleUrl,name,subTitle,type,shortDescription,terms,project.name,project.singleUrl,endereco,acessibilidade&@files=(avatar.avatarSmall):url`;                     
                }
                
                $.getJSON(url, function(entity) {                    
                    if(entity['@files:avatar.avatarSmall'])
                        image_url = entity['@files:avatar.avatarSmall'].url;

                    var entityContent = `
                        <div class="card"> 
                            <img src="${image_url}" alt="Avatar" style="width:100%">
                            <div class="container">
                                <h5><b><a href="${entity.singleUrl}">${entity.name}</a></b></h5> 
                                <p><span class="label"><?php \MapasCulturais\i::_e("Tipo");?>:</span> <a>${entity.type.name}</a></p> 
                            </div>
                        </div>
                    `;
                    document.getElementById('node-details').innerHTML = entityContent;
                    $('#node-details').css({top: pos.y, left: pos.x});
                });
            }
        }
    </script>
    
</div>
