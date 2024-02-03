/* exported */
class node_attribute {
    constructor(parent, id, functionName, loaded = false) {
        this.parent = parent;
        this.id = id;
        this.functionName = functionName
        this.params = {};
        this.loaded = loaded;
    }
}
/* exported */
function update_attribute_params(param_name, node_id, attribute_id, flag) {
    let node_reference = nodes[node_id];
    node_reference.attributes[attribute_id]['params'][param_name] = flag;
}