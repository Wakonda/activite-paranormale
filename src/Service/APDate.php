<?php
	namespace App\Service;

	class APDate
	{
		public function doDate($locale, $datetime, $excludeYear = false)
		{
			if($datetime != null)
			{
				$date = date_format($datetime, "Y-m-d");
				if($date != "0000-00-00")
				{
					$skeleton = !$excludeYear ? 'YYYYMMMMd' : 'MMMMd';
					$pattern = $this->getFormat($locale);

					$fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE,\date_default_timezone_get(), \IntlDateFormatter::GREGORIAN, $pattern);

					return $fmt->format($datetime);
				}
			}

			return "-";
		}

		public function doDateTime($locale, $datetime)
		{
			if($datetime == null)
				return "-";

			$formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM);
			return $formatter->format($datetime);
		}

		public function getFormat($locale, $skeleton = 'YYYYMMMMd') {
			$patternGenerator = new \IntlDatePatternGenerator($locale);
			return $patternGenerator->getBestPattern($skeleton);
		}

		public function doPartialDate(?string $partialDate, $locale)
		{
			$isBC = str_starts_with($partialDate, "-");
			$partialDate = trim($partialDate, "-");
			$dateArray = explode("-", $partialDate);

			if(empty(array_filter($dateArray)))
				return null;

			if (!preg_match('/^(-?\d{1,4})(-\d{2})?(-\d{2})?$/', $partialDate) and !preg_match('/^(-?\d{1,4})(-\d{2})?$/', $partialDate))
				return $partialDate;

			$bc = "";
			if($isBC)
				$bc = " G";
			
			$day = null;
			$month = null;
			$year = null;

			if(count($dateArray) == 1) {
				$skeleton = "YYYY".$bc;
				$year = $dateArray[0];
			} elseif(isset($dateArray[2]) and !empty($dateArray[2])) {
				$skeleton = 'YYYYMMMMd'.$bc;
				$day = $dateArray[2];
				$month = $dateArray[1];
				$year = $dateArray[0];
			} else {
				$skeleton = 'YYYYMMMM'.$bc;
				$month = $dateArray[1];
				$year = $dateArray[0];
			}

			$pattern = $this->getFormat($locale, $skeleton);

			$fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE,\date_default_timezone_get(), \IntlDateFormatter::GREGORIAN, $pattern);

			$cal = \IntlCalendar::fromDateTime(new \DateTime($partialDate));
			if(!empty($day))
				$cal->set(\IntlCalendar::FIELD_DAY_OF_MONTH, $day);
			if(!empty($month))
				$cal->set(\IntlCalendar::FIELD_MONTH, $month - 1);
			if(!empty($year))
				$cal->set(\IntlCalendar::FIELD_EXTENDED_YEAR, ($isBC ? "-" : "").str_pad($year, 4, "0", STR_PAD_LEFT));

			return $this->removeZero(ucfirst($fmt->format($cal)));
		}
		
		public function doPartialDateTime(?string $partialDateTime, $locale)
		{
			$dateTimeArray = explode(" ", $partialDateTime);

			if(empty($partialDateTime))
				return null;

			$dateString = $this->doPartialDate($dateTimeArray[0], $locale);

			if(empty($dateTimeArray[1]))
				return $dateString;

			if(empty($dateTimeArray[0]))
				return $dateTimeArray[0];

			$dateExploded = explode("-", trim($dateTimeArray[0], "-"));
			$year = isset($dateExploded[0]) ? $dateExploded[0] : null;
			$month = isset($dateExploded[1]) ? $dateExploded[1] : null;
			$day = isset($dateExploded[2]) ? $dateExploded[2] : null;

			$skeleton = 'YYYYMMMMd';

			if(!empty($year) and !empty($month) and empty($day))
				$skeleton = 'YYYYMMMM';
			elseif(!empty($year) and empty($month) and empty($day))
				$skeleton = 'YYYY';

			if(count($dateTimeArray) == 2) {
				$formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM);
				return $formatter->format(new \DateTime($partialDateTime));
			} elseif(count($dateTimeArray) == 1) {
				$formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM);
				return $formatter->format(new \DateTime($partialDateTime.":00"));
			}

			$pattern = $this->getFormat($locale, $skeleton);
			$fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, \date_default_timezone_get(), \IntlDateFormatter::GREGORIAN, $pattern);

			return $this->removeZero(ucfirst($fmt->format(new \DateTime($partialDateTime))));
		}

		public function doYearMonthDayDate($day, $month, $year, $locale) {
			if(empty($day) and empty($month) and empty($year))
				return null;

			if(!empty($day) and empty($month) and !empty($year))
				return null;

			if(empty($day) and empty($month) and !empty($year))
				return $year;

			$skeleton = 'YYYYMMMMd';

			if(empty($year))
				$skeleton = 'MMMMd';
			else
				$skeleton .= (str_starts_with($year, "-") ? " G" : "");

			$pattern = $this->getFormat($locale, $skeleton);
			$fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, \date_default_timezone_get(), \IntlDateFormatter::GREGORIAN, $pattern);
			
			if(empty($year))
				$year = date("Y");
			
			$dateString = rtrim($year."-".$month."-".$day, "-");

			return $this->removeZero(ucfirst($fmt->format(new \DateTime($dateString))));
		}

		public function shortDate($dateTime, $locale, $numberDigitYear = 4) {
			$formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
			$pattern = $formatter->getPattern();
			$format = (preg_match('/\b(yy)\b/', $pattern) and $numberDigitYear == 4) ? preg_replace('/\b(yy)\b/', 'yyyy', $pattern) : $pattern;

			$fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE, null, null, $format);

			return $fmt->format($dateTime);
		}
		
		public function removeZero(string $input): string {
			return preg_replace('/\b0*(\d+)\b/', '$1', $input);
		}
		
		private function removeBC(string $input): string {
			return preg_replace('/\s-\s*(\d+)/', ' $1', $input);
		}
		
		private function removeAll(string $input): string {
			$input = $this->removeZero($input);
			return $this->removeBC($input);
		}
	}