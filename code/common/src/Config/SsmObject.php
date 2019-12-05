<?php


namespace Gustav\Common\Config;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\Sdk;
use Aws\Ssm\SsmClient;
use Gustav\Common\Exception\ConfigException;

class SsmObject implements SsmObjectInterface
{
    const KEY_ACCOUNT_FILE = 'accountFile';
    const KEY_PROFILE = 'profile';
    const KEY_REGION = 'region';
    const DEFAULT_PROFILE = 'default';
    const DEFAULT_REGION = 'ap-northeast-1';

    /**
     * ssm:getParametersで取得できる最大数
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
        $account = $parameters[self::KEY_ACCOUNT_FILE] ?? 'ssm';
        $profile = $parameters[self::KEY_PROFILE] ?? self::DEFAULT_PROFILE;
        $region = $parameters[self::KEY_REGION] ?? self::DEFAULT_REGION;

        // SSMサービスへの認証
        $provider = CredentialProvider::ini($profile, $account);
        $memoizedProvider = CredentialProvider::memoize($provider);

        // SSM Clientの取得
        $sdk = new Sdk([
            'endpoint' => "https://ssm.${region}.amazonaws.com",
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
                $params = $this->client->getParameters([
                    'Names' => $chunkedKeys
                ]);

                // APCUにすべてのデータを登録
                foreach ($params['Parameters'] as $parameter) {
                    $result[$parameter['Name']] = $parameter['Value'];
                }
            }
        } catch (AwsException $e) {
            throw new ConfigException('Access error to aws(' . $e->getAwsErrorMessage() . ')');
        }
        return $result;
    }
}
