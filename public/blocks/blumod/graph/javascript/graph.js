let cyInstance = null;
let graphReady = false;
let pendingCalls = [];
let graphUIInitialized = false;
cytoscape.use(cytoscapeDagre);

// ======================
// GRAPH UI CONTEXT
// ======================

let graphUI = {
    graphContainer: null,
    legendContainer: null,
    popupContainer: null,
    tableContainer: null,
    graphWrapper: null
};

function initGraphUI(config) {

    graphUI = {
        ...graphUI,
        ...config
    };
        graphUIInitialized = true;
}

let cachedAutoUI = null;

function autoDetectGraphUI() {
        // Ya está configurado manualmente
    if (graphUI.graphContainer) {
        return true;
    }
    if (cachedAutoUI) {
        graphUI = cachedAutoUI;
        return true;
    }

    const graph = document.getElementById("graph");
    if (!graph) return false;

    cachedAutoUI = {
        graphContainer: graph,
        legendContainer: document.getElementById("legend"),
        popupContainer: document.getElementById("popup"),
        graphWrapper: document.getElementById("wrapper")
    };

    graphUI = cachedAutoUI;
    return true;
}
// ======================
// HELPERS
// ======================
function getGraphContainer() {

 if (!graphUI.graphContainer) {
        autoDetectGraphUI();
    }

    const container = graphUI.graphContainer;

    if (!container) return null;

    container.innerHTML = "";

    return container;
}
/**
 * Añade un nodo a la lista de elementos de Cytoscape si no existe previamente.
 *
 * @param {Array} elements - Array donde se almacenan los elementos del grafo (nodos/aristas).
 * @param {Set} added - Conjunto de IDs ya añadidos para evitar duplicados.
 * @param {Object} params - Parámetros del nodo.
 * @param {string} params.id - Identificador único del nodo.
 * @param {string} params.label - Etiqueta visible del nodo.
 * @param {string} [params.type] - Tipo opcional del nodo (por ejemplo: "person", "device", etc.).
 * @param {string} [params.classes] - Clases CSS de Cytoscape para estilizar el nodo.
 
 */
function addNode(elements, added, {
    id,
    label,
    type,
    classes
}) {

    if (!id) {
        return;
    }

    const existing = elements.find(el => el.data?.id === id);

    if (existing) {
        if (type) {
            existing.data.type = type;
        }

        if (classes) {
            const existingClasses = existing.classes ? existing.classes.split(/\s+/) : [];
            const newClasses = classes.split(/\s+/);
            existing.classes = Array.from(new Set([...existingClasses, ...newClasses])).join(" ");
        }

        if (label && !existing.data.label) {
            existing.data.label = label;
        }

        return;
    }

    const node = {
        data: {
            id,
            label
        }
    };

    if (type) {
        node.data.type = type;
    }

    if (classes) {
        node.classes = classes;
    }

    elements.push(node);
    added.add(id);
}



function createGraph({
    elements,
    style,
    layout,
    onReady
}) {

    const container = getGraphContainer();
    if (!container) {
        return;
    }
        if (cyInstance) {
        cyInstance.destroy();
            cyInstance = null;
    }

    const cy = cytoscape({
        container,
        elements,
        style,
        layout
    });
cyInstance = cy;
    cy.ready(() => {

        cy.fit();

        if (onReady) {
            onReady(cy);
        }
    });
    //  EVENTO CLICK NODE
  // 1. CLICK EN NODO
    cy.on('tap', 'node', function (event) {
        showNodePopup(event.target, cy);
    });

    // 2. CLICK EN VACÍO (cerrar popup)
    cy.on('tap', function (event) {
        if (event.target === cy) {
            if (graphUI.popupContainer) {
                 graphUI.popupContainer.style.display = "none";
            }
        }
    });
    return cy;
}

function getLabel(binding, keys, fallbackUri) {

    for (const key of keys) {
        if (binding[key]?.value) {
            return binding[key].value;
        }
    }

    return fallbackUri
        ? fallbackUri.split("/").pop()
        : "-";
}

