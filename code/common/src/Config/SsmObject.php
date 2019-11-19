<?php


namespace Gustav\Common\Config;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\Sdk;
use Aws\Ssm\SsmClient;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Network\NameResolver;

class SsmObject extends AbstractSsmObject
{
    /**
     * ssm:getparametersで取得できる最大数
     */
    const SSM_GET_PARAMETERS_MAX = 10;

    /**
     * @param string[] $keys
     * @return string[]
     * @throws ConfigException
     */
    public function getParameters(array $keys): array
    {
        $ssm = $this->createClient();

        $result = [];

        // SystemManagerのパラメータストアから値を一斉に取得
        try {
            foreach (array_chunk($keys, self::SSM_GET_PARAMETERS_MAX) as $chunkedKeys) {
                $result = $ssm->getParameters([
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

    private function createClient(): SsmClient
    {
        // SSMサービスへの認証
        $provider = CredentialProvider::ini($this->getProfile(), $this->getAccountFile());
        $memoizedProvider = CredentialProvider::memoize($provider);

        // SSM Clientの取得
        $ip = NameResolver::getIp('ssm.' . $this->getRegion() . '.amazonaws.com');
        $sdk = new Sdk([
            'endpoint' => 'https://' . $ip,
            'region' => $this->getRegion(),
            'version' => '2014-11-06',
            'credentials' => $memoizedProvider
        ]);
        return $sdk->createSsm();
    }
}
