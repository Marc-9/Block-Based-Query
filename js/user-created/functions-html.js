/* exported */
function games_by_team(id) {
    return `<fieldset id="homegroup${id}">
                <input type="radio" id="home${id}" onchange='update_params("homeaway",${id},"home")' name="homegroup${id}" />Home<br />
                <input type="radio" id="away${id}" onchange='update_params("homeaway",${id},"away")' name="homegroup${id}"/>Away<br />
                <input type="radio" id="homeaway${id}" onchange='update_params("homeaway",${id},"homeaway")' name="homegroup${id}"/>Home or Away<br />
            </fieldset>
            <br>
            ${create_dropdown(id, wc_teams(), "worldcup")}<br>`;
    //<input type='text' id='team${id}' class='typeahead tt-query' autocomplete='on' spellcheck='false' placeholder='Select Team'><br>`;
}

/* exported */
function games_by_score(id) {
    return `<fieldset id="homegroup${id}">
                <input type="radio" id="home${id}" onchange='update_params("homeaway",${id},"home")' name="homegroup${id}" />Home<br />
                <input type="radio" id="away${id}" onchange='update_params("homeaway",${id},"away")' name="homegroup${id}" />Away<br />
                <input type="radio" id="homeaway${id}" onchange='update_params("homeaway",${id},"homeaway")' name="homegroup${id}" />Home and Away<br />
                <input type="radio" id="homeoraway${id}" onchange='update_params("homeaway",${id},"homeoraway")' name="homegroup${id}" />Home or Away<br />
            </fieldset>         
            <br>
            <label>Operator:</label>
            <input type='text' id='operator${id}' onchange='update_params("operator",${id})'><br>
            <label>Score:</label>
            <input type='number' id='score${id}' onchange='update_params("score",${id})'><br>
            <button onclick="$('#advanced${id}').toggle()">Show Advanced</button>
            <div id="advanced${id}" style="display: none">
                <select onchange="update_multiple_dropdown(this.id)" id="periodDropdown${id}" multiple="multiple" class="multiple-select">
                    <option value="full_time">Full Time w/o Extra Time</option>
                    <option value="et_full_time">Full Time w/ Extra Time</option>
                    <option value="first_half">First Half</option>
                    <option value="second_half">Second Half</option>
                    <option value="et">Extra Time</option>
                    <option value="penalties">Penalties</option>
                </select>
                <br>
                <fieldset id="operation${id}">
                    <input type="radio" id="sum${id}" onchange='update_params("operation",${id},"sum")' name="operation${id}" />Sum<br />
                    <input type="radio" id="difference${id}" onchange='update_params("operation",${id},"difference")' name="operation${id}" />Difference<br />
                    <input type="radio" id="none${id}" onchange='update_params("operation",${id},"sum")' name="operation${id}" />None<br />
                </fieldset>
                <br>
                <input type="radio" id="homemathaway${id}" onchange='update_params("homeaway",${id},"homemathaway")' name="homegroup${id}" />Home [MATH] Away<br />
            </div>`;
}

function games_by_date(id) {
    return `<label>Operator:</label>
            <input type='text' id='operator${id}' onchange='update_params("operator",${id})''><br>
            <label'>Date:</label>
            <input type='date' id='date${id}' onchange='update_params("date",${id})'><br>`;
}

