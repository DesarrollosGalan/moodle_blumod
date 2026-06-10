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
            const button = event.target.closest('button[data-action][data-from]');
            if (!button) {
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

        if (isFrameworkOption(control.value)) {
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
        const pattern = searchInput.value.trim().toLowerCase();
        const doFilter = pattern.length >= MIN_FILTER_CHARS;

        Array.prototype.forEach.call(availableSelect.options, function(option) {
            if (isFrameworkOption(option.value)) {
                option.hidden = false;
                return;
            }
            if (!doFilter) {
                option.hidden = false;
                return;
            }
            option.hidden = option.text.toLowerCase().indexOf(pattern) === -1;
        });
    }

    function isFrameworkOption(value) {
        return typeof value === 'string' && value.indexOf('F') === 0;
    }

    return {
        init: init
    };
});
