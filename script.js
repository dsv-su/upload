function ajaxRequest(action, datalist, callback) {
    const request = new XMLHttpRequest();
    request.open('POST', "./?action=" + action, true);
    request.send(datalist);

    request.onreadystatechange = function() {
        if (request.readyState == 4) {
            var json_response = '';
            try {
                json_response = JSON.parse(request.responseText);
            } catch(error) {
                console.log(request.responseText);
            }
            callback(json_response);
        }
    };
}

function make_element(html) {
    const template = document.createElement('template');
    template.innerHTML = html;
    return template.content.firstChild;
}

function share_key(event) {
    console.log('keypress', event)
    if(event.key && event.key != "Enter") {
    }
    return share(event);
}

function share_change(event) {
    console.log('change', event);
}

function share_input(event) {
    if(event instanceof InputEvent) {
        const input = event.currentTarget;
        switch(event.inputType) {
        case 'insertText':
            const parent = event.currentTarget.parentNode.parentNode;
            const pills = parent.querySelectorAll(
                '.existing > .pill > .label');
            const existing = [];
            pills.forEach(function pushUser(pill) {
                existing.push(pill.dataset['user']);
            });
            return suggest_user(input, existing);
            break;
        case 'insertReplacementText':
            event.preventDefault();
            const options = input.list.options;
            for(var i = 0; i < options.length; i++) {
                var option = options[i];
                if(option.value == event.data) {
                    input.value = option.textContent;
                    input.dataset['user'] = option.value;
                    break;
                }
            }
            // weird focusing bug requires a timeout...
            window.setTimeout(function select() {
                input.select();
            }, 0);
            break;
        }
    } else { // Chrome is an idiot as usual
        event.preventDefault();
        const input = event.target;
        const user = input.value;
        const options = input.list.options;
        for(var i = 0; i < options.length; i++) {
            var option = options[i];
            if(option.value == user) {
                input.value = option.textContent;
                input.dataset['user'] = option.value;
                break;
            }
        }
        input.select();
    }
}

function share(event) {
    event.preventDefault();
    const div = event.currentTarget.parentNode;
    const input = div.querySelector('input[type="text"]');
    const suggestions = input.list;
    if(suggestions.length < 1) {
        return;
    }
    const data = new FormData();
    data.append('item', input.dataset['uuid']);
    data.append('user', input.dataset['user']);
    ajaxRequest('share', data, function addPill(response) {
        if(response['ok'] === false) {
            return;
        }
        const existing = div.parentNode.querySelector('.existing');
        existing.appendChild(make_element(response['html']));
        input.value = '';
    });
}

function unshare(event) {
    event.preventDefault();
    const pill = event.currentTarget.parentNode;
    const label = pill.querySelector('.label');
    const data = new FormData();
    data.append('item', label.dataset['item']);
    data.append('user', label.dataset['user']);

    ajaxRequest('unshare', data, function removePill(response) {
        pill.parentNode.removeChild(pill);
    });
}

function suggest_user(input, existing) {
    const render = function(response) {
        const suggestlist = input.list;
        while(suggestlist.firstChild) {
            suggestlist.removeChild(suggestlist.firstChild);
        }
        const suggestions = response['suggestions'];
        for(var i = 0; i < suggestions.length; i++) {
            const user = suggestions[i]['user'];
            if(existing.indexOf(user) != -1) {
                continue;
            }
            var next = document.createElement('option');
            next.value = user;
            next.textContent = suggestions[i]['name']
            next.dataset['user'] = user;
            suggestlist.appendChild(next);
        }
    }
    data = new FormData();
    data.append('input', input.value);
    ajaxRequest('suggest', data, render);
}
