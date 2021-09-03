<?php


namespace TairoLima\AzureStorage;

use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use MicrosoftAzure\Storage\Queue\Models\Queue;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureQueueAdapter
{
    private QueueRestProxy $mQueueProxy;
    private ListMessagesOptions $mOptions;

    public function __construct(string $connectionString)
    {
        //https://docs.microsoft.com/pt-br/rest/api/storageservices/get-messages

        $this->mQueueProxy = QueueRestProxy::createQueueService($connectionString);
        $this->mOptions    = new ListMessagesOptions();
    }

    public function getAllTexto(string $fila, int $numeroDeMenssagens = 1, bool $excluirDaFila = true): array
    {
        $this->mOptions->setNumberOfMessages($numeroDeMenssagens);

        $filaTemp = $this->mQueueProxy->listMessages($fila, $this->mOptions);
        $messages = $filaTemp->getQueueMessages();

        $retorno = [];
        if (is_array($messages))
        {
            foreach ($messages as $value)
            {
                /** @var QueueMessage $value */
                $mensagem = $value->getMessageText();

                if ($this->is_base64_encoded($mensagem)) {
                    $mensagem = base64_decode($mensagem);
                }

                array_push($retorno, $mensagem);

                if ($excluirDaFila) {
                    $this->excluir($fila, $value->getMessageId(), $value->getPopReceipt());
                }
            }
        }

        return $retorno;
    }

    public function getAll(string $fila, int $numeroDeMenssagens = 1, bool $excluirDaFila = true): array
    {
        $this->mOptions->setNumberOfMessages($numeroDeMenssagens);

        $filaTemp = $this->mQueueProxy->listMessages($fila, $this->mOptions);
        $messages = $filaTemp->getQueueMessages();

        $retorno = [];
        if (is_array($messages))
        {
            foreach ($messages as $value)
            {
                /** @var QueueMessage $value */
                $mensagem = $value->getMessageText();

                if ($this->is_base64_encoded($mensagem)) {
                    $mensagem = base64_decode($mensagem);
                }

                $json = json_decode($mensagem, true);

                //var_dump($json);
                if ($json != null || $json != false) {
                    array_push($retorno, $json);
                }

                if ($excluirDaFila) {
                    $this->excluir($fila, $value->getMessageId(), $value->getPopReceipt());
                }
            }
        }

        return $retorno;
    }

    function is_base64_encoded(string $data): bool
    {
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function adicionar(string $fila, array $mensagem): void
    {
        $this->mQueueProxy->createMessage($fila, json_encode($mensagem, true) ?? "");
    }

    public function excluir(string $fila, string $mensagemId, string $popReceipt): void
    {
        try {
            $this->mQueueProxy->deleteMessage($fila, $mensagemId, $popReceipt);
        }catch (ServiceException $e){
        }
    }

    public function totalMensagens(string $fila): int
    {
        $fila = $this->mQueueProxy->getQueueMetadata($fila);
        return $fila->getApproximateMessageCount();
    }

    public function filaGetAll(): array
    {
        /** @var Queue $value */

        $filas   = $this->mQueueProxy->listQueues();
        $retorno = [];

        foreach ($filas->getQueues() as $value)
        {
            array_push($retorno, [
                "nome" => $value->getName(),
                "url" => $value->getUrl(),
            ]);
        }

        return $retorno;
    }

    public function filaCriar(string $nome): bool
    {
        $this->mQueueProxy->createQueue($nome);

        return true;
    }

    public function filaExcluir(string $filaNome): void
    {
        try{
            $this->mQueueProxy->deleteQueue($filaNome);
        }catch (\Exception $e){
            print_r("{$e->getMessage()} \n");
        }
    }
}