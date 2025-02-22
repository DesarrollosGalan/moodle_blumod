document.addEventListener("DOMContentLoaded", function() {
    if (document.getElementById("bluselector").value) {
        llamarSiblings(document.getElementById("bluselector").value);
    }

    document.getElementById("bluselector").addEventListener('change', function(event) {
        llamarSiblings(event.target.value);
    });

    document.querySelectorAll(".validate-selected").forEach((hiddenId) => {
        hiddenId.addEventListener('click', function(event) {
            if (!document.getElementById("bluselector").value) {
                event.preventDefault();
            }
        });
    });

    document.getElementById("siblings").addEventListener('click', function(e) {
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

    llamarSiblings(bluid, action, value);
}

function llamarSiblings(bluid, action, value) {
    document.querySelectorAll("[name=id]").forEach((hiddenId) => {
        hiddenId.value = bluid
    });

    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            document.getElementById("siblings").innerHTML = this.responseText;
        }
    };
    var url = 'siblings.php?id=' + bluid;
    if (action && value) {
        url += '&action=' + action + '&modid=' + value;
    }
    xmlhttp.open('GET', url);
    xmlhttp.send();
}