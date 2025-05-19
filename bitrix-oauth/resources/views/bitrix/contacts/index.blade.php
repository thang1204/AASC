@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Danh sách Contact</h2>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <a href="{{ route('bitrix.contacts.create') }}" class="btn btn-success mb-3">Thêm mới</a>

        @if (count($contacts) > 0)
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contacts as $contact)
                        <tr>
                            <td>{{ $contact['NAME'] }}</td>
                            <td>
                                <a href="{{ route('bitrix.contacts.edit', $contact['ID']) }}" class="btn btn-primary btn-sm">Sửa</a>

                                <form action="{{ route('bitrix.contacts.destroy', $contact['ID']) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn xoá?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xoá</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>Không có contact nào.</p>
        @endif
    </div>
@endsection