function cleanLabel(value) {

    return value
        ? value.split("#").pop().split("/").pop()
        : "-";
}

function cleanType(value) {
    if (!value) {
        return undefined;
    }

    const raw = value.split("#").pop().split("/").pop();
    return raw ? raw.toLowerCase() : undefined;
}

const NODE_COLORS = {
    resource: "#8fbafc",
    url: "#f6c23e",
    page: "#1cc88a",
    book: "#36b9cc",
    folder: "#fd7e14",
    wiki: "#858796",
    imscp: "#6f42c1",
    assign: "#ff8a65",
    quiz: "#f39c12",
    forum: "#f8a5c2",
    workshop: "#4b77be",
    lesson: "#f6d55c",
    lu: "#2ecc71"
};

function getNodeColor(type) {
    return NODE_COLORS[type] || "#ffd699";
}

function getNodeShape(type) {
    return type === "lu" ? "ellipse" : "round-rectangle";
}

function buildNodeTypeStyles() {
    return Object.keys(NODE_COLORS).map(type => ({
        selector: `node[type="${type}"], node.${type}`,
        style: {
            "background-color": NODE_COLORS[type],
            shape: getNodeShape(type),
            color: type === "wiki" || type === "imscp" || type === "workshop" || type === "assign" ? "#000" : "#1a1a1a"
        }
    }));
}
///=====================
///HELPER Berriak
//=====================
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const s = document.createElement("script");
        s.src = src;
        s.onload = resolve;
        s.onerror = reject;
        document.head.appendChild(s);
    });
}
async function ensureDeps() {

    if (typeof cytoscape === "undefined") {
        await loadScript("https://cdn.jsdelivr.net/npm/cytoscape@3.28.1/dist/cytoscape.min.js");
    }

    if (typeof cytoscapeDagre === "undefined") {
        await loadScript("https://cdn.jsdelivr.net/npm/cytoscape-dagre@2.5.0/cytoscape-dagre.js");
    }

    cytoscape.use(cytoscapeDagre);
}
// ======================
// STYLES
// ======================

const STANDARD_STYLE = [

    {
        selector: "node",
        style: {
            label: "data(label)",
            "text-valign": "center",
            "text-halign": "center",
            "font-size": 13,
            "text-wrap": "wrap",
            "text-max-width": 100,
            width: 70,
            height: 50,
            shape: "round-rectangle",
            "background-color": "#ffd699",
            color: "#1a1a1a"
        }
    },
    ...buildNodeTypeStyles(),

    {
        selector: "node[type='lu'], node.lu",
        style: {
            width: 110,
            height: 60,
            "text-wrap": "wrap",
            "text-max-width": 90,
            "text-valign": "center",
            "text-halign": "center",
            padding: "10px"
        }
    },

    {
        selector: "edge",
        style: {
            width: 2,
            "line-color": "#aaa",
            "target-arrow-shape": "triangle",
            "curve-style": "bezier"
        }
    },

    {
        selector: "node:hover",
        style: {
            "border-width": 2,
            "border-color": "#333"
        }
    }
];

const OBSIDIAN_STYLE = [

    {
        selector: "node",
        style: {
            label: "data(label)",
            "text-valign": "center",
            "text-halign": "center",
            "font-size": 13,
            color: "#000",
            "text-wrap": "wrap",
         //   "text-max-width": 150,
            shape: "round-rectangle",
          //  width: 140,
        //    height: 30,
            padding: "15px"
        }
    },

    {
        selector: ".lu",
        style: {
            "background-color": "#4a90e2"
        }
    },

    {
        selector: 'edge[type="sub"]',
        style: {
            "line-color": "#74b9ff",
            "target-arrow-color": "#74b9ff",
            "target-arrow-shape": "triangle",
            "curve-style": "bezier",
            width: 2
        }
    },

    {
        selector: 'edge[type="pre"]',
        style: {
            "line-color": "#d63031",
            "target-arrow-color": "#d63031",
            "target-arrow-shape": "triangle",
            "curve-style": "bezier",
            width: 2,
            "line-style": "dashed",
            opacity: 0.8
        }
    }
];

