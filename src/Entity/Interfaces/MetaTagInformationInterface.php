<?php
namespace App\Entity\Interfaces;

interface MetaTagInformationInterface
{
    public function getMetaTitle();
	public function getMetaDescription();
	public function getMetaKeywords();
}