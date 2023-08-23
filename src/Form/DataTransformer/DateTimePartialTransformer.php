<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class DateTimePartialTransformer implements DataTransformerInterface
{
    public function transform($dateString)
    {
		$timeArray = [];
		$dateTimeArray = explode(" ", $dateString);
		$dateArray = explode("-", $dateTimeArray[0]);
		
		if(isset($dateTimeArray[1]))
			$timeArray = explode(":", $dateTimeArray[1]);

		return [
			"year" => (isset($dateArray[0]) and !empty($dateArray[0])) ? intval($dateArray[0]) : null,
			"month" => (isset($dateArray[1]) and !empty($dateArray[1])) ? intval($dateArray[1]) : null,
			"day" => (isset($dateArray[2]) and !empty($dateArray[2])) ? intval($dateArray[2]) : null,
			"hour" => (isset($timeArray[0]) and !empty($timeArray[0])) ? intval($timeArray[0]) : null,
			"minute" => (isset($timeArray[1]) and !empty($timeArray[1])) ? intval($timeArray[1]) : null
		];
    }

    public function reverseTransform($dataPartialArray)
    {
		$month = $dataPartialArray["month"];
		$day = $dataPartialArray["day"];
		$hour = $dataPartialArray["hour"];
		$minute = $dataPartialArray["minute"];
		
		if(!empty($month))
			$month = str_pad($month, 2, "0", STR_PAD_LEFT);
		
		if(!empty($day))
			$day = str_pad($day, 2, "0", STR_PAD_LEFT);
		
		if(!empty($hour))
			$hour = str_pad($hour, 2, "0", STR_PAD_LEFT);
		
		if(!empty($day))
			$minute = str_pad($minute, 2, "0", STR_PAD_LEFT);
			
		$time = implode(":", array_filter([$hour, $minute]));
		
		return implode("-", array_filter([strval($dataPartialArray["year"]), $month, $day])).(!empty($time) ? " ".$time : "");
    }
}