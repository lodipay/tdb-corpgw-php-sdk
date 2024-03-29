<?php

namespace Lodipay\TdbCorpGwSDK;

use Lodipay\TdbCorpGwSDK\Dto\Client\BalanceReqDocument;
use Lodipay\TdbCorpGwSDK\Dto\Client\BalanceResDocument;
use Lodipay\TdbCorpGwSDK\Dto\GetBalanceReqDto;
use Lodipay\TdbCorpGwSDK\Dto\GetStatementsReqDto;
use Lodipay\TdbCorpGwSDK\Dto\GetStatementsResDto;
use Lodipay\TdbCorpGwSDK\Dto\Client\GroupHeaderReq;
use Lodipay\TdbCorpGwSDK\Dto\Client\StatementReqDocument;
use Lodipay\TdbCorpGwSDK\Dto\Client\StatementResDocument;
use Lodipay\TdbCorpGwSDK\Dto\Client\BankTransferReqDocument;
use Lodipay\TdbCorpGwSDK\Dto\BankTransferReqDto;
use Lodipay\TdbCorpGwSDK\Dto\BankTransferResDto;
use Lodipay\TdbCorpGwSDK\Dto\Client\BankTransferResDocument;
use Lodipay\TdbCorpGwSDK\Dto\GetBalanceResDto;
use Lodipay\TdbCorpGwSDK\Enum\LangCode;
use Lodipay\TdbCorpGwSDK\Enum\PasswordType;
use Lodipay\TdbCorpGwSDK\Enum\TxnCode;
use Lodipay\TdbCorpGwSDK\Exception\CorpGwException;
use Lodipay\TseGuzzle\TseGuzzle;

class TdbCorpGwAPI extends TseGuzzle
{

    public function __construct(
        private string $organizationCode,
        private int $roleID,
        private string $loginID,
        private string $passwordLogin,
        private string $passwordTransaction,
        array $options = []
    ) {

        parent::__construct(array_replace_recursive([
            'base_uri' => 'http://192.168.1.1:8080',
        ], $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getGroupHeader(TxnCode $txnCode, array $options = []): GroupHeaderReq
    {

        $groupHeader = new GroupHeaderReq();
        $groupHeader->txsCode = $txnCode->value;
        $groupHeader->numberOfTxs = 1;
        $groupHeader->organizationCode = $this->organizationCode;
        $groupHeader->lang = LangCode::MN->value;
        $groupHeader->loginId = $this->loginID;
        $groupHeader->roleId = $this->roleID;
        $groupHeader->loginPassType = PasswordType::LOGIN_PASSWORD->value;
        $groupHeader->loginPass = $this->passwordLogin;
        $groupHeader->txsPassType = PasswordType::TXN_PASSWORD->value;
        $groupHeader->txsPass = $this->passwordTransaction;
        $groupHeader->createDate = '2014-10-21T11:16:58';

        if (isset($options['messageId'])) {
            $groupHeader->messageId = $options['messageId'];
        }

        if (isset($options['numberOfTxs'])) {
            $groupHeader->numberOfTxs = $options['numberOfTxs'];
        }

        if (isset($options['controlSum'])) {
            $groupHeader->controlSum = $options['controlSum'];
        }

        if (isset($options['lang'])) {
            $groupHeader->lang = $options['lang'];
        }
        return $groupHeader;
    }

    /**
     * Get balance of an account
     */
    public function getBalance(GetBalanceReqDto $reqDto, array $options = []): ?GetBalanceResDto
    {
        $reqDocument = new BalanceReqDocument();
        $reqDocument->header = $this->getGroupHeader(TxnCode::T5003, $options);
        $reqDocument->info = $reqDto;

        $responseDto = null;
        $response = $this->callAPI($reqDocument->serialize('xml', ['xml_root_node_name' => 'Document']));
        $responseDocument = BalanceResDocument::from($response, 'xml');

        if ($responseDocument->header->responseCode !== 10) {
            throw new CorpGwException($responseDocument->header->responseDesc);
        } else {
            $responseDto = $responseDocument->response;
        }

        return $responseDto;
    }

    /**
     * Get statements
     * 
     * @return array<GetStatementsResDto>
     */
    public function getStatements(GetStatementsReqDto $reqDto, $options = []): array
    {
        $reqDocument = new StatementReqDocument();
        $reqDocument->header = $this->getGroupHeader(TxnCode::T5004, $options);
        $reqDocument->info = $reqDto;

        $responseDto = null;
        $response = $this->callAPI($reqDocument->serialize('xml', ['xml_root_node_name' => 'Document']));

        $responseDocument = StatementResDocument::from($response, 'xml');
        if ($responseDocument->header->responseCode !== 10) {
            throw new CorpGwException($responseDocument->header->responseDesc);
        } else {
            $responseDto = $responseDocument->getGetStatementsArray();
        }

        return $responseDto;
    }

    /**
     * Bank transfer
     * 
     * @return BankTransferResDto|null
     */
    public function bankTransfer(BankTransferReqDto $reqDto, TxnCode $txnCode, $options = []): ?BankTransferResDto
    {
        $reqDocument = new BankTransferReqDocument();
        $reqDocument->header = $this->getGroupHeader($txnCode, $options);
        $reqDocument->info = $reqDto;

        $responseDto = null;
        $response = $this->callAPI($reqDocument->serialize('xml', ['xml_root_node_name' => 'Document']));
        $responseDocument = BankTransferResDocument::from($response, 'xml');
        if ($responseDocument->header->responseCode !== 10) {
            throw new CorpGwException($responseDocument->header->responseDesc);
        } else {
            $responseDto = $responseDocument->response;
        }

        return $responseDto;
    }

    /**
     * Call API
     * 
     * @param ?string $uri
     * @param array<string, mixed> $options
     * 
     * @return mixed
     */
    private function callAPI($body, array $options = []): mixed
    {
        $clientOptions = array_replace_recursive([
            'headers' => [
                'Content-Type' => 'text/xml; charset=UTF8',
            ],
            'body' => $body
        ]);
        $uri = $options['uri'] ?? '';
        $response = $this->client->post($uri, $clientOptions);
        return $response->getBody();
    }
}
