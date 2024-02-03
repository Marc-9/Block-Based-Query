function query() {
    update_url(root);
    $("#loading").toggle();
    $("#queue").toggle();

    let nodes_to_json = JSON.parse(convert_to_json(root));
    remove_null(nodes_to_json);
    $.ajax({
        url: "api/query",
        type: 'post',
        data: JSON.stringify(nodes_to_json),
        success: function (data) {
            interval_id = setInterval(function () {
                check_query_status(data.message)
            }, 5000);

        },
        error: function () {
            aQueryFailed();
        }
    })

}

function check_query_status(uid) {
    $.ajax({
        url: "api/queryStatus?uid=" + uid,
        type: 'get',
        success: function (data) {
            if (data.message == 'finished') {
                clearInterval(interval_id);
                get_query_data(uid)
            }
            else {
                $("#queue").text(data.message);
            }

        },
        error: function () {
            aQueryFailed();
        }
    })
}

function get_query_data(uid) {
    $.ajax({
        url: "api/queryResult?uid=" + uid,
        type: 'get',
        success: function (data) {
            $("#queue").toggle();
            $("#loading").toggle();
            $("#results").html(`<button type="button" id="analysisbutton" onclick="match_analysis(${data.games})">Analysis</button><br/>` + data.table);
            $('#myTable').DataTable({
                dom: 'Blfrtip',
                buttons: [
                    'columnsToggle'
                ],
                "columnDefs": [
                    { "visible": false, "targets": [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67], className: 'hidden' }
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All'],
                ],
                order: [[67, 'desc']],
            });



        },
        error: function () {
            aQueryFailed();
        }
    })
}

function aQueryFailed(queue = True) {
    $("#loading").toggle();
    if (queue) $("#queue").toggle();
    alert('failure');
}

/**
 * Due to a bug in typeahead the full string may not be in the node's param value if the user clicked on the name from the dropdown
 * Add to this function as you give more functions a typeahead
*/
function add_missing_data(node) {
    if (node.function == "games_by_team") {
        //node.params["team"] = $("#team" + node.id).val()
    }
}
var analysistable;
function match_analysis(...args) {
    $("#analysisModal").remove();
    $("#loading").toggle();
    $.ajax({
        url: "/api/analysis",
        type: 'post',
        data: JSON.stringify(args),
        success: function (data) {
            $("#loading").toggle();
            $("body").append(data.html);
            // This works but I worry it may cause issues when more than 1 table exists
            // This does cause issues with the main table when filtering and closing the modal
            data.ids.forEach(e => {
                analysistable = $(`#${e}`).DataTable();
                $('#min, #max').keyup(function () {
                    analysistable.draw();
                });
            });
            openAnalysis();
            document.getElementById("analysisbutton").onclick = function () { openAnalysis(); }
        },
        error: function () {
            aQueryFailed(False);
        }
    })
}

function openAnalysis() {
    $('#analysisModal').modal('show');
    analysistable.draw();
}

function reset_values() {
    document.getElementById("min").value = "";
    document.getElementById("max").value = "";
}