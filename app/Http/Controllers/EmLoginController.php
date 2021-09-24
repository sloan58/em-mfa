<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\ArrayToXml\ArrayToXml;

class EmLoginController extends Controller
{
    protected $axl;

    function __construct()
    {
        ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

        $this->axl = new \SoapClient(
            storage_path('wsdl/axl/12.5/AXLAPI.wsdl'),
            [
                'trace' => 1,
                'exceptions' => true,
                'location' => "https://10.175.200.10:8443/axl/",
                'login' => 'Administrator',
                'password' => 'Password',
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'ciphers' => 'SHA1'
                    ]
                ])
            ]
        );
    }
    /**
     * EM Entrypoint
     */
    public function index()
    {
        $menu = [
            'Title' => 'Cisco Duo EM MFA',
            'Prompt' => 'Enter Username and PIN',
            'URL' => [
                '_attributes' => ['method' => 'get'],
                '_value' => sprintf("%s/api/em/login?device=#DEVICENAME#", env('APP_URL'))
            ],
            'InputItem' => [
                [
                    'DisplayName' => 'User Name',
                    'QueryStringParam' => 'userid',
                    'DefaultValue' => '',
                    'InputFlags' => 'A'
                ],
                [
                    'DisplayName' => 'PIN',
                    'QueryStringParam' => 'pin',
                    'DefaultValue' => '',
                    'InputFlags' => 'NP'
                ]
            ],
        ];
        \Log::info('array', $menu);
        $result = ArrayToXml::convert($menu, 'CiscoIPPhoneInput');
        return response($result, 200)->header('Content-Type', 'text/xml');;
    }

    /**
     * IP Phone Login Request
     */
    public function login()
    {
        $device = request()->get('device');
        $userid = request()->get('userid');
        $pin    = request()->get('pin');

        \Log::info('request', [$device, $userid, $pin]);

        // Authenticate with UCM
        try {
            $res = $this->axl->doAuthenticateUser([
                'userid' => $userid,
                'pin' => $pin
            ]);

            if (filter_var($res->return->userAuthenticated, FILTER_VALIDATE_BOOLEAN)) {

                // Query Duo
                $ikey = 'DUO_I_KEY';
                $skey = 'DUO_S_KEY';
                $host = 'api-ebc76152.duosecurity.com';

                $duo = new \DuoAPI\Auth($ikey, $skey, $host);

                $res = $duo->auth('marty@karmatek.io', 'push', [
                    'device' => 'auto',
                    'type' => 'Extension Mobility MFA',
                    'display_username' => 'Cisco EM Service'
                ]);

                if ($res['success']) {
                    // Login device profile
                    try {
                        $res = $this->axl->doDeviceLogin([
                            'deviceName' => $device,
                            'loginDuration' => '60',
                            'profileName' => 'MartyEMProfile',
                            'userId' => $userid
                        ]);
                        \Log::info('res', [$res]);
                        return response('Success!  Logging you in now.', 200);
                    } catch (\SoapFault $e) {
                        return response($e->getMessage(), 200);
                    }
                }
            }

            return response('Auth Unsuccessful', 200);
        } catch (\SoapFault $e) {
            return response($e->getMessage(), 200);
        }
    }
}
