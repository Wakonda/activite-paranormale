<?php

	namespace App\Service;
	
	class Currency
	{
		public static function getlanguageCountry(string $language): string {
			return ["fr" => "fr_FR", "en" => "en_US", "es" => "es_ES"][$language];
		}

		public static function getCryptoCurrencies(): array
		{
			return [
				"Bitcoin",
				"Ethereum",
				"Dogecoin",
				"Cardano",
				"Zilliqa"
			];
		}

		public static function getCurrencies(): array
		{
			return [
				[
					"realName" => "Real",
					"iso4217" => "BRL",
					"abbr" => "R$"
				],
				[
					"realName" => "Australian dollar",
					"iso4217" => "AUD",
					"abbr" => "A$"
				],
				[
					"realName" => "Canadian dollar",
					"iso4217" => "CAD",
					"abbr" => "C$"
				],
				[
					"realName" => "Yuan",
					"iso4217" => "CNY",
					"abbr" => "¥"
				],
				[
					"realName" => "Koruna česká",
					"iso4217" => "CZK",
					"abbr" => "Kč"
				],
				[
					"realName" => "Danske kroner",
					"iso4217" => "DKK",
					"abbr" => "DKK"
				],
				[
					"realName" => "Euro",
					"iso4217" => "EUR",
					"abbr" => "€"
				],
				[
					"realName" => "港元",
					"iso4217" => "HKD",
					"abbr" => "HK$"
				],
				[
					"realName" => "Magyar forint",
					"iso4217" => "HUF",
					"abbr" => "Ft"
				],
				[
					"realName" => "שקל חדש",
					"iso4217" => "ILS",
					"abbr" => "₪"
				],
				[
					"realName" => "Dollar US",
					"iso4217" => "USD",
					"abbr" => "$"
				],
				[
					"realName" => "Pound sterling",
					"iso4217" => "GBP",
					"abbr" => "£"
				],
				[
					"realName" => "Yen",
					"iso4217" => "JPY",
					"abbr" => "円"
				],
				[
					"realName" => "Ringgit",
					"iso4217" => "MYR",
					"abbr" => "RM"
				],
				[
					"realName" => "Peso mexicano",
					"iso4217" => "MXN",
					"abbr" => "MXN $"
				],
				[
					"realName" => "Xīn táibì",
					"iso4217" => "TWD",
					"abbr" => "NT$"
				],
				[
					"realName" => "New Zealand dollar",
					"iso4217" => "NZD",
					"abbr" => '$NZ'
				],
				[
					"realName" => "Norsk krone",
					"iso4217" => "NOK",
					"abbr" => "kr"
				],
				[
					"realName" => "Piso",
					"iso4217" => "PHP",
					"abbr" => "₱"
				],
				[
					"realName" => "Złoty",
					"iso4217" => "PLN",
					"abbr" => "zł"
				],
				[
					"realName" => "российский рубль",
					"iso4217" => "RUB",
					"abbr" => "₽"
				],
				[
					"realName" => "Singapore dollar",
					"iso4217" => "SGD",
					"abbr" => "S$"
				],
				[
					"realName" => "Krona",
					"iso4217" => "SEK",
					"abbr" => "SEK"
				],
				[
					"realName" => "Franc suisse",
					"iso4217" => "CHF",
					"abbr" => "CHF"
				],
				[
					"realName" => "Baht",
					"iso4217" => "THB",
					"abbr" => "฿"
				]
			];
		}
		
		public function getSymboleValues(): array {
			$res = [];
			
			foreach(self::getCurrencies() as $currency)
				$res[$currency["abbr"]] = $currency["iso4217"];
				
			return $res;
		}
		
		private static function getSymbolByCurrency($currency): string {
			return self::getCurrencies()[array_search($currency, array_column(self::getCurrencies(), "iso4217"))]["abbr"];
		}

		public static function formatPrice($price, $currency, $locale = "en"): string
		{
			if(empty($price))
				return "";

			$fmt = numfmt_create($locale, \NumberFormatter::CURRENCY );
			
			return numfmt_format_currency($fmt, $price, $currency)."\n";
		}
	}