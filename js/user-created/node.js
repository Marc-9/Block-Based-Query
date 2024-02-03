class node_function {
    constructor(level, parent, id, deleted = false) {
        this.level = level;
        this.parent = parent;
        this.id = id;
        this.function = ""
        this.children = [];
        this.result = [];
        this.params = {};
        this.deleted = deleted;
        this.attributes = [];
    }

    // Creates a parent child relationship which is displayed on the screen with a line
    giveChildren(child) {
        this.children.push(child);
        $().connections({
            from: '#' + this.id,
            to: '#' + child.id
        });
    }
}


function add_child(id) {
    let unique_id = count++;
    make_node(unique_id)
    let parent_of_child = nodes[id]
    let child = new node_function(parent_of_child.level + 1, parent_of_child, unique_id)
    parent_of_child.giveChildren(child)
    nodes.push(child);
}

function add_sibling(id) {
    let unique_id = count++;
    make_node(unique_id)
    let sibling = nodes[id]
    let parent_of_sibling = sibling.parent;
    let new_sibling = new node_function(sibling.level, parent_of_sibling, unique_id)
    parent_of_sibling.giveChildren(new_sibling)
    nodes.push(new_sibling);
}

// Draws the node on the dom
function make_node(id) {
    let node_dom_content = $("#nodes");
    node_dom_content.append(
        `<div id="${id}" class="draggable">
            <button type="button" onclick="functions_modal(${id})">Add Function</button>
            <div id="cell${id}"></div>
            <br>
            <button type="button" onclick="add_child(${id})">Add Child</button>
            <button type="button" onclick="add_sibling(${id})">Add Sibling</button>
            <button type="button" onclick="delete_node(${id})">Delete</button>
        </div>`);
}

//Deletes node and all children
function delete_node(id) {
    let node_to_delete = nodes[id];
    let parent_of_deleted_node = node_to_delete.parent;
    let i = 0;
    for (i = 0; i < parent_of_deleted_node.children.length; i++) {
        if (parent_of_deleted_node.children[i] == node_to_delete) {
            break;
        }
    }
    //parent_of_deleted_node.children.splice(i, 1);
    for (let i = 0; i < node_to_delete.children.length; i++) {
        remove_node_from_dom(node_to_delete.children[i].id)
    }
    node_to_delete['function'] = "";
    node_to_delete['deleted'] = true;
    $("#" + id).remove();
    update_url(root);
}

//Helper recursive function for delete_node
function remove_node_from_dom(id) {
    let node = nodes[id]
    for (let i = 0; i < node.children.length; i++) {
        remove_node_from_dom(node.children[i].id)
    }
    $("#" + id).remove();
}

// Updates node with the values typed into its field
function update_params(param_name, node_id, flag) {
    let node_reference = nodes[node_id]
    if (flag) {
        // Check if it is a checkbox
        if (param_name == "period" || param_name == "qualifiers") {
            let index = node_reference.params[param_name].indexOf(flag);
            if (index !== -1) {
                node_reference.params[param_name].splice(index, 1);
            } else {
                node_reference.params[param_name].push(flag);
            }
        } else {
            if (param_name == 'result') {
                if (flag == 'homeoraway') {
                    $("#cell" + node_id).prepend("<div id = '" + node_id + "attributes'><button type='button' class='addAttribute' onclick='attributes_modal(" + node_id + ", 2)'>Add Attribute</button></div>");
                } else {
                    node_reference?.attributes.forEach(e => {
                        e.loaded = false;
                    })
                    $("#" + node_id + "attributes").remove();
                }
            }
            node_reference.params[param_name] = flag;
        }
    } else {
        node_reference.params[param_name] = $("#" + param_name + node_id).val()
    }
    update_url(root);
    //console.log(nodes)
}

function update_multiple_dropdown(id) {
    let node_id = -1;
    let node_reference = null;
    if (id.includes("worldcup")) {
        node_id = id.split("worldcup")[1];
        node_reference = nodes[node_id];
        node_reference.params['team'] = $("#" + id).multipleSelect('getSelects');
    } else if (id.includes("periodDropdown")) {
        node_id = id.split("periodDropdown")[1];
        node_reference = nodes[node_id];
        node_reference.params['period'] = $("#" + id).multipleSelect('getSelects').length > 0 ? $("#" + id).multipleSelect('getSelects') : ['full_time_et_opt'];
    }
}