<?php


namespace TairoLima\AzureStorage;

use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use MicrosoftAzure\Storage\Queue\Models\Queue;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureQueueAdapter
{
    private string $mFila;
    private QueueRestProxy $mQueueProxy;
    private ListMessagesOptions $mOptions;

    public function __construct(string $fila, string $connectionString)
    {
        $this->mFila       = $fila;
        $this->mQueueProxy = QueueRestProxy::createQueueService($connectionString);
        $this->mOptions    = new ListMessagesOptions();
    }

    public function getAll(int $numeroDeMenssagens = 1): array
    {
        $this->mOptions->setNumberOfMessages($numeroDeMenssagens);

        $filaTemp = $this->mQueueProxy->listMessages($this->mFila, $this->mOptions);

        $retorno = [];
        foreach ($filaTemp->getQueueMessages() as $value)
        {
            /** @var QueueMessage $value */

            $json = json_decode($value->getMessageText());

            array_push($retorno, $json);

            $this->excluir($value->getMessageId(), $value->getPopReceipt());
        }

        return $retorno;
    }

    public function adicionar(array $mensagem): void
    {
        $this->mQueueProxy->createMessage($this->mFila, json_encode($mensagem, true) ?? "");
    }

    public function excluir(string $mensagemId, string $popReceipt): void
    {
        $this->mQueueProxy->deleteMessage($this->mFila, $mensagemId, $popReceipt);
    }

    public function totalMensagens(): int
    {
        $fila = $this->mQueueProxy->getQueueMetadata($this->mFila);
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