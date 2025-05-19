<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class BitrixController extends Controller
{
    public function handleInstall(Request $request)
    {
        Log::info('Bitrix Install App - Dữ liệu nhận được:', $request->all());

        $data = $request->all();

        if (!isset($data['auth']) || !isset($data['auth']['access_token'])) {
            Log::warning('Bitrix Install App - Thiếu auth hoặc access_token');
            return response('Missing access_token in auth', 400);
        }

        $auth = $data['auth'];

        $tokenData = [
            'access_token'       => $auth['access_token'],
            'refresh_token'      => $auth['refresh_token'] ?? null,
            'expires_in'         => time() + (int) ($auth['expires_in'] ?? 3600),
            'domain'             => $auth['domain'] ?? null,
            'member_id'          => $auth['member_id'] ?? null,
            'user_id'            => $auth['user_id'] ?? null,
            'application_token'  => $auth['application_token'] ?? null,
            'scope'              => $auth['scope'] ?? null,
            'status'             => $auth['status'] ?? null,
            'server_endpoint'    => $auth['server_endpoint'] ?? null,
            'client_endpoint'    => $auth['client_endpoint'] ?? null,
        ];

        Storage::put('bitrix_tokens.json', json_encode($tokenData));

        Log::info('Bitrix Install App - Token và thông tin khác đã được lưu thành công');

        return response('App installed successfully');
    }

    public function testApi()
    {
        if (!Storage::exists('bitrix_tokens.json')) {
            return response()->json(['error' => 'Token chưa được lưu. Vui lòng cài lại app.']);
        }

        $response = $this->callBitrixApi('crm.contact.list');

        return response()->json($response);
    }

    private function callBitrixApi($method, $params = [])
    {

        $tokens = json_decode(Storage::get('bitrix_tokens.json'), true);

        // Nếu hết hạn token thì làm mới
        if (time() >= $tokens['expires_in']) {
            try {
                $tokens = $this->refreshToken($tokens['refresh_token']);
            } catch (\Exception $e) {
                return ['error' => 'refresh_failed', 'message' => $e->getMessage()];
            }
        }

        try {
            $url = "{$tokens['client_endpoint']}{$method}";

            $res = Http::get($url, array_merge([
                'auth' => $tokens['access_token']
            ], $params));

            $data = $res->json();

            // Token hết hạn, tự động refresh và thử lại
            if (isset($data['error']) && $data['error'] === 'expired_token') {
                $tokens = $this->refreshToken($tokens['refresh_token']);
                return $this->callBitrixApi($method, $params);
            }

            return $data;
        } catch (\Exception $e) {
            return ['error' => 'network_error', 'message' => $e->getMessage()];
        }
    }

    private function refreshToken($refreshToken)
    {
        $res = Http::get('https://oauth.bitrix.info/oauth/token/', [
            'grant_type' => 'refresh_token',
            'client_id' => env('BITRIX_CLIENT_ID'),
            'client_secret' => env('BITRIX_CLIENT_SECRET'),
            'refresh_token' => $refreshToken
        ]);

        $data = $res->json();

        if (!isset($data['access_token'])) {
            throw new \Exception('Không thể gia hạn token');
        }

        $old = json_decode(Storage::get('bitrix_tokens.json'), true);

        $updated = [
            'access_token'       => $data['access_token'],
            'refresh_token'      => $data['refresh_token'] ?? null,
            'expires_in'         => time() + (int) ($data['expires_in'] ?? 3600),
            'domain'             => $old['domain'] ?? null,
            'member_id'          => $old['member_id'] ?? null,
            'user_id'            => $old['user_id'] ?? null,
            'application_token'  => $old['application_token'] ?? null,
            'scope'              => $old['scope'] ?? null,
            'status'             => $old['status'] ?? null,
            'server_endpoint'    => $old['server_endpoint'] ?? null,
            'client_endpoint'    => $old['client_endpoint'] ?? null,
        ];

        Storage::put('bitrix_tokens.json', json_encode($updated));
        return $updated;
    }

    public function index()
    {
        $res = $this->callBitrixApi('crm.contact.list', [
            'select' => ['ID', 'NAME']
        ]);

        $contacts = $res['result'] ?? [];

        return view('bitrix.contacts.index', compact('contacts'));
    }

    public function create()
    {
        return view('bitrix.contacts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'nullable|email',
            'phone' => 'nullable',
            'website' => 'nullable|url',
            'ward' => 'nullable',
            'district' => 'nullable',
            'city' => 'nullable',
            'bank_name' => 'nullable',
            'bank_account' => 'nullable',
        ]);

        $contactRes = $this->callBitrixApi('crm.contact.add', [
            'fields' => [
                'NAME' => $request->name,
                'EMAIL' => [['VALUE' => $request->email, 'VALUE_TYPE' => 'WORK']],
                'PHONE' => [['VALUE' => $request->phone, 'VALUE_TYPE' => 'WORK']],
                'WEB'   => [['VALUE' => $request->website, 'VALUE_TYPE' => 'WORK']],
            ]
        ]);

        if (!isset($contactRes['result'])) {
            return back()->with('error', 'Lỗi tạo contact.');
        }

        $contactId = $contactRes['result'];

        $requisiteRes = $this->callBitrixApi('crm.requisite.add', [
            'fields' => [
                'ENTITY_TYPE_ID' => 3,
                'ENTITY_ID' => $contactId,
                'PRESET_ID' => 1,
                'NAME' => 'Thông tin liên hệ',
            ]
        ]);

        if (!isset($requisiteRes['result'])) {
            return back()->with('error', 'Tạo contact OK nhưng lỗi tạo requisite.');
        }

        $requisiteId = $requisiteRes['result'];

        $this->callBitrixApi('crm.address.add', [
            'fields' => [
                'TYPE_ID' => 1,
                'ENTITY_TYPE_ID' => 8,
                'ENTITY_ID' => $requisiteId,
                'ADDRESS_2' => $request->ward,
                'CITY' => $request->district,
                'REGION' => $request->city,
                'COUNTRY' => 'Việt Nam'
            ]
        ]);

        $this->callBitrixApi('crm.requisite.bankdetail.add', [
            'fields' => [
                'ENTITY_ID' => $requisiteId,
                'ENTITY_TYPE_ID' => 8,
                'NAME' => $request->bank_name,
                'RQ_BANK_NAME' => $request->bank_name,
                'RQ_ACC_NUM' => $request->bank_account
            ]
        ]);

        return redirect()->route('bitrix.contacts.index')->with('success', 'Thêm contact thành công!');
    }

    public function edit($id)
    {
        $resContact = $this->callBitrixApi('crm.contact.get', ['id' => $id]);
        if (!isset($resContact['result'])) {
            return redirect()->route('bitrix.contacts.index')->with('error', 'Không tìm thấy contact');
        }
        $contact = $resContact['result'];

        $resRequisite = $this->callBitrixApi('crm.requisite.list', [
            'filter' => ['ENTITY_TYPE_ID' => 3, 'ENTITY_ID' => $id]
        ]);
        $requisite = $resRequisite['result'][0] ?? null;

        $address = null;
        $bank = null;

        if ($requisite) {
            $resAddress = $this->callBitrixApi('crm.address.list', [
                'filter' => ['ENTITY_TYPE_ID' => 8, 'ENTITY_ID' => $requisite['ID'], 'TYPE_ID' => 1]
            ]);
            $address = $resAddress['result'][0] ?? null;

            $resBank = $this->callBitrixApi('crm.requisite.bankdetail.list', [
                'filter' => ['ENTITY_TYPE_ID' => 8, 'ENTITY_ID' => $requisite['ID']]
            ]);
            $bank = $resBank['result'][0] ?? null;
        }

        return view('bitrix.contacts.edit', compact('contact', 'requisite', 'address', 'bank'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'nullable|email',
            'phone' => 'nullable',
            'website' => 'nullable|url',
            'ward' => 'nullable',
            'district' => 'nullable',
            'city' => 'nullable',
            'bank_name' => 'nullable',
            'bank_account' => 'nullable',
        ]);

        $contactRes = $this->callBitrixApi('crm.contact.update', [
            'id' => $id,
            'fields' => [
                'NAME' => $request->name,
                'EMAIL' => [['VALUE' => $request->email, 'VALUE_TYPE' => 'WORK']],
                'PHONE' => [['VALUE' => $request->phone, 'VALUE_TYPE' => 'WORK']],
                'WEB'   => [['VALUE' => $request->website, 'VALUE_TYPE' => 'WORK']],
            ]
        ]);

        if (empty($contactRes['result'])) {
            return back()->with('error', 'Cập nhật contact thất bại');
        }

        $resRequisite = $this->callBitrixApi('crm.requisite.list', [
            'filter' => ['ENTITY_TYPE_ID' => 3, 'ENTITY_ID' => $id]
        ]);
        $requisite = $resRequisite['result'][0] ?? null;

        if (!$requisite) {
            return back()->with('error', 'Không tìm thấy requisites của contact');
        }

        $requisiteId = $requisite['ID'];

        $resAddress = $this->callBitrixApi('crm.address.list', [
            'filter' => ['ENTITY_TYPE_ID' => 8, 'ENTITY_ID' => $requisiteId, 'TYPE_ID' => 1]
        ]);
        $address = $resAddress['result'][0] ?? null;

        if ($address && isset($address['LOC_ADDR_ID'])) {
            $this->callBitrixApi('crm.address.update', [
                'id' => $address['LOC_ADDR_ID'],
                'fields' => [
                    'TYPE_ID' => 1,
                    'ENTITY_TYPE_ID' => 8,
                    'ENTITY_ID' => $requisiteId,
                    'ADDRESS_2' => $request->ward,
                    'CITY' => $request->district,
                    'REGION' => $request->city,
                    'COUNTRY' => 'Việt Nam',
                    'ANCHOR_TYPE_ID' => 3,
                    'ANCHOR_ID' => $id
                ]
            ]);
        } else {
            $this->callBitrixApi('crm.address.add', [
                'fields' => [
                    'TYPE_ID' => 1,
                    'ENTITY_TYPE_ID' => 8,
                    'ENTITY_ID' => $requisiteId,
                    'ADDRESS_2' => $request->ward,
                    'CITY' => $request->district,
                    'REGION' => $request->city,
                    'COUNTRY' => 'Việt Nam',
                    'ANCHOR_TYPE_ID' => 3,
                    'ANCHOR_ID' => $id
                ]
            ]);
        }

        $resBank = $this->callBitrixApi('crm.requisite.bankdetail.list', [
            'filter' => ['ENTITY_TYPE_ID' => 8, 'ENTITY_ID' => $requisiteId]
        ]);
        $bank = $resBank['result'][0] ?? null;

        if ($bank) {
            $this->callBitrixApi('crm.requisite.bankdetail.update', [
                'id' => $bank['ID'],
                'fields' => [
                    'NAME' => $request->bank_name,
                    'RQ_BANK_NAME' => $request->bank_name,
                    'RQ_ACC_NUM' => $request->bank_account
                ]
            ]);
        } else {
            $this->callBitrixApi('crm.requisite.bankdetail.add', [
                'fields' => [
                    'ENTITY_ID' => $requisiteId,
                    'ENTITY_TYPE_ID' => 8,
                    'NAME' => $request->bank_name,
                    'RQ_BANK_NAME' => $request->bank_name,
                    'RQ_ACC_NUM' => $request->bank_account
                ]
            ]);
        }

        return redirect()->route('bitrix.contacts.index')->with('success', 'Cập nhật contact thành công');
    }

    public function destroy($id)
    {
        $res = $this->callBitrixApi('crm.contact.delete', ['id' => $id]);

        if (isset($res['result']) && $res['result'] === true) {
            return redirect()->route('bitrix.contacts.index')->with('success', 'Xóa contact thành công');
        }

        return redirect()->route('bitrix.contacts.index')->with('error', 'Xóa contact thất bại');
    }
}
