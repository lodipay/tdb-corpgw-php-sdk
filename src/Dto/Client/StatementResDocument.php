<?php

namespace Lodipay\TdbCorpGwSDK\Dto\Client;

use Lodipay\TdbCorpGwSDK\Dto\GetStatementsResDto;
use Symfony\Component\Serializer\Attribute\SerializedPath;
use Lodipay\DTO\DTO\TseDTO;

class StatementResDocument extends TseDTO
{
    #[SerializedPath('[GrpHdr]')]
    public GroupHeaderRes $header;

    /** @var array<GetStatementsResDto> $response */
    #[SerializedPath('[EnqRsp][Ntry]')]
    public array $response;

    /**
     * @return GetStatementsResDto[] 
     */
    public function getGetStatementsArray(): array
    {
        return isset($this->response[0]) && is_array($this->response[0]) ? GetStatementsResDto::fromArray($this->response) : GetStatementsResDto::fromArray([$this->response]);
    }
}
