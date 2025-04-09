<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withCache(__DIR__ . '/var/cache/dev/ecs-fixer')
    ->withPreparedSets(psr12: true, common: true)
    ->withRules([
        DeclareStrictTypesFixer::class,
    ])
    ->withConfiguredRule(
        ForbiddenFunctionsSniff::class,
        [
            'forbiddenFunctions' => [
                'var_dump' => 'null',
                'phpinfo' => 'null',
                'xdebug_info' => 'null',
                'dump' => 'null',
                'dd' => 'null',
            ],
        ]
    );
