<?php
	namespace App\Service;

	class APDate
	{
		public function doDate($language, $datetime, $excludeYear = false)
		{
			if($datetime != null)
			{
				$date = date_format($datetime, "Y-m-d");
				if($date != "0000-00-00")
				{
					if($language == "fr")
						$pattern = "d MMMM".(!$excludeYear ? " y" : "");
					else if($language == "es")
						$pattern = "d 'de' MMMM".(!$excludeYear ? " 'de' y" : "");
					else
						$pattern = "MMMM d".(!$excludeYear ? ", y" : "");

					$fmt = new \IntlDateFormatter($language, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE,\date_default_timezone_get(), \IntlDateFormatter::GREGORIAN, $pattern);

					return $fmt->format($datetime);
				}
			}

			return "-";
		}

		public function doDateTime($language, $datetime)
		{
			$dateString = $this->doDate($language, $datetime);

			if($dateString != "-")
			{
				$timeString = $datetime->format("H:i:s");
				switch($language)
				{
					case "fr":
						$dateTimeString = $dateString." à ".$datetime->format("H:i:s");
						break;
					case "en":
						$dateTimeString = $dateString." at ".$datetime->format("h:i:s A");
						break;
					case "es":
						$dateTimeString = $dateString." a las ".$datetime->format("H:i:s");
						break;
				}

				return $dateTimeString;
			}

			return "-";
		}

		private function getEra($date, $language) {
			$bc = false;
			if(str_starts_with($date, "-"))
				$bc = true;

			if($language == "fr")
				$era = $bc ? " av. J.-C." : "";
			else if($language == "es")
				$era = $bc ? " a. C." : "";
			else
				$era = $bc ? "BC" : "";

			return $era;
		}

		public function doPartialDate(?string $partialDate, $language)
		{
			$era = $this->getEra($partialDate, $language);
			$partialDate = trim($partialDate, "-");
			$dateArray = explode("-", $partialDate);

			if(empty($dateArray))
				return null;

			if(count($dateArray) == 1)
				return $dateArray[0].$era;

			if($language == "fr")
				$pattern = ((isset($dateArray[2]) and !empty($dateArray[2])) ? "d " : "")."MMMM y";
			else if($language == "es")
				$pattern = ((isset($dateArray[2]) and !empty($dateArray[2])) ? "d 'de' " : "")."MMMM 'de' y";
			else
				$pattern = "MMMM".((isset($dateArray[2]) and !empty($dateArray[2])) ? " d," : "")." y";

			$fmt = new \IntlDateFormatter($language, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE,\date_default_timezone_get(), \IntlDateFormatter::GREGORIAN, $pattern);

			return ucfirst($fmt->format(new \DateTime($partialDate))).$era;
		}
		
		public function doPartialDateTime(?string $partialDateTime, $language)
		{
			$dateTimeArray = explode(" ", $partialDateTime);
			
			if(empty($partialDateTime))
				return null;
			
			$dateString = $this->doPartialDate($dateTimeArray[0], $language);
			
			if(empty($dateTimeArray[1]))
				return $dateString;
			
			$word = "at";
			
			if($language == "fr")
				$word = "à";
			elseif($language == "es")
				$word = "a las";
			
			return $dateString." ${word} ".$dateTimeArray[1];
		}
		
		public function doYearMonthDayDate($day, $month, $year, $language) {
			if(empty($day) and empty($month) and empty($year))
				return null;

			if(!empty($day) and empty($month) and !empty($year))
				return null;

			if(empty($day) and empty($month) and !empty($year))
				return $year;

			$era = (!empty($year)) ? $this->getEra($year, $language) : null;
			$year = ltrim($year, "-");

			$months = array_map(
				function($i) use ($language) { 
					return (new \IntlDateFormatter($language, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE,\date_default_timezone_get(), \IntlDateFormatter::GREGORIAN, 'MMMM'))->format(mktime(0, 0, 0, $i, 1, 1970));
				}, range(1, 12)
			);	

			if($language == "fr")
				$dateString = ltrim($day, "0")." ".$months[$month-1].(!empty($year) ? " ".$year : "");
			else if($language == "es")
				$dateString = ((!empty($day)) ? ltrim($day, "0")." de " : "").$months[$month-1].(!empty($year) ? " de ".$year : "");
			else
				$dateString = $months[$month-1].((!empty($day)) ? " ".ltrim($day, "0") : "").(!empty($year) ? ", ".$year : "");

			return trim($dateString).$era;
		}

		public function shortDate($dateTime, $locale, $numberDigitYear = 4) {
			$formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
			$patern = $formatter->getPattern();
			$format = (preg_match('/\b(yy)\b/', $patern) and $numberDigitYear == 4) ? preg_replace('/\b(yy)\b/', 'yyyy', $patern) : $patern;

			$fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE, null, null, $format);

			return $fmt->format($dateTime);
		}
	}