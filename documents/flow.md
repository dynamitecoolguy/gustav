# Gustav

## 処理の流れ

### php-fpm/nginx

Gustavでは、PHPへのリクエストは/code/xxx/public/index.phpへ転送されるように設定されており、index.phpで全てのリクエストを処理できるようにしています。

```
設定ファイル
/compose/web_xxx/nginx-server.conf
```

もちろん、リクエストの内容によって別々のphpファイルで処理するように実装してもかまいません。


### index.php

/code/xxx/public/index.phpは、以下のような内容になっています。

```php
use DI\Bridge\Slim\Bridge;
use Gustav\App\AppContainerBuilder;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Config\ConfigLoader;
use Slim\Middleware\ContentLengthMiddleware;

/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = require __DIR__ . '/../../vendor/autoload.php';
$autoloader->addPsr4('Gustav\\App\\', __DIR__ . '/../src');               // app/src
$autoloader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src
$autoloader->addPsr4('Gustav\\Dx\\', __DIR__ . '/../../flatbuffers/php');             // flatbuffers/php

// 設定ファイルの読み込み
$loader = new ConfigLoader('/usr/local/etc/gustav/settings.yml', '/usr/local/etc/gustav/settings-secret.yml');

// 設定取得用クラスの作成
$config = new ApplicationConfig($loader);

// DIコンテナの作成
$containerBuilder = new AppContainerBuilder($config);
$container = $containerBuilder->build();

// SLIMアプリケーションの作成
$app = Bridge::create($container);

// Middleware
$app->add(new ContentLengthMiddleware());

// ルーティング (@see PHP-DI in Slim)
$app->post('/', [Gustav\App\Controller\MainController::class, 'post']);

// 実行
$app->run();
```

まず、composerによるautoloaderに、Gustavのソースコードを追加しています。

これは、composerの実行はDocker外部のホスト側から行うの対して、実際の処理はDockerコンテナ内から行うため、パスに差異が生じるためです。
ここで指定しているパスは、Dockerコンテナ内部から見たソースの相対パスになります。

```php
/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = require __DIR__ . '/../../vendor/autoload.php';
$autoloader->addPsr4('Gustav\\App\\', __DIR__ . '/../src');               // app/src
$autoloader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src
$autoloader->addPsr4('Gustav\\Dx\\', __DIR__ . '/../../flatbuffers/php');             // flatbuffers/php
```

次に設定ファイルの読み込みです。

設定ファイルは、Gustavによる実装で、ymlファイルを一つ、ないし、二つ読み込むことができます。
読み込んだ設定ファイルの内容は内部でマージされます。
キーが重複している場合は、後で指定した値に上書きされます。

これは、最初のymlファイルは通常の設定項目を、次のymlファイルはgit-cryptによってアクセスキーなどを暗号化して収納することを想定しています。

```php
// 設定ファイルの読み込み
$loader = new ConfigLoader('/usr/local/etc/gustav/settings.yml', '/usr/local/etc/gustav/settings-secret.yml');

// 設定取得用クラスの作成
$config = new ApplicationConfig($loader);
```


次にコンテナビルダとコンテナです。
Gustavでは、PHP-DIのContainerBuilderを拡張し、頻繁に使用するDBインスタンスなどがすでに定義済みになっています。

アプリケーション毎にコンテナの定義を追加、または、修正する場合は、Gustav\Common\BaseContainerBuilderを拡張します。


```php
// DIコンテナの作成
$containerBuilder = new AppContainerBuilder($config);
$container = $containerBuilder->build();
```


コンテナを使って、Slimアプリケーションを作成します。
作成には、PHP-DI/Slim-Bridgeを使用しています。
共通のミドルウェアなどがあれば、このときに追加することもできます。

```php
// SLIMアプリケーションの作成
$app = Bridge::create($container);

// Middleware
$app->add(new ContentLengthMiddleware());
```

リクエストのルーティングを定義します。
URLによって処理先を変更することもできますが、Gustavでは一つのControllerで処理を行えるようになっています。

これは、処理解析対策として、リクエストのURLが処理のヒントにならないようにするためです。

```php
$app->post('/', [Gustav\App\Controller\MainController::class, 'post']);
```

処理を開始します。

```php
// 実行
$app->run();
```

### Controller

リクエストは、index.phpで定義されたルーティングルールに従って、Controllerに処理が委譲されます。


実装例として、以下のような処理を行います。


