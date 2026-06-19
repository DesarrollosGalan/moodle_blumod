define('block_blumod/assign_resources_blu', [], function() {
    const MIN_FILTER_CHARS = 3;

    function init() {
        const resourceSelector = document.getElementById('resourceselector_resources');
        const blusContainer = document.getElementById('blus');
        if (!resourceSelector || !blusContainer) {
            return;
        }

        bindResourceFilter();

        if (resourceSelector.value) {
            loadBlusForResource(resourceSelector.value);
        }

        resourceSelector.addEventListener('change', function(event) {
            const resourceid = event.target.value;
            loadBlusForResource(resourceid);
        });

        // Some browsers/UIs delay change events for select interactions.
        resourceSelector.addEventListener('click', function() {
            if (resourceSelector.value) {
                loadBlusForResource(resourceSelector.value);
            }
        });

        blusContainer.addEventListener('click', function(event) {
            const button = event.target.closest('button[data-action][data-from]');
            if (!button) {
                return;
            }

            const from = button.dataset.from;
            const control = document.getElementById(from);
            if (!control || !control.value || !resourceSelector.value) {
                return;
            }

            loadBlusForResource(resourceSelector.value, button.dataset.action, control.value);
        });
    }

    function loadBlusForResource(resourceid, action, bluid) {
        const blusContainer = document.getElementById('blus');
        if (!blusContainer) {
            return;
        }

        if (!resourceid) {
            blusContainer.innerHTML = '';
            return;
        }

        const courseid = (new URLSearchParams(window.location.search)).get('courseid');
        if (!courseid) {
            return;
        }

        const params = new URLSearchParams({
            courseid: courseid,
            resourceid: resourceid
        });

        if (action && bluid) {
            params.set('action', action);
            params.set('bluid', bluid);
        }

        const url = M.cfg.wwwroot + '/blocks/blumod/resources_blu.php?' + params.toString();

        fetch(url)
            .then(function(response) {
                return response.text();
            })
            .then(function(html) {
                blusContainer.innerHTML = html;
            })
            .catch(function() {
                console.log('Error fetching BLUs for resource');
            });
    }

    function bindResourceFilter() {
        const searchInput = document.getElementById('resourceselector_search');
        const availableSelect = document.getElementById('resourceselector_resources');
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