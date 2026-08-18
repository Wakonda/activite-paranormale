<?php

namespace App\Service;

class CompressResize
{
    /**
     * Qualité WebP par défaut.
     * 80-85 = quasi indolore visuellement, gain de poids énorme.
     * 90+ = "sans perte visible" au sens strict, gain plus modeste.
     */
    private int $quality;

    public function __construct(int $quality = 85)
    {
        if ($quality < 0 || $quality > 100) {
            // throw new InvalidArgumentException('La qualité doit être comprise entre 0 et 100.');
        }

        if (!function_exists('imagewebp')) {
            // throw new RuntimeException(
                // 'L\'extension GD n\'a pas le support WebP. Recompilez PHP/GD avec --with-webp, ' .
                // 'ou installez php-gd via un paquet qui l\'inclut (ex: libgd avec libwebp).'
            // );
        }

        $this->quality = $quality;
    }

    /**
     * Convertit une image source en WebP, avec redimensionnement optionnel.
     *
     * @param string   $sourcePath      Chemin de l'image source
     * @param string   $destinationPath Chemin de sortie (.webp)
     * @param int|null $maxWidth        Largeur max (null = pas de contrainte)
     * @param int|null $maxHeight       Hauteur max (null = pas de contrainte)
     * @param bool     $allowUpscale    Autoriser l'agrandissement si l'image est plus petite
     *
     * @return array{path:string, width:int, height:int, size:int, ratio:float}
     */
    public function convert(
        string $sourcePath,
        ?int $maxWidth = null,
        ?int $maxHeight = null,
        bool $allowUpscale = false
    ): array {
        if (!is_file($sourcePath)) {
            // throw new InvalidArgumentException(sprintf('Le fichier "%s" n\'existe pas.', $sourcePath));
        }

        $originalSize = filesize($sourcePath);
        $image = $this->createImageFromFile($sourcePath);

        [$origWidth, $origHeight] = [imagesx($image), imagesy($image)];

        // Calcul des dimensions cibles en conservant le ratio
        [$targetWidth, $targetHeight] = $this->computeTargetDimensions(
            $origWidth,
            $origHeight,
            $maxWidth,
            $maxHeight,
            $allowUpscale
        );

        if ($targetWidth !== $origWidth || $targetHeight !== $origHeight) {
            $image = $this->resize($image, $targetWidth, $targetHeight);
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

		ob_start();

        if (imagewebp($image, null, $this->quality)) {

		$webpData = ob_get_clean();

		$base64 = base64_encode($webpData);

            imagedestroy($image);
            // throw new RuntimeException('Échec de l\'écriture du fichier WebP.');
        }

        imagedestroy($image);

        $newSize = strlen($webpData);

        return [
            'base64' => $base64,
            'width' => $targetWidth,
            'height' => $targetHeight,
            'size' => $newSize,
            'ratio' => $originalSize > 0 ? round(($originalSize - $newSize) / $originalSize * 100, 2) : 0.0,
        ];
    }

    /**
     * Optimisation "au mieux" : tente plusieurs niveaux de qualité et garde
     * le premier résultat qui passe sous une taille cible, sans descendre
     * en dessous d'une qualité plancher.
     *
     * @param string   $sourcePath
     * @param string   $destinationPath
     * @param int      $targetSizeBytes Taille cible en octets (ex: 200 * 1024 pour 200 Ko)
     * @param int      $minQuality      Qualité plancher pour ne pas dégrader visuellement
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     *
     * @return array{path:string, width:int, height:int, size:int, ratio:float, quality:int}
     */
    public function convertToTargetSize(
        string $sourcePath,
        string $destinationPath,
        int $targetSizeBytes,
        int $minQuality = 60,
        ?int $maxWidth = null,
        ?int $maxHeight = null
    ): array {
        $qualitySteps = range(95, $minQuality, -5);
        $lastResult = null;

        foreach ($qualitySteps as $quality) {
            $this->quality = $quality;
            $result = $this->convert($sourcePath, $destinationPath, $maxWidth, $maxHeight);
            $lastResult = $result;

            if ($result['size'] <= $targetSizeBytes) {
                $result['quality'] = $quality;
                return $result;
            }
        }

        // Aucune qualité testée n'atteint la cible : on garde la dernière (la plus compressée)
        $lastResult['quality'] = $minQuality;
        return $lastResult;
    }

    private function createImageFromFile(string $path)
    {
        $mime = mime_content_type($path);

        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/bmp' => imagecreatefrombmp($path)//,
            // default => throw new InvalidArgumentException(sprintf('Format d\'image non supporté : %s', $mime)),
        };

        if ($image === false) {
            // throw new RuntimeException('Impossible de lire l\'image source (fichier corrompu ?).');
        }

        return $image;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function computeTargetDimensions(
        int $origWidth,
        int $origHeight,
        ?int $maxWidth,
        ?int $maxHeight,
        bool $allowUpscale
    ): array {
        if ($maxWidth === null && $maxHeight === null) {
            return [$origWidth, $origHeight];
        }

        $widthRatio = $maxWidth !== null ? $maxWidth / $origWidth : PHP_FLOAT_MAX;
        $heightRatio = $maxHeight !== null ? $maxHeight / $origHeight : PHP_FLOAT_MAX;
        $ratio = min($widthRatio, $heightRatio);

        if (!$allowUpscale) {
            $ratio = min($ratio, 1.0);
        }

        $targetWidth = (int) round($origWidth * $ratio);
        $targetHeight = (int) round($origHeight * $ratio);

        return [max(1, $targetWidth), max(1, $targetHeight)];
    }

    private function resize($image, int $targetWidth, int $targetHeight)
    {
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);

        // Conserve la transparence pendant le resize
        imagepalettetotruecolor($resized);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled(
            $resized,
            $image,
            0, 0, 0, 0,
            $targetWidth,
            $targetHeight,
            imagesx($image),
            imagesy($image)
        );

        imagedestroy($image);

        return $resized;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            // throw new RuntimeException(sprintf('Impossible de créer le répertoire "%s".', $directory));
        }
    }
}