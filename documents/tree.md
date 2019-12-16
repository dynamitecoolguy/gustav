# Gustav

## ソースツリー

ソースツリーは以下の通りです。

```
  .gitattributes                    git-cryptの対象となるファイル
  .gitignore
  /.git-crypt                       git-crypt用ディレクトリ
  /aws
    ec2-userdata.txt                  EC2用の起動スクリプト
  /certs                            開発用証明書
    README
    server.crt
    server.csr
    server.key
  /code                             ソースコード用フォルダ
    /app                              APIサーバアプリケーション
      /public                           公開用フォルダ
        index.php                         エントリー用PHP
      /src                              ソース用ツリー (Gustav\App)
      /test                             テスト用ツリー (Gustav\App)
    /common                           各アプリケーション共通コード
      /src                              ソース用ツリー (Gustav\Common)
      /test                             テスト用ツリー (Gustav\Common)
    /flatbuffers                      FlatBuffers用フォルダ
      /example                          サンプル
      /idl                              FlatBuffers定義ファイルフォルダ
      /php                              FlatBuffersによるソース出力先 (\)
      build.sh                          /idl以下の*.fbsをビルドし/phpに出力するスクリプト
    /mgr                              管理ツールアプリケーション
      /public                           公開用フォルダ
        index.php                         エントリー用PHP
      /src                              ソース用ツリー (Gustav\Mgr)
      /test                             テスト用ツリー (Gustav\Mgr)
    /vendor                           composerによる依存ライブラリの出力先
    composer.json                     composer用定義ファイル
    composer.lock
  /compose                          Docker-compose用フォルダ
    /dynamodb                         DynamoDB(AWSエミュレーション用)
    /fluentd                          Fluentd
    /mysql                            MySQL(AWSエミュレーション用)
    /pgsql                            PostgreSQL(AWSエミュレーション用)
    /proxy                            NginxによるReverse Proxy
    /sqs                              SQS(AWSエミュレーション用)
    /web_app                          APIサーバアプリケーション
    /web_mgr                          管理ツールアプリケーション
    docker-compose-local.yml          Docker-compose定義ファイル(ローカル環境用)
    docker-compose-development.yml    Docker-compose定義ファイル(開発環境用)
  /documents                        各種ドキュメント
```
