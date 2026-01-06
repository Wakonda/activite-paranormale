<?php
namespace App\Service;

class Color
{
	public function getRelativeLuminance($color) {
		$components = array_map(function($c) {
			$c /= 255;
			return ($c <= 0.03928) ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
		}, $color);

		return 0.2126 * $components[0] + 0.7152 * $components[1] + 0.0722 * $components[2];
	}

	public function getContrastRatio($hex1, $hex2) {
		$rgb1 = sscanf(ltrim($hex1, '#'), "%02x%02x%02x");
		$rgb2 = sscanf(ltrim($hex2, '#'), "%02x%02x%02x");

		$l1 = $this->getRelativeLuminance($rgb1);
		$l2 = $this->getRelativeLuminance($rgb2);

		return ($l1 > $l2) ? ($l1 + 0.05) / ($l2 + 0.05) : ($l2 + 0.05) / ($l1 + 0.05);
	}

	public function checkAccessibilityAAA($hex1, $hex2, $isLargeText = false) {
		$ratio = $this->getContrastRatio($hex1, $hex2);
		$threshold = $isLargeText ? 4.5 : 7.0;

		return [
			'ratio' => round($ratio, 2),
			'passed' => $ratio >= $threshold,
			'threshold_needed' => $threshold
		];
	}

	public function fixContrastAAA($hexColor, $fixedColor, $targetRatio = 7.0) {
		$currentRatio = $this->getContrastRatio($hexColor, $fixedColor);
		
		if ($currentRatio >= $targetRatio) {
			return $hexColor;
		}

		$rgb = sscanf(ltrim($hexColor, '#'), "%02x%02x%02x");

		$darkerHex = $hexColor;
		$darkerRgb = $rgb;
		while ($this->getContrastRatio($darkerHex, $fixedColor) < $targetRatio) {
			$darkerRgb = array_map(function($c) { return max(0, $c - 1); }, $darkerRgb);
			$darkerHex = sprintf("#%02x%02x%02x", $darkerRgb[0], $darkerRgb[1], $darkerRgb[2]);
			if ($darkerRgb === [0, 0, 0]) break;
		}

		$lighterHex = $hexColor;
		$lighterRgb = $rgb;
		while ($this->getContrastRatio($lighterHex, $fixedColor) < $targetRatio) {
			$lighterRgb = array_map(function($c) { return min(255, $c + 1); }, $lighterRgb);
			$lighterHex = sprintf("#%02x%02x%02x", $lighterRgb[0], $lighterRgb[1], $lighterRgb[2]);
			if ($lighterRgb === [255, 255, 255]) break;
		}

		$ratioDark = $this->getContrastRatio($darkerHex, $fixedColor);
		$ratioLight = $this->getContrastRatio($lighterHex, $fixedColor);

		if ($ratioDark >= $targetRatio) return $darkerHex;
		if ($ratioLight >= $targetRatio) return $lighterHex;

		return ($ratioDark > $ratioLight) ? "#000000" : "#ffffff";
	}
}