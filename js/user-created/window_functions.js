/** 
 * Nodes to Json
 * @param {node} window's root node
 * @returns {string} valid json representation of node structure
*/

function convert_to_json(root) {
    let seen = [];

    return JSON.stringify(root, function (key, node) {
        if (node != null && typeof node == "object") {
            if (seen.indexOf(node) >= 0 || node.deleted) {
                return;
            }
            add_missing_data(node)

            seen.push(node);
        }
        return node;
    });
}

/** 
 * Update nodes arg in url
 * @param {node} window's root node
 * @returns {void}
*/
// I had another workspace show nulls as children for some reason
function update_url(root) {
    nodes.forEach(e => {
        let node_attribute_size = e?.attributes?.length ?? 0;
        while (node_attribute_size--) {
            if (!e.attributes[node_attribute_size].loaded) {
                e.attributes.splice(node_attribute_size, 1);
            }
        }
    })
    let nodes_to_json = JSON.parse(convert_to_json(root));
    remove_null(nodes_to_json);
    encoded = btoa(JSON.stringify(nodes_to_json)).replaceAll("/", "_").replaceAll("+", "-").replaceAll("=", "");
    if (encoded == "eyJsZXZlbCI6MCwicGFyZW50IjpudWxsLCJpZCI6InJvb3QiLCJmdW5jdGlvbiI6IiIsImNoaWxkcmVuIjpbXSwicmVzdWx0IjpbXSwicGFyYW1zIjp7fSwiZGVsZXRlZCI6ZmFsc2UsImF0dHJpYnV0ZXMiOltdfQ") {
        return;
    }
    window.history.replaceState({}, 'Query Sytem', '?nodes=' + encoded);

}

function remove_null(remove_nodes) {
    if (remove_nodes === null || remove_nodes === undefined) {
        return
    }
    let size = remove_nodes.children.length;
    while (size--) {
        remove_null(remove_nodes.children[size]);
        if (remove_nodes.children[size] === null) {
            remove_nodes.children.splice(size, 1);
        }
    }
    return;

}

// Changes the values to percentages in the analysis tab
function percentage() {
    let table = $("#teams").DataTable();
    table.rows().every(function (index) {
        let row = table.row(index);
        let games_count = row.data()[1];
        if (switch_bool) {
            table.cell(row, 2).data((row.data()[2] / games_count * 100).toPrecision(3) + "%").draw();
            table.cell(row, 3).data((row.data()[3] / games_count * 100).toPrecision(3) + "%").draw();
            table.cell(row, 4).data((row.data()[4] / games_count * 100).toPrecision(3) + "%").draw();
        } else {
            table.cell(row, 2).data(Math.round(parseFloat(row.data()[2]) / 100 * games_count)).draw();
            table.cell(row, 3).data(Math.round(parseFloat(row.data()[3]) / 100 * games_count)).draw();
            table.cell(row, 4).data(Math.round(parseFloat(row.data()[4]) / 100 * games_count)).draw();
        }
    })

    switch_bool = !switch_bool;
}

/* exported */
function new_workspace(coded) {
    nodes = [root];
    count = 1;
    coded = coded.replaceAll("_", "/").replaceAll("-", "+");
    nodes[0].children = [];
    $("#nodes").html("");
    let encodedString = window.atob(coded);
    let json = JSON.parse(encodedString);
    json.children.forEach(element => {
        let child = generate_blocks(element);
        nodes[0].giveChildren(child);
        child.parent = nodes[0];
    })
}


function clear_nodes() {
    $("#nodes").html(`<div id="1" class="draggable">
            <button type="button" onclick="functions_modal(1)">Add Function</button>
            <div id="cell1"></div>
            <br>
            <button type="button" onclick="add_child(1)">Add Child</button>
            <button type="button" onclick="add_sibling(1)">Add Sibling</button>
            <button type="button" onclick="delete_node(1)">Delete</button>
        </div>`);
    count = 1;
    root = new node_function(0, null, "root");
    first = new node_function(1, root, count++);
    nodes = [root, first];
    root.giveChildren(first);
    overwrite = false;
    $('#workspace').betterDropdown({
        destroy: true
    });
    $('#workspace').betterDropdown({
        displayTextBelow: true
    });
    update_url(root);
}
