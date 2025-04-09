<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

abstract class AbstractBinPackingDto
{
    public string $username = '';

    #[SerializedName('api_key')]
    public string $apiKey = '';

    public function withAuth(#[\SensitiveParameter] string $username, #[\SensitiveParameter] string $apiKey): self
    {
        $this->username = $username;
        $this->apiKey = $apiKey;

        return $this;
    }
}
