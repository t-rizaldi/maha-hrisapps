<?php



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
