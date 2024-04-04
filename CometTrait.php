<?php

namespace App\Http\Traits;

use App\Models\CometServer;
use Comet\{BrandingOptions, EmailOptions, GroupPolicy, HTTPConnectorOptions, MacOSCodeSignProperties, Organization, RemoteStorageOption, SoftwareBuildRoleOptions, SSHConnection, UserProfileConfig, WebhookOption};
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as ApiRequest;
use Illuminate\Http\Request;

trait CometTrait
{
    public function authenticate($request, $url)
    {
        try {
            $apiData = $this->loginData($request);
            $client = new Client();
            $apiRequest = new ApiRequest('POST', $url);
            $response = $client->sendAsync($apiRequest, $apiData)->wait();

            return json_decode($response->getBody());
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function startSession(CometServer $server)
    {
        try {
            return $server->getRemote()->HybridSessionStart();
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function createPolicy(Request $request, CometServer $server, $organizationId = '')
    {
        try {
            $policyData = $this->policyData($request);
            $newPolicy = GroupPolicy::createFromArray([
                "Description" => $request->name,
                "Policy" => $policyData,
                "OrganizationID" => $organizationId,
            ]);

            return $server->getRemote()->AdminPoliciesNew($newPolicy);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function updatePolicy(Request $request, CometServer $server, $policyID, $organizationId = '')
    {
        try {
            $policyData = $this->policyData($request);
            $policy = GroupPolicy::createFromArray([
                "Description" => $request->name,
                "Policy" => $policyData,
                "OrganizationID" => $organizationId,
            ]);

            return $server->getRemote()->AdminPoliciesSet($policyID, $policy);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function deletePolicy(CometServer $server, $policyId)
    {
        try {
            return $server->getRemote()->AdminPoliciesDelete($policyId);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function listUsers(CometServer $server)
    {
        try {
            return $server->getRemote()->AdminListUsers();
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function listUsersFull(CometServer $server)
    {
        try {
            return $server->getRemote()->AdminListUsersFull();
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function createUser(Request $request, CometServer $server)
    {
        try {
            $server->getRemote()->AdminAddUser($request->username, $request->password);
            $profile = $server->getRemote()->AdminGetUserProfile($request->username);
            $profileHash = $server->getRemote()->AdminGetUserProfileHash($request->username);
            $userProfile = [
                'Username' => $request->username,
                'AccountName' => $request->account_name,
                'Emails' => [$request->emails],
                'PolicyID' => $request->policy_id,
                'OrganizationID' => $request->organization_id,
                'PasswordFormat' => $profile->PasswordFormat,
                'PasswordHash' => $profile->PasswordHash
            ];
            $profileConfig = UserProfileConfig::createFromArray($userProfile);

            return $server->getRemote()->AdminSetUserProfileHash($request->username, $profileConfig, $profileHash->ProfileHash);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function setUserProfile(Request $request, CometServer $server)
    {
        try {
            $profile = $server->getRemote()->AdminGetUserProfile($request->username);

            return $server->getRemote()->AdminSetUserProfile($request->username, $profile);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function createTenant(Request $request, CometServer $server)
    {
        try {
            $data = Organization::createFromArray([
                'Name' => $request->hostname,
                'Hosts' => [parse_url($request->host_url, PHP_URL_HOST)],
            ]);

            return $server->getRemote()->AdminOrganizationSet(null, $data);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function updateTenant(Request $request, CometServer $server)
    {
        try {
            $organizationId = $request->organization_id;
            $organizations = $server->getRemote()->AdminOrganizationList();
            if (!in_array($organizationId, array_keys($organizations))) {
                throw new Exception('Your account does not exist anymore');
            }
            $isSuspended = $request->is_suspended ?? false;
            $branding = $organizations[$organizationId];
            $windowIcon = $branding->Branding->PathIcoFile;
            $macOSIcon = $branding->Branding->PathIcnsFile;
            $macOSMenuBarIcon = $branding->Branding->PathMenuBarIcnsFile;
            $eula = $branding->Branding->PathEulaRtf;
            $tileImage = $branding->Branding->PathTilePng;
            $appIconImage = $branding->Branding->PathAppIconImage;
            $logoImage = $branding->Branding->LogoImage;

            $data = Organization::createFromArray([
                'Name' => $request->hostname,
                'Hosts' => [parse_url($request->host_url, PHP_URL_HOST)],
                'Branding' => BrandingOptions::createFromArray([
                    'BrandName' => 'test brand',
                    'LogoImage' => $logoImage,
                    'ProductName' => 'product name',
                    'CompanyName' => 'company name',
                    'HelpURL' => '',
                    'DefaultLoginServerURL' => '',
                    'TileBackgroundColor' => '',
                    'AccountRegisterURL' => '',
                    'HideBackgroundLogo' => true,
                    'PathIcoFile' => $windowIcon,
                    'PathIcnsFile' => $macOSIcon,
                    'PathMenuBarIcnsFile' => $macOSMenuBarIcon,
                    'PathEulaRtf' => $eula,
                    'PathTilePng' => $tileImage,
                    'PathHeaderImage' => '',
                    'PathAppIconImage' => $appIconImage,
                    'MacOSCodeSign' => MacOSCodeSignProperties::createFromArray([
                        'SSHServer' => SSHConnection::createFromArray([
                            'SSHServer' => '',
                            'SSHUsername' => '',
                            'SSHAuthMode' => 0,
                            'SSHPassword' => '',
                            'SSHPrivateKey' => '',
                            'SSHCustomAuth_UseKnownHostsFile' => false,
                            'SSHCustomAuth_KnownHostsFile' => ''
                        ])
                    ])
                ]),
                'SoftwareBuildRole' => SoftwareBuildRoleOptions::createFromArray([
                    'AllowUnauthenticatedDownloads' => true
                ]),
                'RemoteStorage' => [
                    RemoteStorageOption::createFromArray([
                        'Type' => 'comet',
                        'Description' => 'Cloud Storage',
                        'RemoteAddress' => 'https://ecs-ca-central.eazybackup.ca/',
                        'Username' => 'api',
                        'Password' => '7XX49(CV(xkho@',
                        'RebrandStorage' => true
                    ])
                ],
                'Email' => EmailOptions::createFromArray([
                    'FromName' => 'EmailFromName',
                    'FromEmail' => 'fromemail@gmail.com',
                    'Mode' => 'smtp',
                    'SMTPHost' => 'hostname',
                    'SMTPPort' => '22',
                    'SMTPUsername' => 'username',
                    'SMTPPassword' => 'Shine@123'
                ]),
                "IsSuspended" => $isSuspended
            ]);

            return $server->getRemote()->AdminOrganizationSet($organizationId, $data);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function deleteUser(CometServer $server, $username)
    {
        try {
            return $server->getRemote()->AdminDeleteUser($username);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function deleteTenant(CometServer $server, $tenantId)
    {
        try {
            $server->getRemote()->AdminOrganizationDelete($tenantId);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function addTenantAdminAccount(Request $request, CometServer $server)
    {
        try {
            return $server->getRemote()->AdminAdminUserNew($request->username, $request->password, $request->organization_id);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function setServerConfig(CometServer $server, $username)
    {
        try {
            $serverConfig = $server->getRemote()->AdminMetaServerConfigGet();
            foreach ($serverConfig->AdminUsers as $index => $adminUser) {
                if ($adminUser->Username == $username) {
                    unset($serverConfig->AdminUsers[$index]);
                    break;
                }
            }
            $serverConfig->AdminUsers = array_merge($serverConfig->AdminUsers);

            return $server->getRemote()->AdminMetaServerConfigSet($serverConfig);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function addHttpConnector(Request $request, CometServer $server)
    {
        try {
            $serverConfig = $server->getRemote()->AdminMetaServerConfigGet();
            $httpConnector = HTTPConnectorOptions::createFromArray([
                'ListenAddress' => $server->listen_address,
                'AutoSSLDomains' => parse_url($request->host_url, PHP_URL_HOST)
            ]);
            array_push($serverConfig->ListenAddresses, $httpConnector);

            return $server->getRemote()->AdminMetaServerConfigSet($serverConfig);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function deleteHttpConnector(Request $request, CometServer $server)
    {
        try {
            $host = parse_url($request->host_url, PHP_URL_HOST);
            $serverConfig = $server->getRemote()->AdminMetaServerConfigGet();
            foreach($serverConfig->ListenAddresses as $index => $connection) {
                if ($host == $connection->AutoSSLDomains) {
                    unset($serverConfig->ListenAddresses[$index]);
                    $serverConfig->ListenAddresses = array_values($serverConfig->ListenAddresses);
                    break;
                }
            }

            return $server->getRemote()->AdminMetaServerConfigSet($serverConfig);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function addWebhook(CometServer $server)
    {
        try {
            $whiteListedEventTypes = [];
            $customHeaders = [];
            $webhookUrl = config('constants.lambda_url') . '?serverId=' . $server->id;
            $webhooks = $server->getRemote()->AdminMetaWebhookOptionsGet();
            $addKey = parse_url($server->url, PHP_URL_HOST);
            $newWebHooks = [
                "URL" => $webhookUrl,
                "WhiteListedEventTypes" => $whiteListedEventTypes,
                "CustomHeaders" => $customHeaders
            ];
            if (count($webhooks)) {
                $webhookKeys = array_keys($webhooks);
                if (in_array($addKey, $webhookKeys)) {
                    // check webhook already added
                    if ($webhookUrl == $webhooks[$addKey]->URL) {
                        // if match no need to add
                        return '';
                    }
                    $addKey = $this->getWebhookKey($addKey, $webhookKeys);
                }
            }
            $setWebhooks = array_merge($webhooks, [$addKey => WebhookOption::createFromArray($newWebHooks)]);

            return $server->getRemote()->AdminMetaWebhookOptionsSet($setWebhooks);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function deleteWebhook(CometServer $server)
    {
        try {
            $webhookUrl = $webhookUrl = config('constants.lambda_url') . '?serverId=' . $server->id;;
            $webhooks = $server->getRemote()->AdminMetaWebhookOptionsGet();

            if (count($webhooks)) {
                foreach ($webhooks as $key => $webhook) {
                    if ($webhookUrl == $webhook->URL) {
                        // remove this key from the webhooks
                        unset($webhooks[$key]);
                        break;
                    }
                }
                return $server->getRemote()->AdminMetaWebhookOptionsSet($webhooks);
            }

            return '';
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public function getWebhookKey($key, $keys, $value = 2)
    {
        $key = preg_replace('/[0-9]+/', '', $key) . $value;
        if (in_array($key, $keys)) {
            $key = $this->getWebhookKey($key, $keys, ++$value);
        }

        return $key;
    }

    public function getUserProfile(CometServer $server, $username)
    {
        try {
            return $server->getRemote()->AdminGetUserProfile($username);
        } catch (\Throwable $th) {
            return 'New user';
        }
    }

    public function getAllBuckets(CometServer $server)
    {
        try {
            return $server->getRemote()->AdminStorageListBuckets();
        } catch (\Throwable $th) {
            return null;
        }
    }

    private function loginData(Request $request)
    {
        return [
            'multipart' => [
                [
                    'name' => 'Password',
                    'contents' => $request->password
                ],
                [
                    'name' => 'AuthType',
                    'contents' => config('constants.comet_server.auth_type')
                ],
                [
                    'name' => 'Username',
                    'contents' => $request->username
                ]
            ]
        ];
    }

    private function policyData(Request $request)
    {
        // this is the storage vault ids
        $policy = [];

        if ($request->has('boosters')) {
            // boosters_source
            $boosters = [];
            foreach ($request->boosters as $booster) {
                $boosters[] = $request->boosters_source[$booster];
            }
            $policy['ProtectedItemEngineTypes'] = [
                "ShouldRestrictEngineTypeList" => true,
                "AllowedEngineTypeWhenRestricted" => $boosters
            ];
        }

        if ($request->has('vaults')) {
            // vaults_source
            $vaults = [];
            foreach ($request->vaults as $vaultId) {
                $vaults[] = $request->vaults_source[$vaultId];
            }
            $policy['StorageVaultProviders'] = [
                "ShouldRestrictProviderList" => true,
                "AllowedProvidersWhenRestricted" => $vaults
            ];
        }

        return $policy;
    }
}