/* exported */
function games_by_event(id) {
    return `<fieldset id="homegroup${id}">
                <input type="radio" id="home${id}" onchange='update_params("homeaway",${id},"home")'name="homegroup${id}" />Home<br />
                <input type="radio" id="away${id}" onchange='update_params("homeaway",${id},"away")'name="homegroup${id}"/>Away<br />
                <input type="radio" id="homeaway${id}" onchange='update_params("homeaway",${id},"homeaway")' name="homegroup${id}"/>Home and Away<br />
                <input type="radio" id="homeoraway${id}" onchange='update_params("homeaway",${id},"homeoraway")' name="homegroup${id}"/>Home or Away<br />
            </fieldset>
            <br>
            <label>Choose Event Type:</label>
            <fieldset id="event${id}">
                <input type="radio" id="shots${id}" onchange='qualifiers_by_event(${id},"shots")' name="event${id}" />Shots<br />
                <input type="radio" id="goals${id}" onchange='qualifiers_by_event(${id},"goals")' name="event${id}" />Goals<br />
                <input type="radio" id="passes${id}" onchange='qualifiers_by_event(${id},"passes")' name="event${id}" />Passes<br />
            </fieldset>
            <div id="qualifier${id}"></div>
            <br>
            <label>Operator:</label>
            <input type='text' id='operator${id}' onchange='update_params("operator",${id})'><br>
            <label>Value:</label>
            <input type='number' id='value${id}' onchange='update_params("value",${id})'><br>
            
            <button onclick="$('#advanced${id}').toggle()">Show Advanced</button>
            <div id="advanced${id}" style="display: none">
                <fieldset id="operation${id}">
                    <input type="radio" id="sum${id}" onchange='update_params("operation",${id},"sum")' name="operation${id}" />Sum<br />
                    <input type="radio" id="difference${id}" onchange='update_params("operation",${id},"difference")' name="operation${id}" />Difference (Not Supported)<br />
                    <input type="radio" id="none${id}" onchange='update_params("operation",${id},"sum")' name="operation${id}" />None<br />
                </fieldset>
                <br>
                <input type="radio" id="homemathaway${id}" onchange='update_params("homeaway",${id},"homemathaway")' name="homegroup${id}" />Home [MATH] Away<br />
                <label>Start Time:</label>
                <input type='number' id='start${id}' onchange='update_params("start",${id})'><br>
                <label>End Time:</label>
                <input type='number' id='end${id}' onchange='update_params("end",${id})'><br>
                <fieldset id="period${id}">
                    <input type="checkbox" id="firsth${id}" onchange='update_params("period",${id},"1")' name="period${id}" />First Half
                    <input type="checkbox" id="secondh${id}" onchange='update_params("period",${id},"2")' name="period${id}" />Second Half<br />
                    <input type="checkbox" id="etfirsth${id}" onchange='update_params("period",${id},"3")' name="period${id}" />Extra Time First Half
                    <input type="checkbox" id="etsecondh${id}" onchange='update_params("period",${id},"4")' name="period${id}" />Extra Time Second Half<br />
                    <input type="checkbox" id="pen${id}" onchange='update_params("period",${id},"5")' name="period${id}" />Penalties
                </fieldset>
            </div>`;
    //<input type="radio" id="saves${id}" onchange='update_params("event",${id},"saves")' name="event${id}" />Saves<br />
    //<input type="radio" id="lossop${id}" onchange='update_params("event",${id},"lossop")' name="event${id}" />Loss of Possession<br />
}

/* exported */
function qualifiers_by_event(id, val) {
    let node_reference = nodes[id]
    node_reference.params.qualifiers = [];
    update_params("event", id, val);

    if (val == 'shots') {
        $("#qualifier" + id).html(`<label>Qualifiers:</label>
            <fieldset id="quals${id}">
                <input type="checkbox" id="SOnT${id}" onchange='update_params("qualifiers",${id},"SOnT")' name="quals${id}" />Shots on Target
                <input type="checkbox" id="SOffT${id}" onchange='update_params("qualifiers",${id},"SOffT")' name="quals${id}" />Shots off Target
                <input type="checkbox" id="blocked${id}" onchange='update_params("qualifiers",${id},"blocked")' name="quals${id}" />Blocked
            </fieldset>
        `);
    }
    if (val == 'goals') {
        $("#qualifier" + id).html(`<label>Qualifiers:</label>
            <fieldset id="quals${id}">
                <input type="checkbox" id="freekick${id}" onchange='update_params("qualifiers",${id},"freekick")' name="quals${id}" />Free Kick
                <input type="checkbox" id="owngoal${id}" onchange='update_params("qualifiers",${id},"owngoal")' name="quals${id}" />Own Goal
                <input type="checkbox" id="penalty${id}" onchange='update_params("qualifiers",${id},"penalty")' name="quals${id}" />Penalty
            </fieldset>
        `);
    }
    if (val == 'passes') {
        $("#qualifier" + id).html(`<label>Qualifiers:</label>
            <fieldset id="quals${id}">
                <input type="checkbox" id="successful${id}" onchange='update_params("qualifiers",${id},"successful")' name="quals${id}" />Successful
                <input type="checkbox" id="unsuccessful${id}" onchange='update_params("qualifiers",${id},"unsuccessful")' name="quals${id}" />Unsuccessful
                <input type="checkbox" id="assist${id}" onchange='update_params("qualifiers",${id},"assist")' name="quals${id}" />Assists
                <input type="checkbox" id="corner${id}" onchange='update_params("qualifiers",${id},"corner")' name="quals${id}" />Corners<br />
                <input type="checkbox" id="passforward${id}" onchange='update_params("qualifiers",${id},"passforward")' name="quals${id}" />Pass Forward
                <input type="checkbox" id="passbackward${id}" onchange='update_params("qualifiers",${id},"passbackward")' name="quals${id}" />Pass Backward
                <input type="checkbox" id="passsideway${id}" onchange='update_params("qualifiers",${id},"passsideway")' name="quals${id}" />Pass Sideway
            </fieldset>
        `);
    }
}

