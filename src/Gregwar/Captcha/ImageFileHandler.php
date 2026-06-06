<?php

namespace Gregwar\Captcha;

use GdImage;
use Symfony\Component\Finder\Finder;

/**
 * Handles actions related to captcha image files including saving and garbage collection
 *
 * @author Gregwar <g.passault@gmail.com>
 * @author Jeremy Livingston <jeremy@quizzle.com>
 */
class ImageFileHandler
{
    /**
     * Name of folder for captcha images
     */
    protected string $imageFolder;

    /**
     * Absolute path to public web folder
     */
    protected string $webPath;

    /**
     * Frequency of garbage collection in fractions of 1
     */
    protected ?int $gcFreq = null;

    /**
     * Maximum age of images in minutes
     */
    protected int $expiration;

    public function __construct(string $imageFolder, string $webPath, int $gcFreq, int $expiration)
    {
        $this->imageFolder      = $imageFolder;
        $this->webPath          = $webPath;
        $this->gcFreq           = $gcFreq;
        $this->expiration       = $expiration;
    }

    /**
     * Saves the provided image content as a file
     */
    public function saveAsFile(GdImage $contents): string
    {
        $this->createFolderIfMissing();

        $filename = md5(uniqid()) . '.jpg';
        $filePath = $this->webPath . '/' . $this->imageFolder . '/' . $filename;
        imagejpeg($contents, $filePath, 15);

        return '/' . $this->imageFolder . '/' . $filename;
    }

    /**
     * Randomly runs garbage collection on the image directory
     */
    public function collectGarbage(): bool
    {
        if ($this->gcFreq && mt_rand(1, $this->gcFreq) !== 1) {
            return false;
        }

        $this->createFolderIfMissing();

        $finder = new Finder();
        $criteria = sprintf('<= now - %s minutes', $this->expiration);
        $finder->in($this->webPath . '/' . $this->imageFolder)
            ->date($criteria);

        foreach ($finder->files() as $file) {
            unlink($file->getPathname());
        }

        return true;
    }

    /**
     * Creates the folder if it doesn't exist
     */
    protected function createFolderIfMissing(): void
    {
        if (!file_exists($this->webPath . '/' . $this->imageFolder)) {
            mkdir($this->webPath . '/' . $this->imageFolder, 0755);
        }
    }
}
