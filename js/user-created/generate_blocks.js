$(document).ready(function () {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    let coded = urlParams.get('nodes')
    if (coded) {
        new_workspace(coded)
        $('#save').prop("disabled", false);
    }
    //update_url(root);
});

/* exported */
function generate_blocks(node) {
    let node_id = count;
    let new_node = new node_function(node.level, node.parent, node_id, node.deleted);
    new_node.params = node.params;
    new_node.attributes = node.attributes ?? [];
    nodes.push(new_node);
    count++;
    if (!node.deleted && node.level != 0) {
        make_node(node_id)
        populate_node_in_dom(node.function, node_id);
        new_node.attributes.forEach(e => {
            if (e.loaded) {
                if ($("#" + node_id + "attributes").length == 0) {
                    $("#cell" + node_id).prepend("<div id = '" + node_id + "attributes'><button type='button' class='addAttribute' onclick='attributes_modal(" + node_id + ", 2)'>Add Attribute</button></div>");
                }
                $("#" + node_id + "attributes").append(`<div id='${node_id}~${e.id}' class='circle' onclick='edit_attibute(${node_id}, ${e.id})'>${e.functionName}</div>`);
            }
        })
        new_node.params = node.params;
        fill_params(node.params, node_id);
    }

    node.children.forEach(element => {
        let child = generate_blocks(element);
        new_node.giveChildren(child);
        child.parent = new_node
    });
    return new_node;
}

function fill_params(params, node_id) {
    // For checkbox inputs
    let checkbox = ["homeaway", "operation", "result", "comp"];
    checkbox.forEach(e => {
        if (checked = params[e]) {
            // A gross check I need to do to display the add attribute button for games by team
            // as the add arttribute tag should only be visible if the home or away option is selected
            if (checked == 'homeoraway' && e == 'result' && $("#" + node_id + "attributes").length == 0) {
                $("#cell" + node_id).prepend("<div id = '" + node_id + "attributes'><button type='button' class='addAttribute' onclick='attributes_modal(" + node_id + ", 2)'>Add Attribute</button></div>");
            }
            document.getElementById(checked + node_id).checked = true;
        }
    })
    // For radio checkboxes
    if (checked = params.period) {
        checked.forEach(e => {
            if (isNaN(e)) {
                //document.getElementById(e + node_id).checked = true;
            } else {
                if (e == 1) document.getElementById("firsth" + node_id).checked = true;
                else if (e == 2) document.getElementById("secondh" + node_id).checked = true;
                else if (e == 3) document.getElementById("etfirsth" + node_id).checked = true;
                else if (e == 4) document.getElementById("etsecondh" + node_id).checked = true;
                else if (e == 5) document.getElementById("pen" + node_id).checked = true;
            }
            // The above is an edge case where I have interesting values for periods, I believe
            // this was to make queries easier as they match up with the period ids
        })
    }

    // Radio checkboxes for qualifiers, which require they are visisble in the function
    if (checked = params.event) {
        document.getElementById(checked + node_id).checked = true;
        let temp = params.qualifiers;
        qualifiers_by_event(node_id, checked);
        // This is because the above funciton clears qualifiers
        let node_reference = nodes[node_id]
        node_reference.params.qualifiers = temp;
        params.qualifiers = temp;
    }
    if (checked = params.qualifiers) {
        checked.forEach(e => {
            document.getElementById(e + node_id).checked = true;
        })
    }
    // Filling in user-given inputs
    checkbox = ["operator", "score", "value", "start", "end", "date"]
    checkbox.forEach(e => {
        if (val = params[e]) {
            document.getElementById(e + node_id).value = val;
        }
    })
    // Multi-Select Boxes
    checkbox = { "team": "worldcup", "period": "periodDropdown" };
    Object.keys(checkbox).forEach(function (k) {
        if (val = params[k]) {
            let select = $(`#${checkbox[k]}${node_id}`)
            try {
                select.multipleSelect('setSelects', val);
            }
            catch (error) { }
        }
    });

}