```php
namespace Gustav\App\Controller;

use DI\Container;
use \Exception;
use Gustav\Common\Processor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class MainController
 * @package Gustav\App\Controller
 */
class MainController
{
    public function post(
        ServerRequestInterface $request,
        Container $container,
        ResponseInterface $response): ResponseInterface
    {
        try {
            // リクエストのボディ部の取得
            $content = $request->getBody()->getContents();

            // 処理結果を出力
            $response->getBody()->write(Processor::process($content, $container));
        } catch (Exception $e) {
            // 余分な情報を与えない
            $response->withStatus(500);
        }
        return $response;
    }
}
```

リクエストを受け取るメソッドは、PHP-DI/Slim-Bridgeを使用しているため、                                            
Containerに登録されているインターフェイスであれば、自由に引数に指定することができます。

アプリケーション側でインターフェイスを追加する場合は、ContainerBuilderを継承したクラス側でインターフェイスとその実装を追加します。


このメソッドでは、それぞれのリクエストを処理するために、
リクエスト本体とcontainerインスタンスをGustavのProcessor::processメソッドに渡しています。
そして、結果をそのままレスポンスに渡しているだけです。


### Processor::process

Gustav\Common\Processorクラスは、Gustavにおける標準的な処理を行うためのフローを定義しているクラスです。

標準的なフローは以下の通りです。

1. クライアント側
    1. クライアント側でリクエストオブジェクトを作成する
    1. リクエスト処理方法を示す識別子(ChunkID)、処理方法毎のバージョン番号、任意のリクエストIDと、リクエストオブジェクトをひとまとめにする
    1. ひとまとめになったオブジェクトをシリアライズする
    1. 暗号化を行う
    1. サーバーにリクエストを行う
1. サーバ側
    1. 復号化を行う
    1. デシリアライズを行う
    1. 識別子、バージョン番号、リクエストID、リクエストオブジェクトを取り出す
    1. 識別子により処理するクラスを決定し、処理をディスパッチする
    1. 処理結果(レスポンスオブジェクト)があれば、リクエスト時の識別子、バージョン番号、リクエストIDと、レスポンスオブジェクトをひとまとめにする
    1. ひとまとめになったオブジェクトをシリアライズする
    1. 暗号化を行う
    1. クライアントに返す
1. クライアント側
    1. 復号化を行う
    1. デシリアライズを行う
    1. 識別子、バージョン番号、リクエストID、レスポンスオブジェクトを取り出す
    1. クライアント側でレスポンス結果を反映する


コードは以下の通りです。

```php
namespace Gustav\Common;

use DI\Container;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Operation\BinaryEncryptorInterface;

/**
 * Class Processor
 * @package Gustav\Common
 */
class Processor
{
    /**
     * @param string $input
     * @param Container $container
     * @return string
     */
    public static function process(string $input, Container $container): string
    {
        // Containerからデータ処理に使用するオブジェクトを取得する
        $dispatcher = $container->get(DispatcherInterface::class);
        $encryptor = $container->get(BinaryEncryptorInterface::class);
        $serializer = $container->get(ModelSerializerInterface::class);

        // 復号化
        $decrypted = $encryptor->decrypt($input);

        // デシリアライズ
        $requestObjectList = $serializer->deserialize($decrypted);

        // リクエストオブジェクト毎に処理
        $resultList = [];
        foreach ($requestObjectList as $requestObject) {
            $result = $dispatcher->dispatch($container, $requestObject);
            if (!is_null($result)) {
                $resultList[] = new ModelChunk(
                    $requestObject->getChunkId(),
                    $requestObject->getVersion(),
                    $requestObject->getRequestId(),
                    $result
                );
            }
        }

        // 結果をシリアライズ
        $resultBinary = $serializer->serialize($resultList);

        // 暗号化
        return $encryptor->encrypt($resultBinary);
    }
}
```

処理に使用するオブジェクトをContainerから取り出します。ContainerBuilderまたはその継承クラスをカスタマイズすることで、暗号化やシリアライズ化をカスタマイズすることができます。

また、識別子毎に処理するクラスを定義するDispatcherを、各アプリケーション毎に作成する必要があります。

```php
        // Containerからデータ処理に使用するオブジェクトを取得する
        $dispatcher = $container->get(DispatcherInterface::class);
        $encryptor = $container->get(BinaryEncryptorInterface::class);
        $serializer = $container->get(ModelSerializerInterface::class);
```

