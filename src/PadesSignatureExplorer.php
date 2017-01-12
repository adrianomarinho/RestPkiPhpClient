<?php

namespace Lacuna\RestPki\Client;

/**
 * Class PadesSignatureExplorer
 * @package Lacuna\RestPki\Client
 */
class PadesSignatureExplorer extends SignatureExplorer
{
    const PDF_MIME_TYPE = "application/pdf";

    /**
     * @param RestPkiClient $client
     */
    public function __construct($client)
    {
        parent::__construct($client);
    }

    /**
     * @return mixed The signature information
     * @throws RestUnreachableException
     */
    public function open()
    {
        $request = $this->getRequest();

        $response = $this->client->post("Api/PadesSignatures/Open", $request);

        foreach ($response->signers as $signer) {
            $signer->validationResults = new ValidationResults($signer->validationResults);
            $signer->messageDigest->algorithm = RestPkiClient::_getPhpDigestAlgorithm($signer->messageDigest->algorithm);
            if (isset($signer->signingTime)) {
                $signer->signingTime = date("d/m/Y H:i:s P", strtotime($signer->signingTime));
            }
        }

        return $response;
    }
}
