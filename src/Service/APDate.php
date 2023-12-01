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
			$dateString = utf8_decode($this->doDate($language, $datetime));
			
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
				
				return utf8_encode($dateTimeString);
			}
			
			return "-";
		}
		
		public function doPartialDate(?string $partialDate, $language)
		{
			$dateArray = explode("-", $partialDate);
			
			if(empty($dateArray))
				return null;
			
			if(count($dateArray) == 1)
				return $dateArray[0];
			
			$year = $dateArray[0];	
			$month = $dateArray[1];

			if($language == "fr")
			{
				$monthFrench = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
				$dateString = ((isset($dateArray[2]) and !empty($dateArray[2])) ? intval($dateArray[2])." " : "").$monthFrench[$dateArray[1]-1]." ".$dateArray[0];
			}
			else if($language == "es")
			{
				$monthSpain = array('Enero', 'Frebero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
				$dateString = ((isset($dateArray[2]) and !empty($dateArray[2])) ? $dateArray[2]." de " : "").$monthSpain[$dateArray[1]-1]." de ".$dateArray[0];
			}
			else
			{
				$monthEnglish = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
				$dateString = ((isset($dateArray[2]) and !empty($dateArray[2])) ? $dateArray[2]."th " : "").$monthEnglish[$dateArray[1]-1]." ".$dateArray[0];
			}

			return $dateString;
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
		
			if($language == "fr") {
				$monthFrench = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
				$dateString = ltrim($day, "0")." ".$monthFrench[$month-1]." ".$year;
			}
			else if($language == "es") {
				$monthSpain = array('Enero', 'Frebero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
				$dateString = ((!empty($day)) ? ltrim($day, "0")." de " : "").$monthSpain[$month-1].(!empty($year) ? " de ".$year : "");
			}
			else {
				$dt = new \DateTime(trim("$year-$month-$day", "-"));

				$monthEnglish = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
				$dateString = ((!empty($day)) ? $dt->format("jS")." " : "").$monthEnglish[$month-1]." ".$year;
			}

			return $dateString;
		}
		
		public function shortDate($dateTime, $locale, $numberDigitYear = 4) {
			$formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
			$patern = $formatter->getPattern();
			$format = (preg_match('/\b(yy)\b/', $patern) and $numberDigitYear == 4) ? preg_replace('/\b(yy)\b/', 'yyyy', $patern) : $patern;

			$fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE, null, null, $format);

			return $fmt->format($dateTime);
		}
	}