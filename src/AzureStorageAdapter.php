<?php


namespace TairoLima\AzureStorage;


use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsResult;
use MicrosoftAzure\Storage\Blob\Models\SetBlobTierOptions;
use MicrosoftAzure\Storage\Common\Models\ServiceOptions;

class AzureStorageAdapter
{
    private BlobRestProxy $mBlobRestProxy;

    public function __construct(string $connectionString)
    {
        // https://github.com/Azure/azure-storage-php

        $this->mBlobRestProxy = BlobRestProxy::createBlobService($connectionString);
    }

    public function adicionar(string $container, string $blob, string $uriArquivo, ?string $contentType = null, bool $alterarAccessTier = false, string $accessTier = "Cool"): void
    {
        $arquivo = fopen($uriArquivo, "r");

        //upload para Azure
        if ($contentType == null){
            $this->mBlobRestProxy->createBlockBlob($container, $blob, $arquivo);
        }else{
            $options = new CreateBlockBlobOptions();
            $options->setContentType($contentType);

            $this->mBlobRestProxy->createBlockBlob($container, $blob, $arquivo, $options);
        }

        if ($alterarAccessTier == true)
        {
            $this->alterarCamadaDeAcesso($container, $blob, $accessTier);
        }
    }

    public function adicionarPasta(string $container, string $blob, string $uriPasta): void
    {
        if (is_dir($uriPasta))
        {
            $diretorio = dir($uriPasta);

            //Adiciona arquivo por arquivo
            while ($arquivo = $diretorio->read())
            {
                if (strlen($arquivo) > 3)
                {
                    $tempUri = "{$uriPasta}/{$arquivo}";
                    $this->adicionar($container, "{$blob}/{$arquivo}", $tempUri);
                }
            }

            $diretorio->close();
        }
    }

    public function excluir(string $container, string $blob): void
    {
        try {
            $this->mBlobRestProxy->deleteBlob($container, $blob);
        }catch (\Exception $e){
          //Se der algum error não faz nada
        }
    }

    // ["Hot", "Cool", "Archive"]
    public function alterarCamadaDeAcesso(string $container, string $blob, string $accessTier): void
    {
        // https://docs.microsoft.com/pt-br/azure/storage/blobs/storage-blob-storage-tiers

        $tipo = ["Hot", "Cool", "Archive"];

        if (in_array($accessTier, $tipo))
        {
            $tier = new SetBlobTierOptions();
            $tier->setAccessTier($accessTier);

            $this->mBlobRestProxy->setBlobTier($container, $blob, $tier);
        }
    }

    public function tamanho(string $container, string $prefix): void
    {
        $options  = new ListBlobsOptions();
        $options->setPrefix($prefix);

        $retorno = $this->mBlobRestProxy->listBlobs($container, $options);

        print_r($retorno->getBlobs());
    }
}