
define('block_blumod/assign_resources', [], function() {
    const MIN_FILTER_CHARS = 3;

    function init() {
        const bluSelector = document.getElementById('bluselector');
        const resourcesContainer = document.getElementById('resources');
        if (!bluSelector || !resourcesContainer) {
            return;
        }

        if (bluSelector.value) {
            asignarBlu(bluSelector.value);
            llamarresources(bluSelector.value);
        }

        bluSelector.addEventListener('change', function(event) {
            asignarBlu(event.target.value);
            llamarresources(event.target.value);
        });

        document.querySelectorAll('.validate-selected').forEach(function(hiddenId) {
            hiddenId.addEventListener('click', function(event) {
                if (!bluSelector.value) {
                    event.preventDefault();
                }
            });
        });

        resourcesContainer.addEventListener('click', function(event) {
            const button = event.target.closest('button[data-action][data-from]');
            if (!button) {
                return;
            }
            executeAction(button.dataset.action, button.dataset.from, bluSelector);
        });
    }

    function executeAction(action, from, bluSelector) {
        if (!action || !from || !bluSelector.value) {
            return;
        }

        const control = document.getElementById(from);
        if (!control || !control.value) {
            return;
        }

        const bluid = bluSelector.value;
        asignarBlu(bluid);
        llamarresources(bluid, action, control.value);
    }

    function asignarBlu(bluid) {
        document.querySelectorAll('[name=id]').forEach(function(hiddenId) {
            hiddenId.value = bluid;
        });
    }

    function llamarresources(bluid, action, value) {
        const courseid = (new URLSearchParams(window.location.search)).get('courseid');
        if (!courseid) {
            return;
        }

        const params = new URLSearchParams({
            courseid: courseid,
            id: bluid
        });

        if (action && value) {
            params.set('action', action);
            params.set('modid', value);
        }

        const url = M.cfg.wwwroot + '/blocks/blumod/resources.php?' + params.toString();

        fetch(url)
            .then(function(response) {
                return response.text();
            })
            .then(function(data) {
                const resourcesContainer = document.getElementById('resources');
                if (!resourcesContainer) {
                    return;
                }
                resourcesContainer.innerHTML = data;
                bindSearchFilter();
            })
            .catch(function() {
                console.log('Error fetching resources');
            });
    }

    function bindSearchFilter() {
        const searchInput = document.getElementById('resourceselector_search');
        const availableSelect = document.getElementById('resourceselector_available');
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
            if (!doFilter) {
                option.hidden = false;
                return;
            }
            option.hidden = option.text.toLowerCase().indexOf(pattern) === -1;
        });
    }

    return {
        init: init
    };
});