const COMPOUND_STYLE = [
    {
        selector: "node",
        style: {
            label: "data(label)",
            "text-valign": "center",
            "text-halign": "center",
            "font-size": 16,
            "text-wrap": "wrap",
            "text-max-width": 100,
            width: 70,
            height: 50,
            shape: "rectangle",
            "background-color": "#d3d3d3",
            color: "#000000"
        }
    },
    {
        selector: ":parent",
        style: {
            "background-opacity": 0.08,
            "border-width": 2,
            "border-color": "#888",
            "padding": "10px",
            "label": "data(label)",
            "text-valign": "center",
            "text-margin-y": "-20px",
            "background-fit": "contain",
            "text-halign": "center",
            'text-opacity': 1,
            'font-size': 14,
            'color': '#333',
            'max-width': '280px',
            'text-wrap': 'nowrap'
        }
    }
];
// ======================
// BUILDERS
// ======================
/**
 * Convierte una lista de bindings en elementos compatibles con Cytoscape.
 * Genera nodos y relaciones (edges), evitando duplicados y soportando distintos esquemas de datos.
 *
 * Cada binding puede contener distintas propiedades para representar el origen y destino
 * de una relación (resource, subject, lu, object).
 *
 * @param {Array<Object>} bindings - Lista de relaciones/datos de entrada.
 * @returns {Array<Object>} elements - Array de elementos Cytoscape (nodos y edges).
 *
 * @description
 * La función:
 * - Extrae el nodo origen (s) y destino (o) desde diferentes campos posibles.
 * - Genera etiquetas legibles usando `getLabel`.
 * - Clasifica los nodos según su tipo (resource, subject, lu, object o default).
 * - Evita duplicados usando un Set interno.
 * - Añade nodos y relaciones en formato compatible con Cytoscape.js.
 *
 * @example
 * const elements = buildStandardElements(bindings);
 *
 * // Resultado típico:
 * [
 *   { data: { id: "A", label: "Nodo A", type: "resource" } },
 *   { data: { id: "B", label: "Nodo B", type: "object" } },
 *   { data: { source: "A", target: "B" } }
 * ]
 */
function buildStandardElements(bindings) {

    const elements = [];
    const added = new Set();

    bindings.forEach(b => {

        const s =
            b.source?.value ??
            b.resource?.value ??
            b.subject?.value;

        const o =
            b.target?.value ??
            b.lu?.value ??
            b.object?.value;

        const sLabel = b.sourceLabel?.value ??
         getLabel(b, ["resourceName", "resourceLabel", "subjectLabel"], s);

          const oLabel = b.targetLabel?.value ??
        getLabel(b, ["luName", "luLabel", "objectLabel"], o);

        /*const sType =
            b.type??
            (b.resource ? "resource" :
            b.subject ? "subject" :
            "default");

        const oType =
        b.type??
           (b.lu ? "lu" :
            b.object ? "object" :
            "default");*/

        // Nodo aislado
        const sType = cleanType(b.sourceType?.value)
            || (b.resource?.value || b.subject?.value ? "resource" : undefined);
        const oType = cleanType(b.targetType?.value)
            || (b.lu?.value ? "lu" :
                b.object?.value ? "resource" : undefined);

        if (!s && o) {

            addNode(elements, added, {
                id: o,
                label: oLabel,
                type: oType,
                classes: oType ? `${oType}${oType === "lu" ? " lu" : ""}` : undefined
            });

            return;
        }
        if (s && !o) {

            addNode(elements, added, {
                id: s,
                label: sLabel,
                type: sType,
                classes: sType ? `${sType}${sType === "lu" ? " lu" : ""}` : undefined
            });

            return;
        }
        // Relación

        if (s && o) {

            addNode(elements, added, {
                id: s,
                label: sLabel,
                type: sType,
                classes: sType ? `${sType}${sType === "lu" ? " lu" : ""}` : undefined
            });

            addNode(elements, added, {
                id: o,
                label: oLabel,
                type: oType,
                classes: oType ? `${oType}${oType === "lu" ? " lu" : ""}` : undefined
            });

            elements.push({
                data: {
                    source: s,
                    target: o
                }
            });
        }
    });

    return elements;
}


