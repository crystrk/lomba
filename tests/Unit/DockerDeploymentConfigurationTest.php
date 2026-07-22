<?php

use Symfony\Component\Yaml\Yaml;

test('the Coolify deployment targets the application server port', function () {
    $projectRoot = dirname(__DIR__, 2);
    $compose = Yaml::parseFile($projectRoot.'/docker-compose.yml');
    $dockerfileLines = file($projectRoot.'/Dockerfile', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    expect($compose['services']['app']['ports'])
        ->toContain('${APP_PORT:-8080}:8080')
        ->and($compose['services']['app']['healthcheck']['test'])
        ->toBe(['CMD', 'curl', '-f', 'http://localhost:8080/up'])
        ->and($compose['services']['app']['networks'])
        ->toContain('coolify')
        ->and($compose['networks']['coolify']['external'])
        ->toBeTrue()
        ->and($dockerfileLines)
        ->toContain('EXPOSE 8080')
        ->not->toContain('EXPOSE 80');
});
