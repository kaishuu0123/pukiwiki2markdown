<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    // 対象のソースコードディレクトリを指定
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/public',
    ]);

    // PHP 8.5 までのルールを一括適用
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_85,
    ]);

    // 見た目だけの大量差分になるので除外
    $rectorConfig->skip([
        LongArrayToShortArrayRector::class,
    ]);
};
