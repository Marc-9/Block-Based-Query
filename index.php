<html>

<head>
	<link rel="stylesheet" href="css/third-party/bootstrap-v4-3-1.css">
	<script src="js/third-party/jquery-3.6.0.min.js"></script>
	<script src="js/third-party/jquery.betterdropdown.js"></script>
	<script src="js/third-party/bootstrap.min.js"></script>
	<script src='js/third-party/connections.js'></script>
	<script src="js/third-party/interact.min.js"></script>
	<script src="js/third-party/jquery.dataTables.min.js"></script>
	<script src='js/third-party/dragging.js'></script>
	<link rel="stylesheet" href="css/third-party/jquery.dataTables.min.css">
	<link rel="stylesheet" href="css/user-created/node.css">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/user-created/typeahead.css" />
	<link rel="stylesheet" href="css/third-party/jquery.betterdropdown.css" />
	<link rel="stylesheet" href="css/third-party/multiple-select.min.css" />
	<link rel="stylehseet" href="css/third-party/buttons.dataTables.min.css" />
	<title>Query System</title>
</head>

<body>
	<div class="containment">
		<div id="workspace_container">
			<select id="workspace">

			</select>
		</div>
		<button type="button" onclick="query()">Query</button>
		<button type="button" id="save" onclick="save()">Save</button>
		<button type="button" onclick="clear_nodes()">Reset</button>

		<div id="nodes">

		</div>
		<br>
		<br>
		<br>
		<p id="queue" style="display:none"></p>
		<img class="centered" id="loading" alt="loading symbol" style="display:none" src="load.gif">
		<div id="results">
		</div>
	</div>
</body>

</html>
<script>
	//Global window vars
	var root;
	var first;
	var nodes;
	var count = 1;
	var switch_bool = true;
	var overwrite = false;
	var workspace_id = -1;
	var interval_id;

	// A hidden root node is created to be parent to all future created nodes
	$(document).ready(function() {
		root = new node_function(0, null, "root");
		first = new node_function(1, root, count++);
		nodes = [root, first];
		root.giveChildren(first);
		make_node(1);
		get_workspaces("workspace");


	});

	// This is the code used for filtering
	$.fn.dataTable.ext.search.push(
		function(settings, data, dataIndex) {
			var min = parseInt($('#min').val(), 10);
			var max = parseInt($('#max').val(), 10);
			var age = parseFloat(data[1]) || 0; // use data for the age column

			if ((isNaN(min) && isNaN(max)) ||
				(isNaN(min) && age <= max) ||
				(min <= age && isNaN(max)) ||
				(min <= age && age <= max)) {
				return true;
			}
			return false;
		}
	);
</script>
<script src="js/user-created/window_functions.js"></script>
<script src="js/third-party/typeahead.min.js"></script>
<script src='js/user-created/node.js'></script>
<script src='js/user-created/functions-html.js'></script>
<script src='js/user-created/query.js'></script>
<script src='js/user-created/modal.js'></script>
<script src='js/user-created/generate_blocks.js'></script>
<script src='js/user-created/load_save.js'></script>
<script src='js/user-created/ComplexFunction.js'></script>
<script src='js/third-party/multiple-select.min.js'></script>
<script src='js/user-created/attributes.js'></script>
<script src='js/third-party/dataTables.buttons.min.js'></script>
<script src='js/third-party/buttons.colVis.min.js'></script>