送られてきたデータを復号します。
デフォルトでは、Gustav\Common\Operation\BinaryEncryptorクラスが処理を行います。
必要であれば、より強固な方法で暗号化を行ってください。

```php
        // 復号化
        $decrypted = $encryptor->decrypt($input);
```

復号したバイナリデータから、識別子、バージョン番号、リクエストID、リクエストオブジェクトのひとかたまりを取り出します。
なお、このかたまりは、同時に複数送ることができます。複数送られてきた場合は、順番に処理されます。
したがって、処理順序が重要となる処理は、ひとまとめに送る必要がある場合があります。

なお、シリアライズ方法は、Json、MessagePack、FlatBuffersが用意されています。

```php
        // デシリアライズ
        $requestObjectList = $serializer->deserialize($decrypted);
```

リクエストを各処理クラスに振り分けます。振り分けを行うのはDispatcherと呼ばれるクラスで、各アプリケーション毎に、Gustav\Common\DispatcherInterfaceを実装したクラスを作成するか、Gustasv\Common\BaseDispatcherを継承したクラスを作成します。

なお、結果を必要としないリクエストの場合は、処理クラスからnullを返します。

```php
        // リクエストオブジェクト毎に処理
        $resultList = [];
        foreach ($requestObjectList as $requestObject) {
            $result = $dispatcher->dispatch($container, $requestObject);
            if (!is_null($result)) {
                $resultList[] = new ModelChunk(
                    $requestObject->getChunkId(),
                    $requestObject->getVersion(),
                    $requestObject->getRequestId(),
                    $result
                );
            }
        }
```

結果をシリアライズします。

```php
        // 結果をシリアライズ
        $resultBinary = $serializer->serialize($resultList);
```

暗号化します。

```php
        // 暗号化
        return $encryptor->encrypt($resultBinary);
```

### Dispatcher

リクエストを処理するディスパッチャーは、Gustav\Common\DispatcherInterfaceを実装したクラスで行います。
このクラスの実装は、containerにDispatcherInterface::classをキーにして登録しておく必要があります。


```php
use DI\Container;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelInterface;

/**
 * Interface DispatcherInterface
 * @package Gustav\App
 */
interface DispatcherInterface
{
    /**
     * @param Container      $container     // DI\Container
     * @param ModelChunk     $request       // リクエストオブジェクト
     * @return ModelInterface|null          // リザルト。必要ない場合はnull
     * @throws ModelException
     */
    public function dispatch(Container $container, ModelChunk $request): ?ModelInterface;
}
```

標準的な実装はGustav\Common\BaseDispatcherです。
ただし、この実装には、実際の振り分け先は登録されていないので、通常は、このクラスを継承したクラスを登録します
(例: Gustav\App\AppDispatcher)。

BaseDispatcherを継承したクラスでは、getModelAndExecutor()メソッドをoverrideします。
このメソッドでは、リクエストに付けられた識別子、リクエストオブジェクトを格納するためのモデルクラス、リクエスト処理を行う処理クラスのセットの配列を返します。

モデルクラスは、Gustav\Common\Model\ModelInterfaceを実装する必要があります。

```php
    /**
     * 必要であればアプリケーション側でoverrideする
     * @return array
     */
    protected static function getModelAndExecutor(): array
    {
        return [
            ['REG', IdentificationModel::class, [UserRegistration::class, 'register']],
            ['TRC', TransferCodeModel::class, TransferOperation::class]
        ];
    }
```

### 処理クラス

上述した処理を行うクラスはcallableの形式で登録します。
このメソッドの引数は、typeに応じてcontainerに登録された値がセットされます。
ただし、Gustav\Common\Model\ModelChunk、Gustav\Common\Model\ModelInterface、実際にリクエストに用いられたクラス名(下の例ではGustav\App\Model\IdentificationModel)に関しては、Containerに登録されていなくても値がセットされます。


```php
namespace Gustav\App\Logic;

use DI\Container;
use Gustav\App\Model\IdentificationModel;
use Gustav\Common\Adapter\MySQLMasterInterface;

/**
 * ユーザ登録処理
 * Class UserRegistration
 * @package Gustav\App\Logic
 */
class UserRegistration
{
    /**
     * @param Container $container
     * @param IdentificationModel $request
     * @param MySQLMasterInterface $mysql
     * @return IdentificationModel
     */
    public function register(Container $container, IdentificationModel $request, MySQLMasterInterface $mysql, KeyOperatorInterface $keyOperator): IdentificationModel
    {
        return new IdentificationModel([....]);
    }
}
```