function buildObsidianElements(bindings) {

    const elements = [];
    const added = new Set();
    const edgeTypes = new Set();

    bindings.forEach(item => {

        const source = item.source?.value;
        const target = item.target?.value;
        const type = item.type?.value || "sub";

        const sourceLabel =
            item.sourceLabel?.value || cleanLabel(source);

        const targetLabel =
            item.targetLabel?.value || cleanLabel(target);

        addNode(elements, added, {
            id: source,
            label: sourceLabel,
            classes: "lu"
        });

        addNode(elements, added, {
            id: target,
            label: targetLabel,
            classes: "lu"
        });

        if (source && target) {

            edgeTypes.add(type);

            let sourceNode = source;
            let targetNode = target;

            if (type === "pre") {
                sourceNode = target;
                targetNode = source;
            }

            elements.push({
                data: {
                    source: sourceNode,
                    target: targetNode,
                    type
                }
            });
        }
    });

    return {
        elements,
        edgeTypes
    };
}



function buildCompoundElements(bindings) {

    const nodes = new Map();
    const edges = [];
    const edgeTypes = new Set();

    const getId = (v) => v?.value?.trim();

    // -------------------------
    // 1. CREAR NODOS + COMPOUNDS
    // -------------------------
    bindings.forEach(item => {

        const source = getId(item.source);
        const target = getId(item.target);
        const type = item.type?.value;

        const sourceLabel = item.sourceLabel?.value || source;
        const targetLabel = item.targetLabel?.value || target;

        if (!source) return;

        // Crear nodo source
        if (!nodes.has(source)) {
            nodes.set(source, {
                data: {
                    id: source,
                    label: sourceLabel
                }
            });
        }

        if (target) {

            if (type === "sub") {

                // target queda dentro de source
                nodes.set(target, {
                    data: {
                        id: target,
                        label: targetLabel,
                        parent: source
                    }
                });

            } else {

                if (!nodes.has(target)) {
                    nodes.set(target, {
                        data: {
                            id: target,
                            label: targetLabel
                        }
                    });
                }
            }
        }
    });

    // -------------------------
    // 2. CREAR EDGES (SOLO PRE)
    // -------------------------
    bindings.forEach(item => {

        const source = getId(item.source);
        const target = getId(item.target);
        const type = item.type?.value || "pre";

        if (!source || !target) return;

        // Solo dibujamos relaciones PRE
        if (type !== "pre") return;

        edgeTypes.add(type);

        // Invertir dirección igual que en Obsidian
        let sourceNode = source;
        let targetNode = target;

        if (type === "pre") {
            sourceNode = target;
            targetNode = source;
        }

        edges.push({
            data: {
                id: `${sourceNode}-${targetNode}-${type}`,
                source: sourceNode,
                target: targetNode,
                type
            }
        });
    });

    return {
        elements: [
            ...nodes.values(),
            ...edges
        ],
        edgeTypes
    };
}
// ======================
//  GRAPH RENDERS 
// ======================

