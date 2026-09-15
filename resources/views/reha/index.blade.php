@extends('layouts.app')
@push('custom-css-files')
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <style>
        .detail-info {
            background-color: #f8f9fc;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }
        .donatur-counter {
            display: inline-flex;
            align-items: center;
            background-color: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            border-radius: 50rem;
            padding: 0.3rem 0.9rem;
            font-weight: 700;
            font-size: 0.875rem;
        }
    </style>
@endpush
@section('content')
    <div class="container-fluid">
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
                    <div class="col-12 col-md-3">
                        <button type="button" class="btn btn-primary btn-block" id="btn_apply_filter">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center py-2 py-md-2">
            @can('reha-create')
                <a class="btn btn-success" href="{{ route('reha.create') }}"> Buat Report Harian</a>
            @endcan
        </div>
        <div class="row">
            <div class="col-12 col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="datatable-reha">
                            <thead>
                                <tr>
                                    <th style="width:50px">No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Relawan</th>
                                    <th>Realisasi</th>
                                    <th>Donatur Baru</th>
                                    <th>Donatur Lama</th>
                                    <th>Deal Hari Ini</th>
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
    <script type="text/javascript">
        $(document).ready(function () {
            let dataUrl = "{{ route('reha.index_data') }}";

            $("#filter_date_from").datepicker({ dateFormat: 'dd-mm-yy' });
            $("#filter_date_to").datepicker({ dateFormat: 'dd-mm-yy' });

            dt = $("#datatable-reha").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                "searching": true,
                "order": [0, 'desc'],
                "language": {
                    "search": "Cari Relawan:",
                    "searchPlaceholder": "Ketik nama relawan...",
                    "processing": "Memuat data...",
                    "emptyTable": "Tidak ada report harian",
                    "zeroRecords": "Tidak ada report yang cocok dengan pencarian",
                    "info": "Menampilkan _START_ - _END_ dari _TOTAL_ report",
                    "infoEmpty": "Menampilkan 0 report",
                    "infoFiltered": "(difilter dari _MAX_ total report)",
                    "paginate": {
                        "first": "Awal",
                        "last": "Akhir",
                        "next": "Berikutnya",
                        "previous": "Sebelumnya"
                    }
                },
                "ajax": {
                    "url": dataUrl,
                    "data": function (d) {
                        d.date_from = $("#filter_date_from").val();
                        d.date_to = $("#filter_date_to").val();
                    }
                },
                columns: [{
                        data: "DT_RowIndex",
                        name: "DT_RowIndex"
                    },
                    {
                        data: "tanggal",
                        name: "tanggal",
                        render: function (data) {
                            if (!data) return '';
                            var parts = data.split('-');
                            if (parts.length === 3) return parts[2] + '-' + parts[1] + '-' + parts[0];
                            return data;
                        }
                    },
                    {
                        data: "nama_relawan",
                        name: "nama_relawan"
                    },
                    {
                        data: "realisasi_summary",
                        name: "realisasi_summary",
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            return '<i class="fas fa-walking text-primary"></i> ' + data;
                        }
                    },
                    {
                        data: "donatur_baru_summary",
                        name: "donatur_baru_summary",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: "donatur_lama_summary",
                        name: "donatur_lama_summary",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: "deal_summary",
                        name: "deal_summary",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: "action",
                        name: "action",
                        orderable: false,
                        searchable: false,
                    },
                ],
            });
            table = dt.$;

            $("#btn_apply_filter").on('click', function () {
                dt.ajax.reload();
            });

            $("#filter_date_from, #filter_date_to").on('keypress', function (e) {
                if (e.which === 13) {
                    dt.ajax.reload();
                }
            });
        });
    </script>
@endpush