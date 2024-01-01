<?php

namespace App\Service;

class Amazon {
    private $PARTNER_TAG = null;
    private $ACCESS_KEY_ID = null;
    private $SECRET_ACCESS_KEY = null;
    private $REGION_NAME = null;
    private $HOST = null;
	
	public function __construct(private AwsV4 $aws) {}

	public function getItem(string $itemId) {
		$this->setConfig();

		$searchItemRequest = [
			"PartnerType" => "Associates",
			"PartnerTag" => $this->PARTNER_TAG,
			"ItemIds" => [$itemId],
			"Resources" => ["Images.Primary.Large","ItemInfo.Title"]
		];

		$host = $this->HOST;
		$path = "/paapi5/searchitems";

		$payload = json_encode ($searchItemRequest);

		$this->aws->setConfig($this->ACCESS_KEY_ID, $this->SECRET_ACCESS_KEY);
		$this->aws->setRegionName($this->REGION_NAME);
		$this->aws->setServiceName("ProductAdvertisingAPI");
		$this->aws->setPath ($path);
		$this->aws->setPayload ($payload);
		$this->aws->setRequestMethod ("POST");
		$this->aws->addHeader ('content-encoding', 'amz-1.0');
		$this->aws->addHeader ('content-type', 'application/json; charset=utf-8');
		$this->aws->addHeader ('host', $host);
		$this->aws->addHeader ('x-amz-target', 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems');
		$headers = $this->aws->getHeaders ();
		$headerString = "";
		foreach ($headers as $key => $value)
			$headerString .= $key . ': ' . $value . "\r\n";

		$params = [
				'http' => [
					'header' => $headerString,
					'method' => 'POST',
					'content' => $payload
				]
			];

		$stream = stream_context_create ( $params );
		$fp = fopen ( 'https://'.$host.$path, 'rb', false, $stream );
		$response = @stream_get_contents ($fp);
		$response = json_decode($response);

		if(property_exists($response, "ItemsResult") and property_exists($response->ItemsResult, "Items"))
			return $response->ItemsResult->Items[0];

		return null;
	}

	private function setConfig()
	{
		$this->PARTNER_TAG = $_ENV["AMAZON_PARTNER_TAG"];
		$this->ACCESS_KEY_ID = $_ENV["AMAZON_ACCESS_KEY"];
		$this->SECRET_ACCESS_KEY = $_ENV["AMAZON_SECRET_ACCESS_KEY"];
		$this->REGION_NAME = $_ENV["AMAZON_REGION_NAME"];
		$this->HOST = $_ENV["AMAZON_HOST"];
	}
}