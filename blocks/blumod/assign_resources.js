document.addEventListener("DOMContentLoaded", function() {
    let bluSelector = document.getElementById("bluselector");
    if (!bluSelector) {
        return;
    }

    let bluid = bluSelector.value;
    if (bluid) {
        asignarBlu(bluid);
        llamarresources(bluid);
    }

    bluSelector.addEventListener('change', function(event) {
        asignarBlu(event.target.value);
        llamarresources(event.target.value);
    });

    document.querySelectorAll(".validate-selected").forEach((hiddenId) => {
        hiddenId.addEventListener('click', function(event) {
            if (!bluSelector.value) {
                event.preventDefault();
            }
        });
    });

    document.getElementById("resources").addEventListener('click', function(e) {
        if (e.target.matches('button[data-action][data-from]')) {
            ejecutarEvento(e.target.dataset.action, e.target.dataset.from);
        }
    });
});



function ejecutarEvento(action, from) {
    if (!action || !from) {
        return;
    }

    if (!document.getElementById("bluselector").value) {
        return;
    }
    var bluid = document.getElementById("bluselector").value;
    var control = document.getElementById(from);

    if (!control || !control.value) {
        return;
    }

    var value = document.getElementById(from).value;

    asignarBlu(bluid);
    llamarresources(bluid, action, value);
}

function asignarBlu(bluid) {
    document.querySelectorAll("[name=id]").forEach((hiddenId) => {
        hiddenId.value = bluid
    });
}

function llamarresources(bluid, action, value) {
    var courseid = (new URLSearchParams(window.location.search)).get('courseid');
    if (!courseid) {
        return;
    }

    var url = 'resources.php?courseid=' + courseid + '&id=' + bluid;
    if (action && value) {
        url += '&action=' + action + '&modid=' + value;
    }

    fetch(url)
        .then(function(response) {
            return response.text();
        })
        .then(function(data) {
            document.getElementById("resources").innerHTML = data;
        })
        .catch(function() {
            console.log('Error fetching resources');
        });
}