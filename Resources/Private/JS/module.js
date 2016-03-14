

document.querySelector('#update-button').addEventListener('click', function(e){

    // toggle button and log
    document.querySelector('#update-button').classList.add('hidden');
    document.querySelector('#update-log').classList.remove('hidden');

    startUpdate();


    /**
     * start update with continuous message
     */
    function startUpdate() {
        if (!!window.EventSource) {

            var source = new EventSource(PARAMS.url);

            source.addEventListener('message', function(e) {
                try
                {
                    // handle updated event
                    handleResponse(JSON.parse(e.data));
                }
                catch (e) {
                    console.log(e);
                }

            }, false);

            source.addEventListener('closing', function(e) {
                source.close();
                console.log("connection closed");
            }, false);

            source.addEventListener('open', function(e) {
                console.log("Connection was opened.");
            }, false);

            source.addEventListener('error', function(e) {
                console.log("Error - connection was lost.");
                if (e.readyState == EventSource.CLOSED) {

                }
            }, false);
        }
    }

    /**
     * handles the given response
     * @param {Object} json
     */
    function handleResponse(json) {
        var ul   = document.querySelector('#result');
        var list = document.querySelector('#result li.step-'+json.step);
        var icon = document.querySelector('#result li.step-'+json.step+' i');
        var text = document.querySelector('#result li.step-'+json.step+' span');

        if (!list) {
            list = ul.appendChild(document.createElement("li"));
            icon = list.appendChild(document.createElement("i"));
            text = list.appendChild(document.createElement("span"));
        }

        // set classes and icon
        list.className = "step-"+json.step+" list-group-item "+getNodeClass(json.state);
        icon.className = "pull-right fa "+getIconClass(json.state);

        if (json.msg) {
            // set message
            text.textContent = json.msg;
        }
    }

    /**
     * returns the list item class
     * @param {int} state
     * @returns {string}
     */
    function getNodeClass(state) {
        switch(state) {
            case 0: return "list-group-item-danger";
            case 1: return "list-group-item-success";
            case 2: return "text-muted";
            case 3: return "list-group-item-warning";
        }
    }

    /**
     * returns the icon class
     * @param {int} state
     * @returns {string}
     */
    function getIconClass(state) {
        switch(state) {
            case 0: return "fa-close";
            case 1: return "fa-check";
            case 2: return "fa-spinner fa-pulse";
            case 3: return "fa-exclamation-triangle";
        }
    }
});