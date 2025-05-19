@extends('layouts.app')

@section('content')
<h2>Thêm Contact mới</h2>

<form method="POST" action="{{ route('bitrix.contacts.store') }}">
    @csrf

    <div class="mb-3">
        <label>Tên</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control">
    </div>

    <div class="mb-3">
        <label>Điện thoại</label>
        <input type="text" name="phone" class="form-control">
    </div>

    <div class="mb-3">
        <label>Website</label>
        <input type="url" name="website" class="form-control">
    </div>

    <hr>
    <h5>Địa chỉ</h5>

    <div class="mb-3">
        <label>Phường/Xã</label>
        <input type="text" name="ward" class="form-control">
    </div>

    <div class="mb-3">
        <label>Quận/Huyện</label>
        <input type="text" name="district" class="form-control">
    </div>

    <div class="mb-3">
        <label>Tỉnh/Thành phố</label>
        <input type="text" name="city" class="form-control">
    </div>

    <hr>
    <h5>Ngân hàng</h5>

    <div class="mb-3">
        <label>Tên ngân hàng</label>
        <input type="text" name="bank_name" class="form-control">
    </div>

    <div class="mb-3">
        <label>Số tài khoản</label>
        <input type="text" name="bank_account" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Lưu</button>
</form>
@endsection
