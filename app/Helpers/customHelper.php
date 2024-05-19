<?php

function romanMonth($month) {
    $roman = ["", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
    $month = intval($month);

    if ($month >= 1 && $month <= 12) {
        return $roman[$month];
    }

    return "Month invalid";
}
