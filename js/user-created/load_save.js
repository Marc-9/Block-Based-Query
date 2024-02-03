function load() {
    let val = $('#workspace').find(":selected").val();
    workspace_id = val.split("~")[0];

    new_workspace(val.split("~")[1]);
    update_url(root);
    overwrite = true;
}

function load_complex(node_id) {
    let val = $('#complex_functions_dropdown').find(":selected").val();
    workspace_id = val.split("~")[0];
}


function save() {
    update_url(root)
    doModal("savemodal", "Save Workspace", save_html(), `<button type="button" class="btn btn-danger" onclick="remove_modal('savemodal')">Close</button>`);
}

function save_html() {
    // check is overwrite is set, if it is pre-populate and have a button for save new/overwirte
    let name = "";
    let description = "";
    if (overwrite) {
        name = $('#workspace').find(":selected").text();
        description = $('#workspace').find(":selected").data()['displayBelowText'];
    }
    let json_object = convert_to_json(root);
    let html = `
        <form id="workspace_form">
        <div class="form-group">
            <label for="name">Workspace Title</label>
            <input type="email" class="form-control" name="workspace_name" value="${name}">
        </div>
        <div class="form-group">
            <label for="name">Workspace Description</label>
            <textarea class="form-control" name="workspace_description" rows="4">${description}</textarea>
        </div>
        <input type="hidden" name="json_object" value="${btoa(json_object).replaceAll("/", "_").replaceAll("+", "-").replaceAll("=", "")}">
        <input type="hidden" name="wid" value="${workspace_id}">
        <button type="button" onclick="save_workspace(-1)">Save New</button>`;
    if (overwrite) {
        html += `<button type="button" onclick="save_workspace()">Overwrite</button>`
    }
    return html += "</form>";

}

function save_workspace(flag) {
    let api_url = "/api/workspace"
    if (flag == -1) {
        api_url = "/api/workspace?workspace_id=-1";
    }
    $.ajax({
        url: api_url,
        type: 'post',
        data: $("#workspace_form").serialize(),
        success: function (data) {
            if (data['response_code'] == 200) {
                $('#workspace').betterDropdown({
                    destroy: true
                });
                if (!data['message']['updated']) {
                    workspace_id = data['message']['id'];
                } else {
                    let find_option = new RegExp('(' + data['message']['id'] + '~)');
                    $("#workspace").children().each((i, item) => {
                        if (find_option.test(item.value)) {
                            item.remove();
                        }
                    })
                }
                overwrite = true;
                $("#workspace").append(`<option value="${data['message']['id']}~${data['message']['json_object']}" data-display-below-text="${data['message']['description']}" selected>${data['message']['name']}</option>`,)
                $('#workspace').betterDropdown({
                    displayTextBelow: true
                });
                $('.dropdown-box-text').text(data['message']['name'])
            }
        },
        error: function () {
            console.log('failure');
        }
    });
}

function get_workspaces(select_id) {
    $.ajax({
        url: "api/workspace",
        type: 'get',
        success: function (data) {
            select_data = JSON.parse(data.message);
            select_data.forEach(e => {
                $("#" + select_id).append(`<option value="${e['id']}~${e['json_object']}" data-display-below-text="${e['description']}">${e['name']}</option>`)
            })
            $('#' + select_id).betterDropdown({
                displayTextBelow: true
            });
        },
        error: function () {
            console.log(data);
            console.log("fail");
        }
    })
}

