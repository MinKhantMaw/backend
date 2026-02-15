<?php

namespace App\Swagger;

use L5Swagger\ConfigFactory;
use L5Swagger\GeneratorFactory as BaseGeneratorFactory;

class GeneratorFactory extends BaseGeneratorFactory
{
    private readonly ConfigFactory $configFactory; // store to reuse

    public function __construct(ConfigFactory $configFactory)
    {
        parent::__construct($configFactory);
        $this->configFactory = $configFactory;
    }

    public function make(string $documentation): ManualGenerator
    {
        $config = $this->configFactory->documentationConfig($documentation);

        $paths = $config['paths'];
        $scanOptions = $config['scanOptions'] ?? [];
        $constants = $config['constants'] ?? [];
        $yamlCopyRequired = $config['generate_yaml_copy'] ?? false;

        $secSchemesConfig = $config['securityDefinitions']['securitySchemes'] ?? [];
        $secConfig = $config['securityDefinitions']['security'] ?? [];

        $security = new \L5Swagger\SecurityDefinitions($secSchemesConfig, $secConfig);

        return new ManualGenerator(
            $paths,
            $constants,
            $yamlCopyRequired,
            $security,
            $scanOptions
        );
    }
}