function _renderGraph(bindings) {
 /*navigator.clipboard.writeText(
        JSON.stringify(bindings, null, 2)
    );

    alert("bindings copiado al portapapeles");*/
    autoDetectGraphUI();
            const elements =
    buildStandardElements(bindings);
      // buscar el label más largo
    const maxLabelLength = Math.max(
        ...elements
            .filter(e => e.data?.label)
            .map(e => e.data.label.length)
    );
       // tamaño común para todos
    // ancho según texto
    const nodeWidth = Math.min(
        180,
        Math.max(
            80,
            Math.ceil(maxLabelLength / 10) * 25
        )
    );


    // altura fija
    const nodeHeight = 50;

       const style = STANDARD_STYLE.map(rule => ({
        ...rule,
        style: {
            ...rule.style,

            ...(rule.selector === "node" && {
                width: nodeWidth,
                height: nodeHeight,
                "text-max-width": nodeWidth - 10
            })
        }
    }));

    const nodeTypes = new Set(
        elements
            .filter(e => e.data?.type)
            .map(e => e.data.type)
    );

    return createGraph({

        elements,

      //  style: STANDARD_STYLE,
        style,

        layout: {
            name: "cose",
            animate: true,
            animationDuration: 800,
            fit: true,
            padding: 30
        },

        onReady: () => {
            renderLegend(new Set(), { showNodes: true, showEdges: false, nodeTypes });
        }
    });
}

//convierte los bindings  en elementos del grafo y crea un grafo con ellos
function _renderObsidianGraph(bindings) {
autoDetectGraphUI();
    const {
        elements,
        edgeTypes
    } = buildObsidianElements(bindings);


     // buscar el label más largo
    const maxLabelLength = Math.max(
        ...elements
            .filter(e => e.data?.label)
            .map(e => e.data.label.length)
    );


    // ancho dinámico según texto
    const nodeWidth = Math.min(
        180,
        Math.max(
            80,
            Math.ceil(maxLabelLength / 10) * 25
        )
    );


    // altura fija
    const nodeHeight = 40;


    // clonamos el estilo y modificamos los nodos
    const style = OBSIDIAN_STYLE.map(rule => ({
        ...rule,
        style: {
            ...rule.style,

            ...(rule.selector === "node" && {
                width: nodeWidth,
                height: nodeHeight,
                "text-max-width": nodeWidth - 10
            })
        }
    }));
    
    showGraph(); 
    return createGraph({

        elements,

        //style: OBSIDIAN_STYLE,
        style,

        layout: {
            name: "dagre",
            rankDir: "TB",
            nodeSep: 25,
            rankSep: 50,
            edgeSep: 10,
            animate: true,
            fit: true
        },

        onReady: () => {
            renderLegend(edgeTypes, { showNodes: false, showEdges: true });
        }
    });

}

function _renderCompoundGraph(bindings) {
autoDetectGraphUI();
    const {
        elements,
        edgeTypes
    } = buildCompoundElements(bindings);

    const maxLabelLength = Math.max(
        ...elements
            .filter(e => e.data?.label)
            .map(e => e.data.label.length)
    );

    const nodeWidth = Math.min(
        180,
        Math.max(
            80,
            Math.ceil(maxLabelLength / 10) * 25
        )
    );

    const nodeHeight = 40;

    // 👇 STYLE BASE + COMPOUND STYLE SEPARADO
    const style = [
        ...OBSIDIAN_STYLE.map(rule => ({
            ...rule,
            style: {
                ...rule.style,

                ...(rule.selector === "node" && {
                    width: nodeWidth,
                    height: nodeHeight,
                    "text-max-width": nodeWidth - 10
                })
            }
        })),
        ...COMPOUND_STYLE
    ];

    showGraph();

    return createGraph({

        elements,
        style,

        layout: {
            name: "breadthfirst",
            rankDir: "TB",
            nodeSep: 25,
            rankSep: 50,
            edgeSep: 10,
            animate: true,
            fit: true,
            padding: 50,
            compoundPadding: 20
        },

        onReady: () => {
            renderLegend(edgeTypes, { showNodes: false, showEdges: true });
        }
    });
}

// ======================
// UI LAYER (legend + popup)
// ======================



