<?php
namespace App\Type;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class EncryptType extends StringType
{
	const ENCRYPT_TYPE = "encrypt";

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return openssl_decrypt($value, 'aes-128-ecb', $_ENV["DB_DECRYPT_KEY"]);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return openssl_encrypt($value, 'aes-128-ecb', $_ENV["DB_DECRYPT_KEY"]);
    }

	public function getName() {
		return self::ENCRYPT_TYPE;
	}
}