/* exported */
function games_by_result(id) {
    return `<fieldset id="result${id}">
                <input type="radio" id="home${id}" onchange='update_params("result",${id},"home")'name="result${id}" />Home<br />
                <input type="radio" id="away${id}" onchange='update_params("result",${id},"away")'name="result${id}"/>Away<br />
                <input type="radio" id="draw${id}" onchange='update_params("result",${id},"draw")' name="result${id}"/>Draw<br />
                <input type="radio" id="homeoraway${id}" onchange='update_params("result",${id},"homeoraway")' name="result${id}"/>Home or Away*<br />
            </fieldset>
            <p>* May only be used with Attribute</p>`;
}

function games_by_comp(id) {
    return `<fieldset id="comp${id}">
        <input type="radio" id="worldcup${id}" onchange='update_params("comp",${id},"worldcup")' name="compgroup${id}" />World Cup<br />
        <input type="radio" id="friendly${id}" onchange='update_params("comp",${id},"friendly")' name="compgroup${id}" />Friendly<br />
        </fieldset>`;
}

/* exported */
function attr_games_by_team(node_id, attribute_id) {
    let form_code = `<div id="${node_id}|${attribute_id}" name="games_by_team">
        <div class="form-group row">
            <label class="col-sm-2">
            Team
            </label>

            <div class="col-sm-10">
            ${create_dropdown(node_id, wc_teams(), "worldcup")}
            </div></div></div>`;
    return form_code;
}

/* exported */
function attr_games_by_team_result(node_id, attribute_id) {
    let form_code = `<div id="${node_id}|${attribute_id}" name="games_by_team">
        <fieldset id="homeattrfield${node_id}">
            <input type="radio" id="winattr${node_id}" name="resultgroupattr${node_id}" onchange='update_attribute_params("result",${node_id},${attribute_id}, "win")' />Win<br />
            <input type="radio" id="drawattr${node_id}" name="resultgroupattr${node_id}" onchange='update_attribute_params("result",${node_id},${attribute_id}, "draw")' />Draw<br />
            <input type="radio" id="lossattr${node_id}" name="resultgroupattr${node_id}" onchange='update_attribute_params("result",${node_id},${attribute_id}, "loss")'/>Loss<br />
        </fieldset>
        <br>
        <div class="form-group row">
            <label class="col-sm-2">
            Team
            </label>

            <div class="col-sm-10">
            ${create_dropdown(node_id, wc_teams(), "worldcup")}</div></div></div>`;
    return form_code;
}


function wc_teams() {
    return {
        "Group A": [[2092, "Qatar"], [1989, "Ecuador"], [2105, "Senegal"], [2069, "Netherlands"]],
        "Group B": [[1992, "England"], [2024, "Iran"], [1931, "USA"], [2153, "Wales"]],
        "Group C": [[1940, "Argentina"], [2103, "Saudi Arabia"], [2057, "Mexico"], [2089, "Poland"]],
        "Group D": [[1927, "France"], [1943, "Australia"], [1985, "Denmark"], [2139, "Tunisia"]],
        "Group E": [[2118, "Spain"], [1978, "Costa Rica"], [2006, "Germany"], [1928, "Japan"]],
        "Group F": [[1951, "Belgium"], [1966, "Canada"], [2063, "Morocco"], [1979, "Croatia"]],
        "Group G": [[1924, "Brazil"], [2106, "Serbia"], [2127, "Switzerland"], [1925, "Cameroon"]],
        "Group H": [[2090, "Portugal"], [2007, "Ghana"], [2147, "Uruguay"], [2035, "South Korea"]]
    }
}

function create_dropdown(node_id, data, id_name) {
    let form_code = `<select onchange="update_multiple_dropdown(this.id)" id="${id_name}${node_id}" multiple="multiple" class="multiple-select">`;
    for (const [key, value] of Object.entries(data)) {
        form_code += `<optgroup label='${key}'>`;
        value.forEach(e => {
            form_code += `<option value="${e[0]}">${e[1]}</option>`;
        })
        form_code += "</optgroup>";
    }
    return form_code += "</select>";
}
