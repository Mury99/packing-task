<?php

declare(strict_types=1);

namespace App\Provider;

class StaticBoxProvider implements BoxProviderInterface
{
    public function getBoxes(): array
    {
        return [
            [
                'id' => 1,
                'width' => 10,
                'height' => 10,
                'length' => 10,
                'maxWeight' => 20,
            ],
            [
                'id' => 2,
                'width' => 10,
                'height' => 10,
                'length' => 10,
                'maxWeight' => 50,
            ],
            [
                'id' => 3,
                'width' => 20,
                'height' => 20,
                'length' => 20,
                'maxWeight' => 100,
            ],
            [
                'id' => 4,
                'width' => 50,
                'height' => 50,
                'length' => 50,
                'maxWeight' => 200,
            ],
        ];
    }
}
