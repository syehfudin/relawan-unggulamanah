@extends('layouts.app')
@push('custom-css-files')
<link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
<style>
    .program-table {
        width: 100%;
        border-collapse: collapse;
    }
    .program-table th,
    .program-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e3e6f0;
        vertical-align: middle;
    }
    .program-table thead th {
        background-color: #f8f9fc;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #e3e6f0;
    }
    .program-table tbody tr:last-child td {
        border-bottom: none;
    }
    .program-table .text-right {
        text-align: right;
    }
    .program-badge {
        display: inline-flex;
        align-items: center;
        background-color: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
        border-radius: 50rem;
        padding: 0.35rem 0.9rem;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .program-badge .badge-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #6366f1;
        margin-right: 0.5rem;
        display: inline-block;
    }
    .detail-info {
        background-color: #f8f9fc;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }
    .detail-item {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
    }
    .detail-item > div {
        min-width: 200px;
    }
    .detail-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 700;
        margin-bottom: 0.25rem;
        display: block;
    }
    .detail-value {
        font-weight: 600;
        color: #1f2937;
    }
    .total-row td {
        background-color: #f8f9fc;
        font-weight: 700;
        font-size: 1rem;
        border-top: 2px solid #4f46e5;
    }
    .nominal-text {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }
</style>
@endpush
@section('content')
@php
    $total_donasi = 0;
    $jumlah_program = $transaksi_detail->count();
@endphp
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-12 col-lg-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Detail {{ $title }}</h3>
                </div>
                <div class="card-body">
                    <!-- Info header: relawan, donatur, jenis -->
                    <div class="detail-info">
                        <div class="detail-item">
                            <div>
                                <span class="detail-label">Nama Relawan</span>
                                <span class="detail-value">
                                    @foreach($relawan as $item)
                                        @if($item->id == $transaksi->pegawai_id) {{ $item->nama }} @endif
                                    @endforeach
                                </span>
                            </div>
                            <div>
                                <span class="detail-label">Nama Donatur</span>
                                <span class="detail-value">
                                    @foreach($donatur as $item)
                                        @if($item->id == $transaksi->donatur_id) {{ $item->nama }} @endif
                                    @endforeach
                                </span>
                            </div>
                            <div>
                                <span class="detail-label">Jenis Transaksi</span>
                                <span class="detail-value">
                                    @if($transaksi->jenis_transaksi == 'transfer')
                                        <span class="program-badge"><span class="badge-dot" style="background-color:#0d9488"></span> Transfer ke Rek ULAMA</span>
                                    @else
                                        <span class="program-badge" style="background-color:#fef3c7;color:#92400e;border-color:#fde68a"><span class="badge-dot" style="background-color:#f59e0b"></span> Titip di Relawan</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Program table -->
                    <h5 class="card-title mb-2">
                        <i class="fas fa-hand-holding-heart"></i>
                        Program Donasi
                        <span class="program-badge" style="margin-left:0.5rem">{{ $jumlah_program }} program</span>
                    </h5>
                    <table class="program-table">
                        <thead>
                            <tr>
                                <th style="width:50px" class="text-center">No</th>
                                <th>Nama Program</th>
                                <th style="width:220px" class="text-right">Nominal Donasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksi_detail as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="program-badge"><span class="badge-dot"></span> {{ $item->nama_program }}</span>
                                    </td>
                                    <td class="text-right nominal-text">Rp {{ number_format($item->nominal_donasi, 0, ',', '.') }}</td>
                                    @php $total_donasi += $item->nominal_donasi @endphp
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Tidak ada program donasi</td>
                                </tr>
                            @endforelse
                            <tr class="total-row">
                                <td colspan="2" class="text-right">Total Donasi</td>
                                <td class="text-right nominal-text" style="color:#4f46e5">Rp {{ number_format($total_donasi, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Bukti transfer -->
                    @if($transaksi->jenis_transaksi == 'transfer')
                    <div class="mt-4">
                        <span class="detail-label">Bukti Transfer</span>
                        <div>
                            <img src="{{ asset($transaksi->path . $transaksi->nama_file) }}" class="img-fluid rounded" style="max-width:360px; border:1px solid #e3e6f0; padding:4px" alt="Bukti Transfer">
                        </div>
                    </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a class="btn btn-primary" href="{{ $redirectUrl }}"> <i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('custom-js-files')
@endpush