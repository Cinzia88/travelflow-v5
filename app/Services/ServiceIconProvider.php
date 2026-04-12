<?php

namespace App\Services;

use File;

class ServiceIconProvider
{
    public static function getIconsService(): array
    {
        $basePath = public_path('icone');
        $options = [];

        foreach (File::directories($basePath) as $directory) {
            $category = basename($directory);
            $categoryLabel = ucfirst($category);

            foreach (File::files($directory) as $file) {
                if ($file->getExtension() === 'png') {
                    $filename = ucfirst($file->getFilenameWithoutExtension());

                    // chiave = valore = quello che salvi in DB
                    $options[$categoryLabel][$filename] = $filename;
                }
            }
        }

        return $options;
    }
}