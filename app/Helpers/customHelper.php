<?php

use App\Models\Holiday;

/*============================
        STATUS LABEL
============================*/

function structureApprovalStatusLabel($status=0)
{
    $label ='';

    switch ($status) {
        case 0 :
            $label = 'Diperiksa Manager';
            break;
        case 1 :
            $label = 'Diperiksa GM';
            break;
        case 2 :
            $label = 'Diperiksa HRD';
            break;
        case 3 :
            $label = 'Diperiksa Direktur';
            break;
        case 4 :
            $label = 'Diperiksa Komisaris';
            break;
        case 5 :
            $label = 'Approved';
            break;
        case 6 :
            $label = 'Ditolak Manager';
            break;
        case 7 :
            $label = 'Ditolak GM';
            break;
        case 8 :
            $label = 'Ditolak HRD';
            break;
        case 9 :
            $label = 'Ditolak Direktur';
            break;
        case 10 :
            $label = 'Ditolak Komisaris';
            break;
        case 11 :
            $label = 'Proses Input';
            break;

        default:
            $label = 'Pending -';
            break;
    }

    return $label;
}

function totalDays($startDate, $endDate)
{
    $date1 = strtotime($startDate);
    $date2 = strtotime($endDate);
    $distance = $date2 - $date1;

    $day = ($distance / 60 / 60 / 24) + 1;
    return $day;
}

function totalSunday($startDate, $endDate)
{
    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    $sundayCount = 0;
    // Loop melalui setiap hari dalam rentang waktu
    for ($currentTimestamp = $startTimestamp; $currentTimestamp <= $endTimestamp; $currentTimestamp += 86400) {
        // 86400 detik = 1 hari
        $currentDayOfWeek = date('N', $currentTimestamp);
        // Jika hari ini adalah Minggu (7 adalah hari Minggu dalam format ISO-8601)
        if ($currentDayOfWeek == 7) {
            $sundayCount++;
        }
    }
    return $sundayCount;
}

function distributionPermitDay($startDate, $endDate)
{
    [$firstYear, $firstMonth, $firstDay] = explode('-', $startDate);
    [$lastYear, $lastMonth, $lastDay] = explode('-', $endDate);

    $totalFirstDay = 0;
    $totalSecondDay = 0;

    if ($firstMonth == $lastMonth && $lastDay < 29) {
        $totalFirstDay = totalDays($startDate, $endDate);
        $totalFirstDay -= totalSunday($startDate, $endDate);
        $totalFirstDay -= Holiday::whereBetween('holidays_date', [$startDate, $endDate])->count();
    }

    if ($firstMonth == $lastMonth && $lastDay > 28) {
        // hitung bulan pertama
        $totalFirstDay = totalDays($startDate, "$lastYear-$lastMonth-28");
        $totalFirstDay -= totalSunday($startDate, "$lastYear-$lastMonth-28");
        $totalFirstDay -= Holiday::whereBetween('holidays_date', [$startDate, "$lastYear-$lastMonth-28"])->count();

        // hitung bulan kedua
        $totalSecondDay = totalDays("$lastYear-$lastMonth-29", $endDate);
        $totalSecondDay -= totalSunday("$lastYear-$lastMonth-29", $endDate);
        $totalSecondDay -= Holiday::whereBetween('holidays_date', ["$lastYear-$lastMonth-29", $endDate])->count();
    }

    if ($firstMonth != $lastMonth && $firstDay > 28 && $lastDay < 29) {
        $totalFirstDay = totalDays($startDate, $endDate);
        $totalFirstDay -= totalSunday($startDate, $endDate);
        $totalFirstDay -= Holiday::whereBetween('holidays_date', [$startDate, $endDate])->count();
    }

    if ($firstMonth != $lastMonth && $firstDay < 29 && $lastDay < 29) {
        // hitung bulan pertama
        $totalFirstDay = totalDays($startDate, "$firstYear-$firstMonth-28");
        $totalFirstDay -= totalSunday($startDate, "$firstYear-$firstMonth-28");
        $totalFirstDay -= Holiday::whereBetween('holidays_date', [$startDate, "$firstYear-$firstMonth-28"])->count();

        // hitung bulan kedua
        $totalSecondDay = totalDays("$firstYear-$firstMonth-29", $endDate);
        $totalSecondDay -= totalSunday("$firstYear-$firstMonth-29", $endDate);
        $totalSecondDay -= Holiday::whereBetween('holidays_date', ["$firstYear-$firstMonth-29", $endDate])->count();
    }

    return [
        'firstMonth'  => $totalFirstDay,
        'secondMonth'    => $totalSecondDay
    ];
}
