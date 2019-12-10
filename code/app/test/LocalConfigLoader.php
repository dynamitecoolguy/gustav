<?php


namespace Gustav\App;


use Gustav\Common\Config\ConfigLoader;

class LocalConfigLoader
{
    private static $tempFilePath;

    /**
     * @return ConfigLoader
     */
    public static function createConfigLoader(): ConfigLoader
    {
        self::$tempFilePath = tempnam('/tmp', 'localconfigloader');

        $fd = fopen(self::$tempFilePath, 'w');
        fwrite($fd, <<<'__EOF__'
mysql:
  host: mysql
  dbname: userdb
  user: scott
  password: tiger

pgsql:
  host: pgsql
  dbname: logdb
  user: scott
  password: tiger

redis:
  host: redis

dynamodb:
  endpoint: http://dynamodb:8000
  key: dummy
  secret: dummy
  table: hogehoge

storage:
  endpoint: http://storage:9000
  key: s3accesskey
  secret: s3secretkey
  bucket: dummy

sqs:
  endpoint: http://sqs:9324
  key: hoge
  secret: fuga

logger:
  type: fluent
  host: fluentd:24224

serializer:
  type: json
__EOF__
        );
        fclose($fd);

        return new ConfigLoader(self::$tempFilePath);
    }

    /**
     *
     */
    public static function destroyConfigLoader(): void
    {
        unlink(self::$tempFilePath);
    }
}