<?php
function mmToFrac($mm) {
    // Conversion factor from millimeters to inches
    $inches = $mm / 25.4;

    // Calculate feet and remaining inches
    $feet = floor($inches / 12);
    $remainingInches = $inches - ($feet * 12);

    // Separate the whole number part of remaining inches
    $wholeInches = floor($remainingInches);

    // Calculate the fractional part
    $fractionalPart = $remainingInches - $wholeInches;

    // Convert the fractional part to the nearest 16th
    $nearestSixteenth = round($fractionalPart * 16);

    // Simplify the fraction
    $gcd = gcd($nearestSixteenth, 16);
    $numerator = $nearestSixteenth / $gcd;
    $denominator = 16 / $gcd;

    // If the numerator is 16, increment the whole inches
    if ($numerator == 16) {
        $wholeInches += 1;
        $numerator = 0;
    }

    // Construct the fraction part
    $fraction = $numerator == 0 ? "" : "-$numerator/$denominator";

    // Return the result as a string
    return "$feet' $wholeInches$fraction\"";
}

// Helper function to calculate the greatest common divisor
function gcd($a, $b) {
    while ($b != 0) {
        $t = $b;
        $b = $a % $b;
        $a = $t;
    }
    return $a;
}

function kgToLbs($kg) {
    // Conversion factor from kilograms to pounds
    $conversionFactor = 2.20462;

    // Convert kilograms to pounds
    $lbs = $kg * $conversionFactor;

    // Return the integer value of pounds
    return (int)round($lbs);
}

function dateToWeekNumber($date) {
    // Create a DateTime object from the input date
    $dateTime = new DateTime($date);

    // Get the year and the ISO-8601 week number
    $year = $dateTime->format('y');
    $weekNumber = $dateTime->format('W');

    // Combine the year and week number to form the YYWW format
    $yyww = $year . $weekNumber;

    return $yyww;
}
