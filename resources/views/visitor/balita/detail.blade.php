@extends('master-home')

@section('content')

<div class="pageheader">

    <h1 class="pagetitle">Data Balita</h1>

    <span class="pagedesc">Data balita posyandu MELATI</span>
    
    <ul class="hornav">
        <li class="current"><a href="#inbox">Detail Data Balita</a></li>

        <li><a href="#compose">Periksa Balita</a></li>
    </ul>

</div><!--pageheader-->

@if($errors->any())
<div id="contentwrapper" class="contentwrapper">
    <div class="notibar announcement">
        <a class="close"></a>
        <h3>Peringatan</h3>

        @foreach($errors->all() as $error)
        <p>
            * {{ $error }}
        </p>
        @endforeach
    </div><!-- notification announcement -->
</div>

@endif

<div id="contentwrapper" class="contentwrapper">
     
    <div id="inbox" class="subcontent">
     
        <div class="msghead">

            <div class="col-md-6">
                
                <h4>Data Detail Balita [ {{ $data_balita->nama_balita }} ]</h4>

                <hr class="garis">

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No. Registrasi</th>
                            <th> : </th>
                            <th> {{ $data_balita->no_reg }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Nama Balita</td>
                            <td> : </td>
                            <td>{{ $data_balita->nama_balita }}</td>
                        </tr>

                        <tr>
                            <td>Tanggal Lahir</td>
                            <td> : </td>
                            <td>{{ $data_balita->tgl_lahir }}</td>
                        </tr>

                        <tr>
                            <td>Jenis Kelamin</td>
                            <td> : </td>
                            <td>{{ $data_balita->jenis_kelamin }}</td>
                        </tr>

                        <tr>
                            <td>Nama Ayah</td>
                            <td> : </td>
                            <td>{{ $data_balita->nama_ayah }}</td>
                        </tr>

                        <tr>
                            <td>Nama Ibu</td>
                            <td> : </td>
                            <td>{{ $data_balita->nama_ibu }}</td>
                        </tr>
                    </tbody>
                </table>

            </div>

            <div class="col-md-6">

                <h4>Riwayat Pemeriksaan [ {{ $data_balita->nama_balita }} ]</h4>

                <hr class="garis">

                @if (empty($score))
                    
                    <h4>Tidak ada riwayat pemeriksaan</h4>

                @else

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal Periksa</th>
                            <th> : </th>
                            <th> {{ $score->periksa->tgl_periksa }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Berat Badan</td>
                            <td> : </td>
                            <td>{{ $score->periksa->berat_badan }} Kg</td>
                        </tr>

                        <tr>
                            <td>Tinggi Badan</td>
                            <td> : </td>
                            <td>{{ $score->periksa->tinggi_badan }} cm</td>
                        </tr>

                        <tr>
                            <td>BB / U</td>
                            <td> : </td>
                            <td>{{ $score->zbbu }} |

                                @if($score->zbbu < -3)

                                    {{ "Gizi Buruk" }}

                                @elseif($score->zbbu >= -3 && $score->zbbu < -2)

                                    {{ "Gizi Kurang" }}

                                @elseif($score->zbbu >= -2 && $score->zbbu <= 2)

                                    {{ "Gizi Baik" }}

                                @elseif($score->zbbu > 2)

                                    {{ "Gizi Lebih" }}

                                @endif

                            </td>
                        </tr>

                        <tr>
                            <td>TB / U</td>
                            <td> : </td>
                            <td>{{ $score->ztbu }} |

                                @if($score->ztbu < -3)

                                    {{ "Sangat Pendek" }}

                                @elseif($score->ztbu >= -3 && $score->ztbu < -2)

                                    {{ "Pendek" }}

                                @elseif($score->ztbu >= -2 && $score->ztbu <= 2)

                                    {{ "Normal" }}

                                @elseif($score->ztbu > 2)

                                    {{ "Tinggi" }}

                                @endif

                            </td>
                        </tr>

                        <tr>
                            <td>BB / TB</td>
                            <td> : </td>
                            <td>{{ $score->zbbtb }} |

                                @if($score->zbbtb < -3)

                                    {{ "Sangat Kurus" }}

                                @elseif($score->zbbtb >= -3 && $score->zbbtb < -2)

                                    {{ "Kurus" }}

                                @elseif($score->zbbtb >= -2 && $score->zbbtb <= 2)

                                    {{ "Normal" }}

                                @elseif($score->zbbtb > 2)

                                    {{ "Gemuk" }}

                                @endif

                            </td>
                        </tr>

                        <tr>
                            <td>Energi yang dibutuhkan</td>
                            <td> : </td>
                            <td>{{ $score->energi }}</td>
                        </tr>

                         <tr>
                            <td>Protein</td>
                            <td> : </td>
                            <td>{{ $score->protein }}</td>
                        </tr>
                    </tbody>
                </table>

                @endif

            </div>

            <div class="col-md-12">

                <hr class="garis">

                <center>
                    <h4>Grafik Pemeriksaan Balita [ {{ $data_balita->nama_balita }} ]</h4>
                </center>

                @if (empty($grafik_score))
                    
                    <h4> Tidak ada riwayat pemeriksaan</h4>

                @else

                    <div id="grafikBalita" style="width:100%; height: 400px;"></div>

                @endif

            </div

            
            <span class="clearall"></span>
        </div><!--msghead-->        
                    
    </div>

    <div id="compose" class="subcontent" style="display: none">
        
        <h3>Form Pemeriksaan Balita [ <b>{{ $data_balita->nama_balita }}</b> ]</h3>

        <hr class="garis">

        {!! Form::open(['route' => 'do_periksa_balita']) !!}

            {!! Form::hidden('id_balita', $data_balita->id) !!}

            <div class="col-md-6">
                
                <div class="form-group">
                    {!! Form::label('tgl_periksa', 'Tanggal Periksa') !!}

                    {!! Form::date('tgl_periksa', \Carbon\Carbon::now(), ['class' => 'form-control']) !!}
                </div>

                <div class="form-group">
                    {!! Form::label('berat_badan', 'Berat Badan') !!}

                    {!! Form::text('berat_badan', null, ['class' => 'form-control']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('tinggi_badan', 'Tinggi Badan') !!}

                    {!! Form::text('tinggi_badan', null, ['class' => 'form-control']) !!}
                </div>

            </div>

            <div class="col-md-12">
                
                <button type="submit" class="btn btn-primary">SIMPAN</button>
            </div>

        {!! Form::close() !!}
    </div>
</div><!--contentwrapper-->

<script>

    var chartData = <?php  echo $grafik_score; ?>

</script>

@endsection