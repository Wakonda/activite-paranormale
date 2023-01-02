<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class DatePartialTransformer implements DataTransformerInterface
{
    public function transform($dateString)
    {
		$dateArray = explode("-", $dateString);
		
		return [
			"year" => (isset($dateArray[0]) and !empty($dateArray[0])) ? intval($dateArray[0]) : null,
			"month" => (isset($dateArray[1]) and !empty($dateArray[1])) ? intval($dateArray[1]) : null,
			"day" => (isset($dateArray[2]) and !empty($dateArray[2])) ? intval($dateArray[2]) : null
		];
    }

    public function reverseTransform($dataPartialArray)
    {
		$month = $dataPartialArray["month"];
		$day = $dataPartialArray["day"];
		
		if(!empty($month))
			$month = str_pad($month, 2, "0", STR_PAD_LEFT);
		
		if(!empty($day))
			$day = str_pad($day, 2, "0", STR_PAD_LEFT);
		
		return implode("-", array_filter([strval($dataPartialArray["year"]), $month,$day]));
    }
}