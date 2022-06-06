<?php
namespace App\Entity\Interfaces;

interface PhotoIllustrationInterface
{
    public function getPhotoIllustrationFilename(): ?String;
	public function getPhotoIllustrationCaption(): ?Array;
}