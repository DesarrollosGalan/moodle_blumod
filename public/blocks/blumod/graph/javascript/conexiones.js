/**
 * IMPORTANTE: graph.js y table.js NO se han modificado. Siguen esperando
 * exactamente el mismo formato { results: { bindings: [...] } } de siempre,
 * porque ajax.php -> repository.php lo sigue devolviendo igual.
 */

let UI = null;

/**
 * Inicializa las referencias al DOM
 */

function initUI() {
    UI = {
        graph: document.getElementById('graph'),
        graphContainer: document.getElementById('wrapper'),
        legend: document.getElementById('legend'),
        nodePopup: document.getElementById('popup'),
        resultsTable: document.getElementById('resultsTable'),
        loadBtn: document.getElementById('loadBtn'),
        querySelect: document.getElementById('querySelect'),
        // courseSelect: document.getElementById('courseSelect'),
    };

    // Validar que los elementos existan
    if (!UI.loadBtn || !UI.querySelect) {
        console.error('ForjaLens: Elementos del UI no encontrados en el DOM', UI);
        return false;
    }

    // Añadir listeners después de que el DOM esté disponible
    UI.loadBtn.addEventListener('click', executeQuery);
    return true;
}

const state = {
    bindings: [],
};

/**
 * Llama a ajax.php pasando la acción (equivalente al antiguo "endpoint")
 * y el courseid actual. sesskey y wwwroot vienen inyectados por view.php
 * en window.FORJALENS.
 */
async function runQuery(action) {
    const { wwwroot, sesskey, courseid } = window.FORJALENS;

    const url = `${wwwroot}/blocks/blumod/graph/ajax.php`
        + `?action=${encodeURIComponent(action)}`
        + `&courseid=${encodeURIComponent(courseid)}`
        + `&sesskey=${encodeURIComponent(sesskey)}`;

    const response = await fetch(url, { credentials: 'same-origin' });
    const responseText = await response.text();
    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        throw new Error(body.error || `Error HTTP ${response.status}`);
    }
    try {
        return JSON.parse(responseText);
    } catch (err) {
        throw new Error(`Invalid JSON response: ${responseText.substring(0, 200)}`);
    }
}

async function executeQuery() {
    const selectedOption = UI.querySelect.selectedOptions[0];
    const action = UI.querySelect.value;
    const mode = selectedOption?.dataset?.mode || 'table';

    UI.loadBtn.disabled = true;
    try {
        const data = await runQuery(action);
        const bindings = data?.results?.bindings || [];
        state.bindings = bindings;
        setView(mode, bindings);

    } catch (err) {
        alert('No se ha podido obtener la información: ' + err.message);
    } finally {
        UI.loadBtn.disabled = false;
    }
}

/**
 * Alterna entre vista de tabla y vista de grafo, delegando el pintado
 * en las funciones ya existentes de table.js / graph.js.
 */
function setView(mode, bindings) {
    
    if (mode === 'table') {
        UI.graphContainer.style.display = 'none';
        UI.resultsTable.style.display = 'block';
        renderTable(bindings, UI.resultsTable);
        return;
    }

    UI.resultsTable.style.display = 'none';
    UI.graphContainer.style.display = 'block';

    if (mode === 'obsidian') {
        // buildObsidianElements(bindings);
        renderObsidianGraph(bindings);
        } else if (mode === 'compound') {
            // buildCompoundElements(bindings);
            renderCompoundGraph(bindings);
        } else {
            // buildStandardElements(bindings);
            renderCompoundGraph(bindings);
        }
    }

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // if (initUI()) {
    //     // Ejecutar la primera consulta automáticamente después de inicializar
    //     executeQuery();
    // }
    initUI();
});
