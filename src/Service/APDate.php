<?php
	namespace App\Service;
	
	class APDate
	{
	   /**
		* @param string $Langue
		* @param string $date
		*/
		public function doDate($language, $date)
		{
			if($date != null)
			{
				$date = date_format($date, "Y-m-d");
				if($date != "0000-00-00")
				{
					if($language == "fr")
					{
						$MoisFr = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
						$d = explode("-", $date);
						$dateF = $d[2]." ".$MoisFr[$d[1]-1]." ".$d[0];
					}
					else if($language == "es")
					{
						$MoisSp = array('Enero', 'Frebero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
						$d = explode("-", $date);
						$dateF = $d[2]." de ".$MoisSp[$d[1]-1]." de ".$d[0];
					}
					else
					{
						$MoisEn = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
						$d = explode("-", $date);
						$dateF = $d[2]."th ".$MoisEn[$d[1]-1]." ".$d[0];
					}
					return utf8_encode($dateF);
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
	}