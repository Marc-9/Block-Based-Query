class ComplexFunction {
    constructor(node_id, function_name, description, base64) {
        this.node_id = node_id;
        this.function_name = function_name;
        this.description = description;
        this.base64 = base64.replaceAll("_", "/").replaceAll("-", "+");
        if (this.base64.includes("~")) {
            this.base64 = this.base64.split("~")[1];
        }
        /* 
        this.unique_id = Math.floor(Math.random() * 1000);
        this.root = new node_function(0, null, `root${this.function_name}${this.node_id}${this.unique_id}`);
        this.nodes = [this.root];
        this.count = 1;
        this.nodes[0].children = [];*/
    }
}