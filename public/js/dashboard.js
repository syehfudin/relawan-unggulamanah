$(document).ready(function () {
    $("#tanggal").datepicker({
        dateFormat: "dd-mm-yy",
    });

    loadTanggal(Date.now());
    dt = $("#" + tabledaily).DataTable({
        responsive: true,
        columnDefs: [
            {
                targets: [3],
                render: function (data, type, row) {
                    return (
                        "Rp " +
                        data
                            .toString()
                            .replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
                    );
                },
            },
        ],
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"],
        paging: false,
        ordering: false,
        searching: false,
        info: false,
        ajax: {
            url: dataUrl,
            data: function (d) {
                d.type = "daily";
                d.tanggal = $("#tanggal").val();
            },
        },
        columns: columns,
        footerCallback: function (row, data, start, end, display) {
            var api = this.api(),
                data;

            // converting to interger to find total
            var intVal = function (i) {
                return typeof i === "string"
                    ? i.replace(/[\$,]/g, "") * 1
                    : typeof i === "number"
                    ? i
                    : 0;
            };

            // computing column Total of the complete result
            var countTotal = api
                .column(2)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            var sumTotal = api
                .column(3)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Update footer by showing the total with the reference of the column index
            $(api.column(1).footer()).html("Total");
            $(api.column(2).footer()).html(countTotal);
            $(api.column(3).footer()).html(
                "Rp " +
                    sumTotal
                        .toString()
                        .replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
            );
        },
    });

    dt1 = $("#" + tablemonhtly).DataTable({
        columnDefs: [
            {
                targets: [3],
                render: function (data, type, row) {
                    return (
                        "Rp " +
                        data
                            .toString()
                            .replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
                    );
                },
            },
        ],
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        processing: true,
        serverSide: true,
        paging: false,
        ordering: false,
        searching: false,
        info: false,
        ajax: {
            url: dataUrl,
            data: function (d) {
                d.type = "monthly";
                d.tanggal = $("#tanggal").val();
            },
        },
        columns: columns,
        footerCallback: function (row, data, start, end, display) {
            var api = this.api(),
                data;

            // converting to interger to find total
            var intVal = function (i) {
                return typeof i === "string"
                    ? i.replace(/[\$,]/g, "") * 1
                    : typeof i === "number"
                    ? i
                    : 0;
            };

            // computing column Total of the complete result
            var countTotal = api
                .column(2)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            var sumTotal = api
                .column(3)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Update footer by showing the total with the reference of the column index
            $(api.column(1).footer()).html("Total");
            $(api.column(2).footer()).html(countTotal);
            $(api.column(3).footer()).html(
                "Rp " +
                    sumTotal
                        .toString()
                        .replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
            );
        },
    });

    dt2 = $("#" + tableyearly).DataTable({
        columnDefs: [
            {
                targets: [3, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26],
                render: function (data, type, row) {
                    return data
                        .toString()
                        .replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,");
                },
            },
        ],
        scrollX: true,
        lengthChange: false,
        autoWidth: false,
        processing: true,
        serverSide: true,
        paging: false,
        ordering: false,
        searching: false,
        info: false,
        ajax: {
            url: dataUrl,
            data: function (d) {
                d.type = "yearly";
                d.tanggal = $("#tanggal").val();
            },
        },
        columns: [
            { data: "DT_RowIndex", name: "DT_RowIndex" },
            { data: "nama_program", name: "nama_program" },
            { data: "count_nominal", name: "count_nominal" },
            {
                data: "sum_nonimal",
                name: "sum_nonimal",
                className: "text-right",
            },
            { data: "jan", name: "jan", className: "text-right" },
            { data: "count_jan", name: "count_jan" },
            { data: "feb", name: "feb", className: "text-right" },
            { data: "count_feb", name: "count_feb" },
            { data: "mar", name: "mar", className: "text-right" },
            { data: "count_mar", name: "count_mar" },
            { data: "apr", name: "apr", className: "text-right" },
            { data: "count_apr", name: "count_apr" },
            { data: "mei", name: "mei", className: "text-right" },
            { data: "count_mei", name: "count_mei" },
            { data: "jun", name: "jun", className: "text-right" },
            { data: "count_jun", name: "count_jun" },
            { data: "jul", name: "jul", className: "text-right" },
            { data: "count_jul", name: "count_jul" },
            { data: "agu", name: "agu", className: "text-right" },
            { data: "count_agu", name: "count_agu" },
            { data: "sep", name: "sep", className: "text-right" },
            { data: "count_sep", name: "count_sep" },
            { data: "okt", name: "okt", className: "text-right" },
            { data: "count_okt", name: "count_okt" },
            { data: "nov", name: "nov", className: "text-right" },
            { data: "count_nov", name: "count_nov" },
            { data: "des", name: "des", className: "text-right" },
            { data: "count_des", name: "count_des" },
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api(),
                data;

            // converting to interger to find total
            var intVal = function (i) {
                return typeof i === "string"
                    ? i.replace(/[\$,]/g, "") * 1
                    : typeof i === "number"
                    ? i
                    : 0;
            };

            $(api.column(1).footer()).html("Total");
            // computing column Total of the complete result
            for (i = 2; i < 28; i++) {
                var Total = api
                    .column(i)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);
                $(api.column(i).footer()).html(
                    Total.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
                );
            }
        },
    });

    loadChar();

    function loadChar() {
        let tanggal = $("#tanggal").val();
        $.ajax({
            url: dataChartUrl,
            data: { tanggal },
            dataType: "json",
            type: "post",
            success: function (data) {
                let mon = [
                    "01",
                    "02",
                    "03",
                    "04",
                    "05",
                    "06",
                    "07",
                    "08",
                    "09",
                    "10",
                    "11",
                    "12",
                ];
                var label = [];
                var value = [];
                for (var i in mon) {
                    let nominal = data
                        .filter(function (e) {
                            return mon[i] == e.mon;
                        })
                        .map((data) => {
                            return data.total_donasi;
                        });
                    value.push(nominal.toString() || null);
                }

                var areaChartOptions = {
                    maintainAspectRatio: false,
                    responsive: true,
                    legend: {
                        display: false,
                    },
                    scales: {
                        xAxes: [
                            {
                                gridLines: {
                                    display: false,
                                },
                            },
                        ],
                        yAxes: [
                            {
                                gridLines: {
                                    display: false,
                                },
                            },
                        ],
                    },
                };

                var lineChartCanvas = $("#chartjs-line");
                var lineChartOptions = $.extend(true, {}, areaChartOptions);

                var lineChart = new Chart(lineChartCanvas, {
                    type: "line",
                    data: {
                        labels: [
                            "Jan",
                            "Feb",
                            "Mar",
                            "Apr",
                            "May",
                            "Jun",
                            "Jul",
                            "Aug",
                            "Sep",
                            "Oct",
                            "Nov",
                            "Dec",
                        ],
                        datasets: [
                            {
                                label: "Donasi (Rp)",
                                backgroundColor: "rgba(60,141,188,0.9)",
                                borderColor: "rgba(60,141,188,0.8)",
                                pointRadius: false,
                                pointColor: "#3b8bba",
                                pointStrokeColor: "rgba(60,141,188,1)",
                                pointHighlightFill: "#fff",
                                pointHighlightStroke: "rgba(60,141,188,1)",
                                data: value,
                            },
                        ],
                    },
                    options: lineChartOptions,
                });
            },
        });
    }

    $("#tanggal").on("change", function () {
        dt.ajax.reload();
        dt1.ajax.reload();
        dt2.ajax.reload();
        loadChar();
        const tanggal = $("#tanggal").val();
        const dateParts = tanggal.split("-");
        const day = dateParts[0];
        const month = dateParts[1];
        const year = dateParts[2];
        loadTanggal(year + "-" + month + "-" + day);
    });

    function loadTanggal(tanggal) {
        const date = new Date(tanggal);
        const options = { year: "numeric", month: "long", day: "numeric" };
        const indonesianDate = date.toLocaleDateString("id-ID", options);
        const month = date.getMonth();
        const monthName = [
            "Januari",
            "Februari",
            "Maret",
            "April",
            "Mei",
            "Juni",
            "Juli",
            "Agustus",
            "September",
            "Oktober",
            "November",
            "Desember",
        ][month];
        let year = 1900 + date.getYear();
        $(".tgl").html(indonesianDate);
        $(".bulan").html(monthName + " " + year);
        $(".tahun").html(year);
    }
});
