define('block_blumod/assign_competencies', [], function() {
    const MIN_FILTER_CHARS = 3;

    function init() {
        const bluSelector = document.getElementById('bluselector');
        const competenciesContainer = document.getElementById('competencies');
        if (!bluSelector || !competenciesContainer) {
            return;
        }

        if (bluSelector.value) {
            asignarBLU(bluSelector.value);
            llamarCompetencies(bluSelector.value);
        }

        bluSelector.addEventListener('change', function(event) {
            asignarBLU(event.target.value);
            llamarCompetencies(event.target.value);
        });

        document.querySelectorAll('.validate-selected').forEach(function(hiddenId) {
            hiddenId.addEventListener('click', function(event) {
                if (!bluSelector.value) {
                    event.preventDefault();
                }
            });
        });

        competenciesContainer.addEventListener('click', function(event) {
            const button = event.target.closest('button[data-action]');
            if (!button) {
                return;
            }

            if (button.dataset.action === 'collapse-available') {
                collapseAvailable();
                return;
            }

            if (button.dataset.action === 'expand-available') {
                expandAvailable();
                return;
            }

            if (!button.dataset.from) {
                return;
            }

            ejecutarEvento(button.dataset.action, button.dataset.from, bluSelector);
        });
    }

    function ejecutarEvento(action, from, bluSelector) {
        if (!action || !from || !bluSelector.value) {
            return;
        }

        const control = document.getElementById(from);
        if (!control || !control.value) {
            return;
        }

        const selectedOption = control.options && control.selectedIndex >= 0
            ? control.options[control.selectedIndex]
            : null;
        if (!selectedOption) {
            return;
        }

        if (!isActionableOption(selectedOption)) {
            return;
        }

        const bluid = bluSelector.value;
        asignarBLU(bluid);
        llamarCompetencies(bluid, action, control.value);
    }

    function asignarBLU(bluid) {
        document.querySelectorAll('[name=id], [name=bluid]').forEach(function(hiddenId) {
            hiddenId.value = bluid;
        });
    }

    function llamarCompetencies(bluid, action, value) {
        const courseid = (new URLSearchParams(window.location.search)).get('courseid');
        if (!courseid) {
            return;
        }

        const params = new URLSearchParams({
            courseid: courseid,
            bluid: bluid
        });

        if (action && value) {
            params.set('action', action);
            params.set('modid', value);
        }

        const url = M.cfg.wwwroot + '/blocks/blumod/competencies.php?' + params.toString();

        fetch(url)
            .then(function(response) {
                return response.text();
            })
            .then(function(data) {
                const competenciesContainer = document.getElementById('competencies');
                if (!competenciesContainer) {
                    return;
                }
                competenciesContainer.innerHTML = data;
                bindSearchFilter();
                expandAvailable();
                bindAvailableTreeToggle();
            })
            .catch(function() {
                console.log('Error fetching competencies');
            });
    }

    function bindSearchFilter() {
        const searchInput = document.getElementById('competencyselector_search');
        const availableSelect = document.getElementById('competencyselector_available');
        if (!searchInput || !availableSelect) {
            return;
        }

        applySearchFilter(searchInput, availableSelect);
        searchInput.addEventListener('input', function() {
            applySearchFilter(searchInput, availableSelect);
        });
    }

    function applySearchFilter(searchInput, availableSelect) {
        const pattern = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const doFilter = pattern.length >= MIN_FILTER_CHARS;
        const isCollapsed = availableSelect.dataset.treeState === 'collapsed';
        const previousPattern = availableSelect.dataset.lastFilterPattern || '';
        if (pattern !== previousPattern) {
            clearForcedVisible(availableSelect);
        }
        availableSelect.dataset.lastFilterPattern = pattern;
        const collapsedParents = [];
        const rows = [];

        Array.prototype.forEach.call(availableSelect.options, function(option, index) {
            const rowtype = option.getAttribute('data-rowtype');
            const frameworkid = option.getAttribute('data-frameworkid') || '';
            const isleaf = option.getAttribute('data-isleaf') === '1';
            const level = parseInt(option.getAttribute('data-level') || '0', 10);

            if (rowtype === 'framework') {
                collapsedParents.length = 0;
            } else {
                while (collapsedParents.length > 0) {
                    const parent = collapsedParents[collapsedParents.length - 1];
                    if (parent.frameworkid !== frameworkid || level <= parent.level) {
                        collapsedParents.pop();
                        continue;
                    }
                    break;
                }
            }

            let baseHidden = false;
            if (collapsedParents.length > 0 && rowtype !== 'framework') {
                baseHidden = true;
            }

            rows[index] = {
                option: option,
                rowtype: rowtype,
                frameworkid: frameworkid,
                isleaf: isleaf,
                level: level,
                basishidden: baseHidden,
                textmatch: option.text.toLowerCase().indexOf(pattern) !== -1,
                forcevisible: option.getAttribute('data-force-visible') === '1',
                hasmatchingdesc: false
            };

            if (isCollapsibleRow(option) && option.getAttribute('data-node-collapsed') === '1') {
                collapsedParents.push({
                    frameworkid: frameworkid,
                    level: rowtype === 'framework' ? 0 : level
                });
            }
        });

        if (!doFilter) {
            clearForcedVisible(availableSelect);
            rows.forEach(function(row) {
                row.option.hidden = row.basishidden;
            });
            return;
        }

        // Propagate matching leaf hits up to all visible ancestors in the same framework.
        const ancestorStack = [];
        rows.forEach(function(row, index) {
            while (ancestorStack.length > 0) {
                const top = ancestorStack[ancestorStack.length - 1];
                if (top.frameworkid !== row.frameworkid || row.level <= top.level) {
                    ancestorStack.pop();
                    continue;
                }
                break;
            }

            if (!row.basishidden && row.isleaf && row.textmatch) {
                ancestorStack.forEach(function(ancestor) {
                    rows[ancestor.index].hasmatchingdesc = true;
                });
            }

            if (row.rowtype === 'framework' || (row.rowtype === 'competency' && !row.isleaf)) {
                ancestorStack.push({
                    index: index,
                    frameworkid: row.frameworkid,
                    level: row.level
                });
            }
        });

        rows.forEach(function(row) {
            if (row.basishidden) {
                row.option.hidden = true;
                return;
            }

            if (row.isleaf) {
                row.option.hidden = !(row.textmatch || row.forcevisible);
                return;
            }

            row.option.hidden = !(row.textmatch || row.hasmatchingdesc || row.forcevisible);
        });
    }

    function collapseAvailable() {
        const availableSelect = document.getElementById('competencyselector_available');
        if (!availableSelect) {
            return;
        }

        availableSelect.dataset.treeState = 'collapsed';

        Array.prototype.forEach.call(availableSelect.options, function(option) {
            if (isCollapsibleRow(option)) {
                option.setAttribute('data-node-collapsed', '1');
            }
            const rowtype = option.getAttribute('data-rowtype');
            option.hidden = rowtype !== 'framework';
        });
    }

    function expandAvailable() {
        const availableSelect = document.getElementById('competencyselector_available');
        const searchInput = document.getElementById('competencyselector_search');
        if (!availableSelect) {
            return;
        }

        availableSelect.dataset.treeState = 'expanded';

        Array.prototype.forEach.call(availableSelect.options, function(option) {
            if (isCollapsibleRow(option)) {
                option.setAttribute('data-node-collapsed', '0');
            }
            option.hidden = false;
        });

        if (searchInput) {
            applySearchFilter(searchInput, availableSelect);
        }
    }

    function bindAvailableTreeToggle() {
        const availableSelect = document.getElementById('competencyselector_available');
        if (!availableSelect) {
            return;
        }

        availableSelect.onchange = function() {
            const option = availableSelect.options[availableSelect.selectedIndex];
            if (!option) {
                return;
            }

            const rowtype = option.getAttribute('data-rowtype');
            const isleaf = option.getAttribute('data-isleaf') === '1';
            const searchInput = document.getElementById('competencyselector_search');
            const hasactivefilter = searchInput && searchInput.value.trim().length >= MIN_FILTER_CHARS;

            if (rowtype === 'framework' || (rowtype === 'competency' && !isleaf)) {
                const forceexpand = rowtype === 'competency' && !isleaf && hasactivefilter;
                toggleSubtree(availableSelect, availableSelect.selectedIndex, forceexpand);
                option.selected = false;
            }
        };
    }

    function toggleSubtree(select, parentIndex, forceExpand) {
        const options = select.options;
        const parent = options[parentIndex];
        if (!parent) {
            return;
        }

        const currentlyCollapsed = parent.getAttribute('data-node-collapsed') === '1';
        const shouldForceExpand = forceExpand === true;

        if (shouldForceExpand) {
            parent.setAttribute('data-node-collapsed', '0');
        } else {
            parent.setAttribute('data-node-collapsed', currentlyCollapsed ? '0' : '1');
        }

        if (shouldForceExpand) {
            markSubtreeForcedVisible(select, parentIndex, true);
        } else {
            markSubtreeForcedVisible(select, parentIndex, false);
        }

        const searchInput = document.getElementById('competencyselector_search');
        applySearchFilter(searchInput, select);
    }

    function markSubtreeForcedVisible(select, parentIndex, forceVisible) {
        const options = select.options;
        const parent = options[parentIndex];
        if (!parent) {
            return;
        }

        const rowtype = parent.getAttribute('data-rowtype');
        const parentLevel = parseInt(parent.getAttribute('data-level') || '0', 10);
        const frameworkid = parent.getAttribute('data-frameworkid') || '';

        for (let i = parentIndex + 1; i < options.length; i++) {
            const current = options[i];
            const currenttype = current.getAttribute('data-rowtype');
            const currentframework = current.getAttribute('data-frameworkid') || '';

            if (rowtype === 'framework') {
                if (currenttype === 'framework') {
                    break;
                }
            } else {
                if (currentframework !== frameworkid) {
                    break;
                }

                const currentlevel = parseInt(current.getAttribute('data-level') || '0', 10);
                if (currentlevel <= parentLevel) {
                    break;
                }
            }

            if (forceVisible) {
                current.setAttribute('data-force-visible', '1');
            } else {
                current.removeAttribute('data-force-visible');
            }
        }
    }

    function clearForcedVisible(select) {
        Array.prototype.forEach.call(select.options, function(option) {
            option.removeAttribute('data-force-visible');
        });
    }

    function isCollapsibleRow(option) {
        if (!option) {
            return false;
        }

        const rowtype = option.getAttribute('data-rowtype');
        if (rowtype === 'framework') {
            return true;
        }

        if (rowtype === 'competency') {
            return option.getAttribute('data-isleaf') !== '1';
        }

        return false;
    }

    function isFrameworkOption(value) {
        return typeof value === 'string' && value.indexOf('F') === 0;
    }

    function isActionableOption(option) {
        if (!option) {
            return false;
        }

        const selectable = option.getAttribute('data-selectable') === '1';
        if (!selectable) {
            return false;
        }

        if (isFrameworkOption(option.value)) {
            return false;
        }

        return /^[0-9]+$/.test(option.value);
    }

    return {
        init: init
    };
});
