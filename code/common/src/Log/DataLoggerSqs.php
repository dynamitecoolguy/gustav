<?php


namespace Gustav\Common\Log;


use Aws\Sqs\SqsClient;

/**
 * Class DataLoggerSqs
 * @package Gustav\Common\Log
 */
class DataLoggerSqs implements DataLoggerInterface
{
    /**
     * @var DataLoggerInterface
     */
    private static $theInstance = null;

    /**
     * @var string[]
     */
    private $entityList;

    /**
     * @var SqsClient
     */
    private $client;

    /**
     * @var string
     */
    private $queueUrl;

    /**
     * @param SqsClient $client
     * @param string $queueUrl
     * @return DataLoggerSqs
     */
    public static function getInstance(SqsClient $client, string $queueUrl): DataLoggerSqs
    {
        if (is_null(self::$theInstance)) {
            self::$theInstance = new DataLoggerSqs($client, $queueUrl);
        }

        return self::$theInstance;
    }

    /**
     * DataLoggerSqs constructor.
     * @param SqsClient $client
     * @param string $queueUrl
     */
    protected function __construct(SqsClient $client, string $queueUrl)
    {
        $this->entityList = [];
        $this->client = $client;
        $this->queueUrl = $queueUrl;
    }


    /**
     * ログ用データの追加.
     * ただし、 flushするまでは出力されない
     * @param string $tag
     * @param float $timestamp
     * @param array $dataHash
     */
    public function add(string $tag, float $timestamp, array $dataHash): void
    {
        $this->entityList[] = json_encode([$tag, $timestamp, $dataHash]);
    }

    /**
     * 貯めたログのflush
     * DBのトランザクションでコミットしたときを想定
     */
    public function flush(): void
    {
        if (isset($this->entityList[0])) {
            $index = 0;
            $entries = [];
            foreach (array_chunk($this->entityList, 10) as $chunked) {
                foreach ($chunked as $entity) {
                    $entries[] = [
                        'Id' => strval($index),
                        'MessageBody' => $entity
                    ];
                    $index++;
                }
                $this->client->sendMessageBatch([
                    'Entries' => $entries,
                    'QueueUrl' => $this->queueUrl
                ]);
            }
        }

        $this->entityList = [];
    }

    /**
     * 貯めたログを出力せずにクリア.
     * DBのトランザクションでロールバックしたときを想定
     */
    public function clear(): void
    {
        $this->entityList = [];
    }
}
