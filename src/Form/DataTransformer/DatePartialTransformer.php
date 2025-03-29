<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class DatePartialTransformer implements DataTransformerInterface
{
    public function __construct(
        private bool $hasYear = true
    ) {}

    public function transform(mixed $dateString): mixed
    {
		if(empty($dateString))
			return [];

		$dateArray = explode("-", trim($dateString, "-"));

		$res = [
			"month" => (isset($dateArray[1]) and !empty($dateArray[1])) ? intval($dateArray[1]) : null,
			"day" => (isset($dateArray[2]) and !empty($dateArray[2])) ? intval($dateArray[2]) : null
		];

		if($this->hasYear) {
			$res["year"] = (isset($dateArray[0]) and !empty($dateArray[0])) ? (str_starts_with($dateString, "-") ? "-" : "").intval($dateArray[0]) : null;
		} else
			$res = [
				"month" => (isset($dateArray[0]) and !empty($dateArray[0])) ? intval($dateArray[0]) : null,
				"day" => (isset($dateArray[1]) and !empty($dateArray[1])) ? intval($dateArray[1]) : null
			];

		return $res;
    }

    public function reverseTransform(mixed $dataPartialArray): mixed
    {
		$month = $dataPartialArray["month"];
		$day = $dataPartialArray["day"];
		
		if(!empty($month))
			$month = str_pad($month, 2, "0", STR_PAD_LEFT);
		
		if(!empty($day))
			$day = str_pad($day, 2, "0", STR_PAD_LEFT);

		if(!isset($dataPartialArray["year"]))
			$dataPartialArray["year"] = null;

		return implode("-", array_filter([strval($dataPartialArray["year"]), $month, $day]));
    }
}