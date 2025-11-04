{{-- Logika @if dimulai dari baris PERTAMA --}}
@if(isset($is_pdf) && $is_pdf == true)

{{-- ================================================== --}}
{{-- BAGIAN 1: INI ADALAH KODE UNTUK PDF --}}
{{-- ================================================== --}}

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan</title>
    <style>
        body {
            font-family: 'sans-serif';
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
        }
        .header p {
            font-size: 12px;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-size: 11px;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row th {
            font-size: 11px;
        }
        .footer {
            margin-top: 20px;
            font-size: 9px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Keuangan</h1>
        @if ($dari_tanggal && $sampai_tanggal)
            <p>Periode: {{ \Carbon\Carbon::parse($dari_tanggal)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($sampai_tanggal)->format('d-m-Y') }}</p>
        @else
            <p>Periode: Semua Data</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Tanggal</th>
                <th>Keterangan</th>
                <th style="width: 18%;">Debit</th>
                <th style="width: 18%;">Kredit</th>
                <th style="width: 18%;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            {{-- Inisialisasi saldo berjalan --}}
            @php $running_saldo_pdf = 0; @endphp 

            @forelse ($laporan as $item)
                
                {{-- ================================================== --}}
                {{-- PERBAIKAN: Blok ini yang menyebabkan error. --}}
                {{-- Pastikan $debit_pdf dan $kredit_pdf didefinisikan --}}
                {{-- ================================================== --}}
                @php
                    $debit_pdf = $item->debit ?? 0;
                    $kredit_pdf = $item->kredit ?? 0;
                    $running_saldo_pdf += ($debit_pdf - $kredit_pdf);
                @endphp

            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                <td>{{ $item->keterangan }}</td>
                <td class="text-end">
                    {{ $item->debit ? 'Rp ' . number_format($item->debit, 0, ',', '.') : '-' }}
                </td>
                <td class="text-end">
                    {{ $item->kredit ? 'Rp ' . number_format($item->kredit, 0, ',', '.') : '-' }}
                </td>
                <td class="text-end">
                    Rp {{ number_format($running_saldo_pdf, 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada data untuk periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <th colspan="3" class="text-end">TOTAL</th>
                <th class="text-end">Rp {{ number_format($totalDebit, 0, ',', '.') }}</th>
                <th class="text-end">Rp {{ number_format($totalKredit, 0, ',', '.') }}</th>
                <th></th> {{-- Kolom Saldo kosong di baris total --}}
            </tr>
            <tr class="total-row">
                <th colspan="3" class="text-end">SALDO AKHIR</th>
                <th colspan="3" class="text-center">
                    Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
                </th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak pada: {{ $tanggal_cetak }}
    </div>
</body>
</html>

@else

{{-- ================================================== --}}
{{-- BAGIAN 2: INI ADALAH KODE UNTUK WEB (NORMAL) --}}
{{-- ================================================== --}}

@include('partials.session')
@include('partials.main')

    <head>
        @include('partials.title-meta', ['title' => 'Laporan Keuangan'])
        <link href="assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
        @include('partials.head-css')
    </head>

    @include('partials.body')

    <div id="layout-wrapper">
        @include('partials.menu')

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title mb-0">Laporan Keuangan</h4>
                                    
                                    <div>
                                        @php
                                            $query_params = ['dari_tanggal' => $dari_tanggal ?? '', 'sampai_tanggal' => $sampai_tanggal ?? ''];
                                        @endphp

                                        <a href="{{ route('laporan.keuangan.cetak', $query_params) }}" class="btn btn-info btn-sm" target="_blank">
                                            <i class="fas fa-print me-1"></i> Cetak PDF
                                        </a>

                                        <a href="{{ route('laporan.keuangan.pdf', $query_params) }}" class="btn btn-success btn-sm">
                                            <i class="fas fa-file-pdf me-1"></i> Download PDF
                                        </a>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title mb-3">Filter Laporan</h5>
                                    <form action="{{ route('laporan.keuangan') }}" method="GET">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="mb-3">
                                                    <label for="dari_tanggal" class="form-label">Dari Tanggal</label>
                                                    <input type="date" class="form-control" id="dari_tanggal" name="dari_tanggal" value="{{ $dari_tanggal ?? '' }}">
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="mb-3">
                                                    <label for="sampai_tanggal" class="form-label">Sampai Tanggal</label>
                                                    <input type="date" class="form-control" id="sampai_tanggal" name="sampai_tanggal" value="{{ $sampai_tanggal ?? '' }}">
                                                </div>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <div class="mb-3">
                                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <hr>
                                    
                                    <table id="datatable" class="table table-hover table-bordered table-striped" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>Keterangan</th>
                                                <th>Debit</th>
                                                <th>Kredit</th>
                                                <th>Saldo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {{-- Inisialisasi saldo berjalan --}}
                                            @php $running_saldo_web = 0; @endphp 

                                            @forelse ($laporan as $item)

                                                {{-- Pastikan blok ini juga benar --}}
                                                @php
                                                    $debit_web = $item->debit ?? 0;
                                                    $kredit_web = $item->kredit ?? 0;
                                                    $running_saldo_web += ($debit_web - $kredit_web);
                                                @endphp

                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                                <td>{{ $item->keterangan }}</td>
                                                <td class="text-end">
                                                    {{ $item->debit ? 'Rp ' . number_format($item->debit, 0, ',', '.') : '-' }}
                                                </td>
                                                <td class="text-end">
                                                    {{ $item->kredit ? 'Rp ' . number_format($item->kredit, 0, ',', '.') : '-' }}
                                                </td>
                                                <td class="text-end">
                                                    Rp {{ number_format($running_saldo_web, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center">No data available in table</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">TOTAL</th>
                                                <th class="text-end">Rp {{ number_format($totalDebit, 0, ',', '.') }}</th>
                                                <th class="text-end">Rp {{ number_format($totalKredit, 0, ',', '.') }}</th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <th colspan="3" class="text-end">SALDO AKHIR</th>
                                                <th colspan="3" class="text-center {{ $saldoAkhir < 0 ? 'text-danger' : 'text-success' }}">
                                                    Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div> 
                    </div>
                </div>
            </div>
            @include('partials.footer')
        </div>
    </div>

    @include('partials.right-sidebar')
    @include('partials.vendor-scripts')

    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#datatable').DataTable({
                "lengthChange": false,
                "searching": false,
                "scrollX": true
            });
        });
    </script>
    <script src="assets/js/app.js"></script>

    </body>
</html>

@endif
{{-- @if ditutup di baris TERAKHIR --}}