<?php


namespace Gustav\Common\Config;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\Sdk;
use Aws\Ssm\SsmClient;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Network\NameResolver;

class SsmObject implements SsmObjectInterface
{
    /**
     * ssm:getparametersで取得できる最大数
     */
    const SSM_GET_PARAMETERS_MAX = 10;

    /**
     * @var SsmClient
     */
    private $client;

    /**
     * SsmObject constructor.
     */
    public function __construct()
    {
        // do nothing
    }

    /**
     * @param array $parameters
     */
    public function setUp(array $parameters): void
    {
        $account = $parameters['account'] ?: 'ssm';
        $profile = $parameters['profile'] ?: 'default';
        $region = $parameters['region'] ?: 'ap-northeast-1';

        // SSMサービスへの認証
        $provider = CredentialProvider::ini($profile, $account);
        $memoizedProvider = CredentialProvider::memoize($provider);

        // SSM Clientの取得
        $ip = NameResolver::getIp("ssm.${region}.amazonaws.com");
        $sdk = new Sdk([
            'endpoint' => "https://${ip}",
            'region' => $region,
            'version' => '2014-11-06',
            'credentials' => $memoizedProvider
        ]);
        $this->client = $sdk->createSsm();
    }

    /**
     * @param string[] $keys
     * @return string[]
     * @throws ConfigException
     */
    public function getParameters(array $keys): array
    {
        $result = [];

        // SystemManagerのパラメータストアから値を一斉に取得
        try {
            foreach (array_chunk($keys, self::SSM_GET_PARAMETERS_MAX) as $chunkedKeys) {
                $result = $this->client->getParameters([
                    'Names' => $chunkedKeys
                ]);

                // APCUにすべてのデータを登録
                foreach ($result['Parameters'] as $parameter) {
                    $result[$parameter['Name']] = $parameter['Value'];
                }
            }
        } catch (AwsException $e) {
            throw new ConfigException('Access error to aws(' . $e->getAwsErrorMessage() . ')');
        }
        return $result;
    }
}
