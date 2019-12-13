# Gustav

## docker-composeコンテナ解説

ローカル、または、開発環境では、同一PC内で必要な全てのDockerコンテナを動作させることでできるようにしています。


### プロジェクト内ツリー

Gustavプロジェクトで、docker-composeに関連する処理は、/compose以下に含まれています。
各ディレクトリがそれぞれdockerコンテナになります。

```
  /compose                          Docker-compose用フォルダ
    /dynamodb                         DynamoDB(AWS DynamoDBエミュレーション用)
    /fluentd                          Fluentd
    /mysql                            MySQL(AWS RDB(MySQL)エミュレーション用)
    /pgsql                            PostgreSQL(AWS RedShiftエミュレーション用)
    /proxy                            NginxによるReverse Proxy
    /sqs                              SQS(AWS SQSエミュレーション用)
    /web_app                          APIサーバアプリケーション
    /web_mgr                          管理ツールアプリケーション
    docker-compose-local.yml          Docker-compose定義ファイル(ローカル環境用)
    docker-compose-development.yml    Docker-compose定義ファイル(開発環境用)
```

web_xxxがユーザーが作成するアプリケーションになります。必要があれば、アプリケーションを追加することができます。

また、各ディレクトリ以外に、redis(AWS ElastiCacheエミュレーション用)とstorage(AWS S3エミュレーション用)の２つのコンテナが起動します。


### ビルド方法

各コンテナをビルドし、起動する際には、以下のようにコマンドを実行します。
ec2などの開発環境で実行する場合には、コマンド内の"local"を適切な文字列に変更してください。

```shell script
$ cd /compose
$ docker-compose -f docker-compose-local.yml build
$ docker-compose -f docker-compose-local.yml up -d
```

なお、mysql、pgsql、redis、storage、dynamodbのデータはdockerのstorage機能で保存されていますので、docker-composeを終了してもデータが残ったままになります。

データを削除するためには、docker volumeコマンドでボリュームを削除する必要があります。


### HTTPリクエストの流れ

まずproxyコンテナがhttpリクエストを受け取ります。proxyコンテナは、リクエストに用いられたホスト名によりweb_xxxコンテナにリクエストを転送します。

したがって、別のアプリケーションを追加したり、名前を変更する場合には、proxyコンテナの設定を変更する必要があります。


### dynamodbコンテナ

Amazonの提供するローカル環境用のDynamoDBを使用しています。

各コンテナ内部からはポート8000、ホスト側からはポート18000でアクセスすることができます。


### fluentdコンテナ

各アプリケーション内のアクションログを保存するためのコンテナです。

実際は、このコンテナからS3、ファイル、PostgreSQLなどにデータを出力しますが、現在はコンテナ内のファイルへのログ出力のみです。

転送の設定を変更する場合は、コンテナ内のfluent.confを編集します。

各コンテナ内部およびホスト側からはポート24224でアクセスすることができます。


### mysqlコンテナ

mysqlコンテナとなっていますが、MariaDBを使用しています。

mysqldおよびmysqlクライアントへの設定は、それぞれ、コンテナ内のmysqld.cnfとclient.cnfを編集してください。

また、初期データを投入する必要がある場合には、init.dフォルダ以下に拡張子sqlのファイルを用意する必要があります。

各コンテナ内部からはポート3306、ホスト側からはポート13306でアクセスすることができます。


### pgsqlコンテナ

PostgeSQLまたはRedshiftエミュレーション用のコンテナです。

初期データを投入する必要がある場合には、init.dフォルダ以下に拡張子sqlのファイルを用意する必要があります。

各コンテナ内部からはポート5432、ホスト側からはポート15432でアクセスすることができます。


### proxyコンテナ

リクエストを振り分けるためのリバースプロキシ用コンテナです。実装はnginxです。

リクエストの振り分けルールなどの設定は、local.conf (開発環境の場合にはdevelopment.conf)を書き換えてください。

ホスト側からはポート80(http)および443(https)でアクセスすることができます。

httpsでアクセスされた場合は、/certs以下の証明書を使用します。また、各アプリケーションへのリクエストは平文で行われます。


### sqsコンテナ

AWSのSQSをエミュレートするために、互換性のあるElasticMQを実行するコンテナです。

キューの設定は、custom.confを書き換えてください。

各コンテナ内部からはポート9324、ホスト側からはポート19324でアクセスすることができます。


### redisコンテナ

AWSのElastiCacheをエミュレートするために、redisを実行するコンテナです。

特に設定はありません。

各コンテナ内部からはポート6379、ホスト側からはポート16379でアクセスすることができます。


### storageコンテナ

AWSのS3をエミュレートするために、minioを実行するコンテナです。

イメージ用のフォルダはありません。設定は直接、docker-composeファイルに記載しています。

各コンテナ内部からはポート9000、ホスト側からはポート19000でアクセスすることができます。


### web_appコンテナ

アプリケーション用のNginx + php-fpmのコンテナです。

Nginxとphp-fpmの２つのプロセスはSupervisorを使ってデーモン化しています。

アプリケーション用の設定はsettings-local.ymlとsettings-local.yml.secretの２つあり、どちらに書いてもかまいません。
しかし、settings-local.yml.secretはgit-cryptによって暗号化されます。
したがって、secret側にはAWSアクセスキーなどの情報のみにとどめる方が良いでしょう。

git-cryptの操作方法や設定方法については、git-cryptの公式ドキュメント等をご参照ください。

また、アプリケーションコンテナには、docker-composeによって/codeディレクトリ以下のいくつかのフォルダがコンテナ内にマウントされます。
これらのフォルダはリンクされているだけですので、/code以下を書き換えると、現在実行されているphpプログラムも書き換わってしまいますので注意が必要です。

```
/code/app/src:/var/www/app/src
/code/app/public:/var/www/app/public
/code/common/src:/var/www/common/src
/code/vendor:/var/www/vendor
```

### web_mgrコンテナ

web_appと同様です。
