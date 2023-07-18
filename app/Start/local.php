<?php


function randomPassword($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
    $special_chars = '!@#$%&*';
    $max = strlen($chars) - 1;
    $max_special_chars = strlen($special_chars) - 1;

    for ($i = 0; $i < ($length - 2); $i++) {
        $n = rand(0, $max);
        $password[] = $chars[$n];
    }

    for ($i = 0; $i < 2; $i++) {
        $n = rand(0, $max_special_chars);
        $password[] = $special_chars[$n];
    }

    return str_shuffle(implode($password));
}

function format_date($string, $format = 'm/d/Y')
{
    $date = new DateTime($string);
    return $date->format($format);
}

function mysql_date($string)
{
    $date = new DateTime($string);
    return $date->format('Y-m-d');
}

function format_timestamp($string)
{
    $date = new DateTime($string);
    return $date->format('m/d/Y h:i:s A');
}

function formatNumber($value)
{
    return number_format($value, 2, '.', '');
}

function formatCurrency($value)
{
    $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    return $formatter->formatCurrency($value, 'USD');
}

function options($data, $key, $value)
{
    $results = [];

    foreach ($data as $item) {
        $results[$item->$key] = $item->$value;
    }

    return $results;
}

function limit($value, $min, $max)
{
    if ($value < $min) return $min;
    if ($value > $max) return $max;

    return $value;
}

function contract_name($contract)
{
  
    return $contract->ContractName != null ?
        $contract->ContractName->name :
        $contract->PaymentType->name;
    /*$contract->contract_type->name;*///  change to payment type name on 25 july 2019
}

function days($date1, $date2)
{
    $day = floatval(60 * 60 * 24);
    $diff = floatval(strtotime($date2) - strtotime($date1));
    return ceil(0.5 + ($diff / $day));
}

function months($date1, $date2)
{
    $date1 = new DateTime($date1);
    $date2 = new DateTime($date2);
    $diff = $date1->diff($date2);

    return ceil(($diff->y * 12) + ($diff->d / 30.4368) + $diff->m);
}

function compare_date($date1, $date2)
{
    return strtotime($date2) - strtotime($date1);
}

function number_to_month($month_number, $format)
{
    $dateObj = DateTime::createFromFormat('!m', $month_number);
    return $monthName = $dateObj->format($format);
}

function getPassword()
{
    return 'hospital';
}

function getDeploymentDate($feature)
{
    if ($feature == "ContractRateUpdate") {
        $deployment_date = date('m/d/Y', strtotime("2020-12-14"));
    }
    $deployment_start_date = with(new DateTime($deployment_date))->setTime(0, 0, 0);
    return $deployment_start_date;
}
