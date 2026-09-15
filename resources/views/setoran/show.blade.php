@extends('layouts.app')
@push('custom-css-files')
<link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
<style>
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
    .total-box {
        background-color: #eef2ff;
        border: 2px solid #4f46e5;
        border-radius: 0.5rem;
        padding: 0.75rem 1.25rem;
        display: inline-flex;
        align-items: center;
        gap: 1rem;
    }
    .total-box .label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 700;
    }
    .total-box .value {
        font-weight: 700;
        font-size: 1.25rem;
        color: #4f46e5;
        font-variant-numeric: tabular-nums;
    }
    .setoran-badge {
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
</style>
@endpush
@section('content')
@php
    $jumlah_detail = $setoran_detail->count();
@endphp
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-12 col-lg-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Detail {{ $title }}</h3>
                </div>
                <div class="card-body">
                    <!-- Info header -->
                    <div class="detail-info">
                        <div class="detail-item">
                            <div>
                                <span class="detail-label">Nama Penyetor</span>
                                <span class="detail-value">{{ $setoran->nama_penyetor }}</span>
                            </div>
                            <div>
                                <span class="detail-label">Tanggal Setor</span>
                                <span class="detail-value">
                                    @if($setoran->created_at) {{ Date('d-m-Y', strtotime($setoran->created_at)) }} @else - @endif
                                </span>
                            </div>
                            <div>
                                <span class="detail-label">Jumlah Transaksi</span>
                                <span class="setoran-badge">{{ $jumlah_detail }} transaksi</span>
                            </div>
                        </div>
                    </div>

                    <h5 class="card-title mb-2"><i class="fas fa-list"></i> List Transaksi Donasi</h5>
                    <table class="table table-striped" id="datatable-setoran-detail">
                        <thead>
                            <tr>
                                <th style="width:50px">No</th>
                                <th>Tanggal Donasi</th>
                                <th>Nama Donatur</th>
                                <th style="width:220px">Nominal Donasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($setoran_detail as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->date ? $item->date : Date('d-m-Y', strtotime($item->tanggal)) }}</td>
                                <td>{{ $item->nama_donatur }}</td>
                                <td>Rp {{ number_format($item->total_donasi, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Total Setor -->
                    <div class="mt-3">
                        <span class="detail-label">Total Setor</span>
                        <div class="total-box">
                            <span class="label">Total</span>
                            <span class="value">Rp {{ number_format($setoran->total_setoran, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <span class="detail-label">Bukti Setoran</span>
                        <div>
                            <img src="{{ asset($setoran->path . $setoran->nama_file) }}" style="max-width:300px; border:1px solid #e3e6f0; padding:4px; border-radius:4px" class="img-fluid mb-2">
                        </div>
                    </div>
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
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script type="text/javascript">
    $("#datatable-setoran-detail").DataTable({
        "pageLength": 15,
        "lengthChange": true,
        "lengthMenu": [[10, 15, 25, 50, -1], [10, 15, 25, 50, "Semua"]],
        "responsive": true,
        "autoWidth": false,
        "order": [[0, 'asc']],
        "language": {
            "search": "Cari Donatur:",
            "searchPlaceholder": "Ketik nama donatur...",
            "processing": "Memuat data...",
            "emptyTable": "Tidak ada transaksi dalam setoran ini",
            "zeroRecords": "Tidak ada transaksi yang cocok dengan pencarian",
            "info": "Menampilkan _START_ - _END_ dari _TOTAL_ transaksi",
            "infoEmpty": "Menampilkan 0 transaksi",
            "infoFiltered": "(difilter dari _MAX_ total transaksi)",
            "paginate": {
                "first": "Awal",
                "last": "Akhir",
                "next": "Berikutnya",
                "previous": "Sebelumnya"
            }
        }
    });
</script>
@endpush