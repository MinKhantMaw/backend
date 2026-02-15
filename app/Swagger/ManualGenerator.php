<?php

namespace App\Swagger;

use Illuminate\Filesystem\Filesystem;
use L5Swagger\Generator;
use Symfony\Component\Yaml\Yaml;

class ManualGenerator extends Generator
{
    public function generateDocs(): void
    {
        $manualPath = base_path('docs/openapi.yaml');

        if (file_exists($manualPath)) {
            $this->prepareDirectory()
                ->defineConstants();

            $filesystem = new Filesystem();
            $yaml = $filesystem->get($manualPath);
            $parsed = Yaml::parse($yaml);
            $json = json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                throw new \RuntimeException('Unable to encode manual OpenAPI spec to JSON.');
            }

            $filesystem->put($this->docsFile, $json);

            if ($this->yamlCopyRequired) {
                $filesystem->put($this->yamlDocsFile, $yaml);
            }

            return;
        }

        parent::generateDocs();
    }
}
