<?php

// Age Count
function ageCount($date){
	$birthDate = new DateTime($date);
	$today = new DateTime("today");
	if ($birthDate > $today) {
	    return "0 tahun 0 bulan 0 hari";
	}
	$y = $today->diff($birthDate)->y;
	return "$y Tahun" ;
}

function numberText($number)
{

    $resultNumber = '';

    switch ($number) {
        case 1:
            $resultNumber = 'satu';
            break;
        case 2:
            $resultNumber = 'dua';
            break;
        case 3:
            $resultNumber = 'tiga';
            break;
        case 4:
            $resultNumber = 'empat';
            break;
        case 5:
            $resultNumber = 'lima';
            break;
        case 6:
            $resultNumber = 'enam';
            break;
        case 7:
            $resultNumber = 'tujuh';
            break;
        case 8:
            $resultNumber = 'delapan';
            break;
        case 9:
            $resultNumber = 'sembilan';
            break;
        case 10:
            $resultNumber = 'sepuluh';
            break;
        case 11:
            $resultNumber = 'sebelas';
            break;
        case 100:
            $resultNumber = 'seratus';
            break;
        case 1000:
            $resultNumber = 'seribu';
            break;
        default :
            $resultNumber = '';
            break;
    }

    return $resultNumber;
}

function numericText($number) {
    $resultText = '';

    if(strlen($number) < 3) $resultText = numericTwoDigit($number);
    if(strlen($number) == 3) $resultText = numericThreeDigit($number);
    if(strlen($number) == 4) $resultText = numericFourDigit($number);
    if(strlen($number) == 5) $resultText = numericFiveDigit($number);
    if(strlen($number) == 6) $resultText = numericSixDigit($number);
    if(strlen($number) == 7) $resultText = numericSevenDigit($number);
    if(strlen($number) == 8) $resultText = numericEightDigit($number);
    if(strlen($number) == 9) $resultText = numericNineDigit($number);

    return $resultText;
}

function numericTwoDigit($numeric) {

    $numericText = '';

    if($numeric <= 11) {
        $numericText = numberText($numeric);
    }

    if($numeric > 11 && $numeric < 20) {
        $text = substr($numeric, -1, 1);
        $numericText = numberText($text) . ' belas';
    }

    if($numeric >= 20 && $numeric < 100) {
        $number1 = numberText(substr($numeric,0,1));
        $number2 = numberText(substr($numeric,-1,1));
        $numericText = "$number1 puluh $number2";
    }

    return $numericText;
}

function numericThreeDigit($numeric) {
    $numericText ='';
    $number1 = substr($numeric,0,1);

    if($number1 == 1) {
        $numericText = 'seratus ';
    } else {
        $numericText = (numberText($number1)) ? numberText($number1) . ' ratus ' : '';
    }

    $numericText .= numericTwoDigit(substr($numeric,1));

    return $numericText;
}

function numericFourDigit($numeric) {
    $numericText ='';
    $number1 = substr($numeric,0,1);

    if($number1 == 1) {
        $numericText = 'seribu ';
    } else {
        $numericText = (numberText($number1)) ? numberText($number1) . ' ribu ' : '';
    }

    $numericText .= numericThreeDigit(substr($numeric,1));

    return $numericText;
}

function numericFiveDigit($numeric) {
    $numericText ='';

    $numericText = numericTwoDigit(substr($numeric,0,2)) . ' ribu ' . numericThreeDigit(substr($numeric,2));

    return $numericText;
}


function numericSixDigit($numeric) {
    $numericText ='';

    $numericText = (numericThreeDigit(substr($numeric,0,3))) ? numericThreeDigit(substr($numeric,0,3)) . ' ribu ' . numericThreeDigit(substr($numeric,3)) : '';

    return $numericText;
}


function numericSevenDigit($numeric) {
    $numericText ='';

    $numericText = numberText(substr($numeric,0,1)) . ' juta ' . numericSixDigit(substr($numeric,1));

    return $numericText;
}


function numericEightDigit($numeric) {
    $numericText ='';

    $numericText = numericTwoDigit(substr($numeric,0,2)) . ' juta ' . numericSixDigit(substr($numeric,2));

    return $numericText;
}


function numericNineDigit($numeric) {
    $numericText ='';

    $numericText = numericThreeDigit(substr($numeric,0,3)) . ' juta ' . numericSixDigit(substr($numeric,3));

    return $numericText;
}
