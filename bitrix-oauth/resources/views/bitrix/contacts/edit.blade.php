@extends('layouts.app')

@section('content')
<h2>Sửa Contact</h2>

<form method="POST" action="{{ route('bitrix.contacts.update', $contact['ID']) }}">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label>Tên</label>
        <input type="text" name="name" class="form-control" value="{{ $contact['NAME'] ?? '' }}" required>
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="{{ $contact['EMAIL'][0]['VALUE'] ?? '' }}">
    </div>

    <div class="mb-3">
        <label>Điện thoại</label>
        <input type="text" name="phone" class="form-control" value="{{ $contact['PHONE'][0]['VALUE'] ?? '' }}">
    </div>

    <div class="mb-3">
        <label>Website</label>
        <input type="url" name="website" class="form-control" value="{{ $contact['WEB'][0]['VALUE'] ?? '' }}">
    </div>

    <hr>
    <h5>Địa chỉ</h5>

    <div class="mb-3">
        <label>Phường/Xã</label>
        <input type="text" name="ward" class="form-control" value="{{ $address['ADDRESS_2'] ?? '' }}">
    </div>

    <div class="mb-3">
        <label>Quận/Huyện</label>
        <input type="text" name="district" class="form-control" value="{{ $address['CITY'] ?? '' }}">
    </div>

    <div class="mb-3">
        <label>Tỉnh/Thành phố</label>
        <input type="text" name="city" class="form-control" value="{{ $address['REGION'] ?? '' }}">
    </div>

    <hr>
    <h5>Ngân hàng</h5>

    <div class="mb-3">
        <label>Tên ngân hàng</label>
        <input type="text" name="bank_name" class="form-control" value="{{ $bank['RQ_BANK_NAME'] ?? '' }}">
    </div>

    <div class="mb-3">
        <label>Số tài khoản</label>
        <input type="text" name="bank_account" class="form-control" value="{{ $bank['RQ_ACC_NUM'] ?? '' }}">
    </div>

    <button type="submit" class="btn btn-primary">Cập nhật</button>
</form>
@endsection