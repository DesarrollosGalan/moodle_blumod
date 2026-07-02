// ======================
// TABLE RENDER (GLOBAL)
// ======================

/**
 * Renderiza una tabla HTML dinámica a partir de bindings SPARQL-like
 */
function renderTable(bindings, tableContainer) {
    if (!tableContainer) return;

    const tbody = tableContainer.querySelector("tbody");
    const thead = tableContainer.querySelector("thead");

    if (!tbody || !thead) return;

    // ======================
    // RESET TABLE
    // ======================
    tbody.innerHTML = "";
    thead.innerHTML = "";

    // ======================
    // COLUMNAS DINÁMICAS
    // ======================
    const columns = [...new Set(
        bindings.flatMap(obj => Object.keys(obj || {}))
    )];

    // ======================
    // HEADER
    // ======================
    const headerRow = document.createElement("tr");

    columns.forEach(col => {
        const th = document.createElement("th");
        th.textContent = col;
        headerRow.appendChild(th);
    });

    thead.appendChild(headerRow);

    // ======================
    // BODY
    // ======================
    bindings.forEach(item => {
        const tr = document.createElement("tr");

        columns.forEach(col => {
            const td = document.createElement("td");

            const raw = item?.[col];

            const value =
                raw?.value ??
                raw ??
                "-";

            td.textContent = value;

            tr.appendChild(td);
        });

        tbody.appendChild(tr);
    });
}

/**
 * Limpia la tabla
 */
function clearTable(tableContainer) {
    if (!tableContainer) return;

    const tbody = tableContainer.querySelector("tbody");
    const thead = tableContainer.querySelector("thead");

    if (tbody) tbody.innerHTML = "";
    if (thead) thead.innerHTML = "";
}