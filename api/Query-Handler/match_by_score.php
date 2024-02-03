<?php
function build_query(string $half, string $homeaway, string &$sql, string $operation, array &$period): void {
    if ($homeaway == 'home') {
        if ($half == 'first_half') $sql .= return_sql(1, [1]);
        if ($half == 'penalties') $sql .= return_sql(1, [7]);
        if ($half == 'et') $sql .= return_sql(4, [5, 3]);
        if ($half == 'second_half') $sql .= return_sql(4, [3, 1]);
        if ($half == 'full_time') $sql .= return_sql(1, [3]);
        if ($half == 'et_full_time') $sql .= return_sql(1, [5]);
    } else if ($homeaway == 'away') {
        if ($half == 'first_half') $sql .= return_sql(1, [2]);
        if ($half == 'penalties') $sql .= return_sql(1, [8]);
        if ($half == 'et') $sql .= return_sql(4, [6, 4]);
        if ($half == 'second_half') $sql .= return_sql(4, [4, 2]);
        if ($half == 'full_time') $sql .= return_sql(1, [4]);
        if ($half == 'et_full_time') $sql .= return_sql(1, [6]);
    } else if ($homeaway == 'homemathaway') {
        if ($operation == '-' && count($period) == 1) {
            if ($half == 'first_half') $sql .= return_sql(2, [1, 2]);
            if ($half == 'penalties') $sql .= return_sql(2, [7, 8]);
            if ($half == 'et') $sql .= return_sql(5, [5, 3, "-", 6, 4]);
            if ($half == 'second_half') $sql .= return_sql(5, [3, 1, "-", 4, 2]);
            if ($half == 'full_time') $sql .= return_sql(2, [3, 4]);
            if ($half == 'et_full_time') $sql .= return_sql(2, [5, 6]);
        } else {
            if ($half == 'first_half') $sql .= return_sql(3, [1, 2]);
            if ($half == 'penalties') $sql .= return_sql(3, [7, 8]);
            if ($half == 'et') $sql .= return_sql(5, [5, 3, "+", 6, 4]);
            if ($half == 'second_half') $sql .= return_sql(5, [3, 1, "+", 4, 2]);
            if ($half == 'full_time') $sql .= return_sql(3, [3, 4]);
            if ($half == 'et_full_time') $sql .= return_sql(3, [5, 6]);
        }
    }
    $sql .= $operation;
}

function return_sql(int $flag, array $ids): string {
    if ($flag == 1) return "sum(case when ma.attribute_id = $ids[0] then ma.attribute_value end)";
    else if ($flag == 2) return "(sum(case when ma.attribute_id = $ids[0] then ma.attribute_value when ma.attribute_id = $ids[1] then -ma.attribute_value end))";
    else if ($flag == 3) return "sum(case when ma.attribute_id in ($ids[0],$ids[1]) then ma.attribute_value end)";
    else if ($flag == 4) return "sum(case when ma.attribute_id = $ids[0] then ma.attribute_value when ma.attribute_id = $ids[1] then -ma.attribute_value end)";
    else if ($flag == 5) return "(sum(case when ma.attribute_id = $ids[0] then ma.attribute_value when ma.attribute_id = $ids[1] then -ma.attribute_value end) $ids[2] sum(case when ma.attribute_id = $ids[3] then ma.attribute_value when ma.attribute_id = $ids[4] then -ma.attribute_value end))";
}
