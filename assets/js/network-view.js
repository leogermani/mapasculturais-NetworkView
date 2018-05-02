$(document).ready(function () {
    // legend
    var mynetwork = document.getElementById('network-canvas');

    var x = - mynetwork.clientWidth / 2 + 50;
    var y = - mynetwork.clientHeight / 2 + 50;
    var step = 70;
    dataNodes = MapasCulturais.networkviewNodes;
    dataedges = MapasCulturais.networkviewEdges;

    var colorEspace = 'rgb(232, 63, 150)';
    var colorAgent = 'rgb(29, 171, 198)';

    dataNodes.push({ id: 0, x: x, y: y, label: 'Agente', 'shape': 'circularImage', 'color': colorAgent, 'image': MapasCulturais.assets.avatarAgent, 'size': 20, fixed: true, physics: false });
    dataNodes.push({ id: 1, x: x, y: y + step, label: 'Espaço', 'shape': 'circularImage', 'color': colorEspace, 'image': MapasCulturais.assets.avatarSpace, 'size': 20, fixed: true, physics: false });

    var nodes = new vis.DataSet(dataNodes);
    var edges = new vis.DataSet(dataedges);

    // create a network
    var container = document.getElementById('network-canvas');

    var data = {
        nodes: nodes,
        edges: edges
    };

    var options = {
        interaction: { hover: true },
        layout: {
            hierarchical: {
                enabled: false
            }
        },
        physics: {
            enabled: false
        },
        nodes: {
            borderWidth: 6,
            color: { background: '#2980b9', border: '#2980b9' },
            shadow: true,
            shapeProperties: {
                useBorderWithImage: true,
                useImageSize: false
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
        if (params.nodes.length == 0)
            document.getElementById('node-details').innerHTML = "";
        else
            showCard(params.nodes[0]);
    });

    //network.on("hoverNode", function (params) {            
    //    showCard(params.node);
    //});

    var image_url = "";
    showCard = function (idNode) {
        pos = network.canvasToDOM(network.getPositions(idNode)[idNode]);
        var node = nodes.get(idNode);
        if (node) {
            image_url = MapasCulturais.assets.avatarAgent;
            var url = `${MapasCulturais.baseURL}/api/agent/findOne/?&id=EQ(${node._id})&@select=id,singleUrl,name,subTitle,type,shortDescription,terms,project.name,project.singleUrl&@files=(avatar.avatarSmall):url`;
            if (node._type == 'space') {
                image_url = MapasCulturais.assets.avatarSpace;
                var url = `${MapasCulturais.baseURL}/api/space/findOne/?&id=EQ(${node._id})&@select=id,singleUrl,name,subTitle,type,shortDescription,terms,project.name,project.singleUrl,endereco,acessibilidade&@files=(avatar.avatarSmall):url`;
            }

            $.getJSON(url, function (entity) {
                if (entity['@files:avatar.avatarSmall'])
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
                $('#node-details').css({ top: pos.y, left: pos.x });
            });
        }
    }
});