function renderLegend(edgeTypes, options = { showNodes: true, showEdges: true, nodeTypes: null }) {

    let legend = graphUI.legendContainer;
    if (!legend) {
        return;
    }

    legend.style.display = "flex";
    // legend.style.flexDirection = "column";
    legend.innerHTML = "";

    if (options.showNodes) {
        const typesToShow = options.nodeTypes
            ? Array.from(options.nodeTypes)
            : Object.keys(NODE_COLORS);

        const sortedTypes = typesToShow.slice().sort((a, b) => {
            if (a === "lu") return -1;
            if (b === "lu") return 1;
            return a.localeCompare(b);
        });

        const NODE_LABELS = {
            course: "Course",
            lu: "Learning Unit",
            resource: "Learning Resource",
            assessment: "Assessment Item",
            competence: "Competence"
        };

        sortedTypes.forEach(type => {
            const item = document.createElement("div");
            item.className = "legend-item";
            item.innerHTML = `
                <span class="color-box" style="
                    display:inline-block;
                    width:16px;
                    height:16px;
                    margin-right:8px;
                    vertical-align:middle;
                    background:${NODE_COLORS[type] || '#ccc'};
                    border:1px solid #999;
                "></span>
                ${NODE_LABELS[type] || type}
            `;
            legend.appendChild(item);
        });
    }

    if (options.showEdges) {
        const edgeColors = {
            sub: "#74b9ff",
            pre: "#d63031"
        };

        const edgeLabels = {
            sub: "sub",
            pre: "pre"
        };

        edgeTypes.forEach(type => {
            const item = document.createElement("div");
            item.className = "legend-item";
            item.innerHTML = `
                <span class="line" style="
                    display:inline-block;
                    width:20px;
                    height:2px;
                    margin-right:8px;
                    background:${edgeColors[type] || "#aaa"};
                    ${type === "pre"
                        ? "border-top:2px dashed #d63031;"
                        : ""}
                "></span>
                ${edgeLabels[type] || type}
            `;
            legend.appendChild(item);
        });
    }
}

function showGraph() {

    if (graphUI.tableContainer) {
        graphUI.tableContainer.style.display = "none";
    }

    if (graphUI.graphWrapper) {
        graphUI.graphWrapper.style.display = "block";
    }

    requestAnimationFrame(() => {
        if (cyInstance) {
            cyInstance.resize();
            cyInstance.fit();
        }
    });
}


function showNodePopup(node, cy) {

    const popup = graphUI.popupContainer;
    if (!popup) {
    return;
    }
    const pos = node.renderedPosition();
    popup.style.width = "300px";
    popup.style.maxWidth = "90vw";
    popup.innerHTML = `
        <strong>${node.data().label || node.data().id}</strong><br>
        <pre>${JSON.stringify(node.data(), null, 2)}</pre>
    `;

    const container = cy.container();
    const rect = container.getBoundingClientRect();

    popup.style.left = (rect.left + pos.x + 15) + "px";
    popup.style.top  = (rect.top + pos.y + 15) + "px";

    popup.style.display = "block";
}

// =====================================================
// EXPORT GLOBAL (sin módulos)
// =====================================================
//window.renderGraph = renderGraph;
window.renderGraph = function (...args) {

    if (!graphReady) {
        pendingCalls.push(() => window.renderGraph(...args));
        return;
    }

    return _renderGraph(...args);
};
//window.renderObsidianGraph = renderObsidianGraph;
window.renderObsidianGraph = function (...args) {
    if (!graphReady) {
        pendingCalls.push(() => 
            _renderObsidianGraph(...args));
        return;
    }
    return _renderObsidianGraph(...args);
};

window.renderCompoundGraph = function (...args) {
    if (!graphReady) {
        pendingCalls.push(() => _renderCompoundGraph(...args));
        return;
    }
    return _renderCompoundGraph(...args);
};
//window.renderCompoundGraph = renderCompoundGraph;

async function initGraphLib() {
    await ensureDeps();
    graphReady = true;

    pendingCalls.forEach(fn => fn());
    pendingCalls = [];
}

initGraphLib();