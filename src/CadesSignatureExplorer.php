<?php

namespace Lacuna\RestPki\Client;

class CadesSignatureExplorer extends SignatureExplorer
{
    const CMS_SIGNATURE_MIME_TYPE = "application/pkcs7-signature";

    /** @var FileReference */
    private $dataFile;

    /**
     * @param RestPkiClient $client
     */
    public function __construct($client)
    {
        parent::__construct($client);
    }

    #region setDataFile

    /**
     * @param $path string The path of the detached data file
     */
    public function setDataFileFromPath($path)
    {
        $this->dataFile = FileReference::fromFile($path);
    }

    /**
     * @param $content string The binary contents of the detached data file
     */
    public function setDataFileFromBinary($content)
    {
        $this->dataFile = FileReference::fromBinary($content);
    }

    /**
     * @deprecated Use function setDataFileFromPath
     *
     * @param $path string The path of the detached data file
     */
    public function setDataFile($path)
    {
        $this->setDataFileFromPath($path);
    }

    #endregion

    /**
     * @return mixed The signature information
     */
    public function open()
    {
        return $this->openCommon(false);
    }

    /**
     * @return CadesSignatureWithEncapsulatedContent The signature information along with the extracted encapsulated content
     */
    public function openAndExtractContent()
    {
        $response = $this->openCommon(false);
        return new CadesSignatureWithEncapsulatedContent($response,
            new FileResult($this->client, $response->encapsulatedContent));
    }

    protected function openCommon($extractEncapsulatedContent)
    {

        $request = parent::getRequest();
        $request['extractEncapsulatedContent'] = $extractEncapsulatedContent;

        if (isset($this->dataFile)) {
            $requiredHashes = $this->getRequiredHashes();
            if (count($requiredHashes) > 0) {
                $request['dataHashes'] = $this->dataFile->computeDataHashes($requiredHashes);
            }
        }

        $response = $this->client->post("Api/CadesSignatures/Open", $request);

        foreach ($response->signers as $signer) {
            $signer->validationResults = new ValidationResults($signer->validationResults);
            $signer->messageDigest->algorithm = RestPkiClient::_getPhpDigestAlgorithm($signer->messageDigest->algorithm);
            if (isset($signer->signingTime)) {
                $signer->signingTime = date("d/m/Y H:i:s P", strtotime($signer->signingTime));
            }
        }

        return $response;
    }

    private function getRequiredHashes()
    {
        $request = $this->signatureFile->uploadOrReference($this->client);
        $response = $this->client->post("Api/CadesSignatures/RequiredHashes", $request);
        $algs = array();
        foreach ($response as $alg) {
            array_push($algs, RestPkiClient::_getPhpDigestAlgorithm($alg));
        }
        return $algs;
    }
}
