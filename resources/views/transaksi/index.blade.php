@extends('layouts.app')
@push('custom-css-files')
<link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
@endpush
@section('content')
@php
    $userRole = strtolower(Auth::user()->roles[0]->name);
    $myPegawaiId = Auth::user()->pegawai_id;
@endphp
<div class="container-fluid">
    <div class="d-flex align-items-center py-2 py-md-2">
        @can('transaksi-create')
        <a class="btn btn-success" href="{{ route('transaksi.create') }}"> Tambah Transaksi</a>
        @endcan
    </div>
    <!-- Filter Panel -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Filter Data</h3>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-12 col-md-3">
                    <label class="fs-6 fw-bold mb-1">Tanggal Mulai</label>
                    <input type="text" class="form-control" id="filter_date_from" placeholder="dd-mm-yyyy" autocomplete="off">
                </div>
                <div class="col-12 col-md-3">
                    <label class="fs-6 fw-bold mb-1">Tanggal Sampai</label>
                    <input type="text" class="form-control" id="filter_date_to" placeholder="dd-mm-yyyy" autocomplete="off">
                </div>
                @if($userRole != 'relawan')
                <div class="col-12 col-md-3">
                    <label class="fs-6 fw-bold mb-1">Nama Relawan</label>
                    <select class="form-control" id="filter_pegawai">
                        <option value="">Semua Relawan</option>
                        @foreach($relawan as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" id="filter_pegawai" value="{{ $myPegawaiId }}">
                @endif
                <div class="col-12 col-md-3">
                    <button type="button" class="btn btn-primary btn-block" id="btn_apply_filter">
                        <i class="fas fa-search"></i> Terapkan Filter
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-lg-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped" id="datatable-transaksi">
                        <thead>
                            <tr align="center">
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama Relawan</th>
                                <th>Nama Donatur</th>
                                <th>Jenis Pembayaran</th>
                                <th>Keterangan</th>
                                <th>Total Donasi</th>
                                <th width="280px">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('custom-js-files')
<!-- DataTables  & Plugins -->
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
<script src="{{ asset('js/jquery.maskMoney.js') }}"></script>
<script type="text/javascript">
    let dataUrl = "{{ route('transaksi.index_data') }}";
    let tableSelector = "datatable-transaksi";

    // Datepicker for filters
    $("#filter_date_from").datepicker({ dateFormat: 'dd-mm-yy' });
    $("#filter_date_to").datepicker({ dateFormat: 'dd-mm-yy' });

    dt = $("#" + tableSelector).DataTable({
        order: [1, 'desc'],
        columnDefs: [
            {
                targets: [1],
                render: function (data, type, row) {
                    var datePart = data.match(/\d+/g),
                        year = datePart[0],
                        month = datePart[1],
                        day = datePart[2];

                    return day+'-'+month+'-'+year;
                }
            },
            {
                targets: [6],
                render: function (data, type, row) {
                    let total = new Intl.NumberFormat().format(data)
                    return 'Rp. '+total;
                }
            }
        ],
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "processing": true,
        "serverSide": true,
        "searching": true,
        "ajax": {
            "url": dataUrl,
            "data": function (d) {
                d.date_from = $("#filter_date_from").val();
                d.date_to = $("#filter_date_to").val();
                d.pegawai_id = $("#filter_pegawai").val() || "";
            }
        },
        columns: [
            { data: "DT_RowIndex", name: "DT_RowIndex" },
            { data: "tanggal_donasi", name: "tanggal_donasi" },
            { data: "nama_relawan", name: "nama_relawan" },
            { data: "nama_donatur", name: "nama_donatur" },
            { data: "jenis_transaksi", name: "jenis_transaksi" },
            { data: "keterangan", name: "keterangan" },
            { data: "total_donasi", name: "total_donasi", class: 'text-right' },
            {
                data: "action",
                name: "action",
                orderable: true,
                searchable: true,
            },
        ],
    });
    table = dt.$;

    // Apply filter button: reload table with filter values
    $("#btn_apply_filter").on('click', function() {
        dt.ajax.reload();
    });

    // Also apply filter on Enter key in date fields
    $("#filter_date_from, #filter_date_to").on('keypress', function(e) {
        if (e.which === 13) {
            dt.ajax.reload();
        }
    });
</script>
@endpush