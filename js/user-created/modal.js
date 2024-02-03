function doModal(modal_id, header, content, footer) {
    html = `<div id="${modal_id}" class="modal" tabindex="-1" role="dialog" aria-labelledby="confirm-modal" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            ${header}
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                        <div class="modal-footer">
                            ${footer}
                        </div>
                    </div>
                </div>
            </div>`;

    $('body').append(html);
    $(`[id='${modal_id}']`).modal({
        backdrop: 'static',
        keyboard: false,
    });
    $(`[id='${modal_id}']`).modal('show')

}

/* exported */
function functions_modal(id) {
    doModal(`modal${id}`, "<h4>Choose a Function</h4>", return_functions(id), `<button type='button' class='btn btn-primary' onclick='populate_node_in_dom("complex", ${id})'>Load</button><button type="button" class="btn btn-danger" onclick="remove_modal('modal${id}')">Close</button>`);
    get_workspaces("complex_functions_dropdown");
}

/* exported */
function attributes_modal(id, flag) {
    doModal(`modal${id}`, "<h4>Choose an Attribute</h4>", return_attributes(id, flag), `<button type='button' class='btn btn-primary' onclick='populate_attribute_in_dom(${id}, ${nodes[id].attributes.length})'>Load</button><button type="button" class="btn btn-danger" onclick="remove_modal('modal${id}')">Close</button>`);
}

function remove_modal(id) {
    $(`[id='${id}']`).modal('hide');
    $(`[id='${id}']`).remove();
}


function return_functions(id) {
    return `
        <button type="button" onclick="populate_node_in_dom('games_by_team',${id})">Games By Team</button>
	    <button type="button" onclick="populate_node_in_dom('games_by_score',${id})">Games By Score</button>
		<button type="button" onclick="populate_node_in_dom('games_by_date',${id})">Games By Date</button>
        <button type="button" onclick="populate_node_in_dom('games_by_event',${id})">Games By Event</button>
        <button type="button" onclick="populate_node_in_dom('games_by_result',${id})">Games By Result</button>
        <button type="button" onclick="populate_node_in_dom('games_by_comp',${id})">Games By Competition</button>
        <hr>
        <div id="complex_functions_container">
        <select id="complex_functions_dropdown">
        </select>
        </div>`;
}

function return_attributes(id, flag) {
    if (flag == 1) {
        return `<button type="button" onclick="populate_attribute_in_modal('games_by_team',${id})">Team Attribute</button>`;
    } else if (flag == 2) {
        return `<button type="button" onclick="populate_attribute_in_modal('games_by_team_result',${id})">Team Attribute</button>`;
    }
}

function populate_attribute_in_modal(attribute_name, node_id) {
    let node_reference = nodes[node_id]
    let attr = new node_attribute(node_id, node_reference.attributes.length, attribute_name);
    node_reference.attributes.push(attr);
    if (attribute_name == "games_by_team") {
        $("#modal" + node_id + " .modal-body").html(attr_games_by_team(node_id, attr.id));
        $(`#worldcup${node_id}`).multipleSelect()
    }
    if (attribute_name == 'games_by_team_result') {
        $("#modal" + node_id + " .modal-body").html(attr_games_by_team_result(node_id, attr.id));
        $(`#worldcup${node_id}`).multipleSelect()
    }

}

function populate_node_in_dom(function_name, node_id) {
    let node_reference = nodes[node_id]
    if (function_name == "games_by_team") {
        $("#cell" + node_id).html(games_by_team(node_id));
        node_reference.function = 'games_by_team';
        node_reference.params = { 'team': [] };
        $(`#worldcup${node_id}`).multipleSelect();
        //start_typeahead('team' + node_id, 'team');
    } else if (function_name == "games_by_score") {
        $("#cell" + node_id).html(games_by_score(node_id));
        node_reference.function = "games_by_score";
        node_reference.params = {};
        node_reference.params['period'] = ['full_time_et_opt'];
        $(`#periodDropdown${node_id}`).multipleSelect({
            selectAll: false
        });
    } else if (function_name == "games_by_date") {
        $("#cell" + node_id).html(games_by_date(node_id));
        node_reference.function = 'games_by_date';
        node_reference.params = {};
    } else if (function_name == "games_by_event") {
        $("#cell" + node_id).html(games_by_event(node_id));
        $("#cell" + node_id).prepend("<div id = '" + node_id + "attributes'><button type='button' class='addAttribute' onclick='attributes_modal(" + node_id + ", 1)'>Add Attribute</button></div>");
        node_reference.function = 'games_by_event';
        node_reference.params = {};
        node_reference.params['qualifiers'] = [];
        node_reference.params['period'] = [];
    } else if (function_name == "games_by_result") {
        $("#cell" + node_id).html(games_by_result(node_id));
        node_reference.function = 'games_by_result';
        node_reference.params = {};
    } else if (function_name == 'complex') {
        let val = $('#complex_functions_dropdown').find(":selected").val() ?? node_reference.params.complex_function.base64;
        let description = $('#complex_functions_dropdown').find(":selected").data()?.displayBelowText ?? node_reference.params.complex_function.description;
        let func_name = $('#complex_functions_dropdown').find(":selected").text() == "" ? node_reference.params.complex_function.function_name : $('#complex_functions_dropdown').find(":selected").text();
        $("#cell" + node_id).html(`${func_name}<br>${description}`);
        let complex_node = new ComplexFunction(node_id, func_name, description, val);
        node_reference.function = 'complex';
        node_reference.params = { "complex_function": complex_node };
    }
    else if (function_name == 'games_by_comp') {
        $("#cell" + node_id).html(games_by_comp(node_id));
        node_reference.function = 'games_by_comp';
        node_reference.params = {};
    }
    remove_modal(`modal${node_id}`);

}

function populate_attribute_in_dom(node_id, attribute_id, flag = true) {
    let node_reference = nodes[node_id]
    let attr_name = document.getElementById(`${node_id}|${attribute_id}`).getAttribute('name')
    node_reference.attributes[attribute_id]['loaded'] = true;
    if (attr_name == 'games_by_team') {
        node_reference['attributes'][attribute_id]['params']['teams'] = [];
        $("#worldcup" + node_id).find(":selected").each((i, e) => {
            node_reference['attributes'][attribute_id]['params']['teams'].push(e.value)
        });
    }
    if (flag) {
        $("#" + node_id + "attributes").append(`<div id='${node_id}~${attribute_id}' class='circle' onclick='edit_attibute(${node_id}, ${attribute_id})'>${attr_name}</div>`);
    }
    remove_modal(`modal${node_id}`);
    update_url(root);
}

function start_typeahead(id_of_element_in_dom, table_name_to_query) {
    $('#' + id_of_element_in_dom).typeahead({
        name: 'typeahead',
        remote: {
            url: '/api/' + table_name_to_query + 'search?key=%QUERY',
            filter: function (parsedResponse) {
                return parsedResponse.message;
            }
        },
        limit: 10
    });
}

function edit_attibute(node_id, attribute_id) {
    let attribute_reference = nodes[node_id]['attributes'][attribute_id]
    if (attribute_reference['functionName'] == 'games_by_team' || attribute_reference['functionName'] == 'games_by_team_result') {
        let body = "";
        if (attribute_reference['functionName'] == 'games_by_team') body = attr_games_by_team(node_id, attribute_id);
        else if (attribute_reference['functionName'] == 'games_by_team_result') body = attr_games_by_team_result(node_id, attribute_id);
        doModal(`modal${node_id}`, "<h4>Edit Attribute</h4>", body, `<button type="button" class="btn btn-danger" onclick="delete_attribute(${node_id}, ${attribute_id})">Delete</button><button type='button' class='btn btn-primary' onclick='populate_attribute_in_dom(${node_id}, ${attribute_id}, false)'>Load</button><button type="button" class="btn btn-danger" onclick="remove_modal('modal${node_id}')">Close</button>`);
        $(`#worldcup${node_id}`).multipleSelect();
        let select = $(`#worldcup${node_id}`)
        try {
            select.multipleSelect('setSelects', attribute_reference['params']['teams'])
        }
        catch (error) { }
        if (attribute_reference['functionName'] == 'games_by_team_result') {
            document.getElementById(attribute_reference['params']['result'] + "attr" + node_id).checked = true
        }
    }

}

function delete_attribute(node_id, attribute_id) {
    let attribute_reference = nodes[node_id]['attributes'][attribute_id]
    attribute_reference['loaded'] = false;
    document.getElementById(`${node_id}~${attribute_id}`).remove()
    remove_modal(`modal${node_id}`)
    update_url(root)

}