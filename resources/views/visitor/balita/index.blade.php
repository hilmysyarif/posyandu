@extends('master-home')

@section('content')

<div class="pageheader">

    <h1 class="pagetitle">Data Balita</h1>

    <span class="pagedesc">Data balita posyandu MELATI</span>
    
    <ul class="hornav">
        <li class="current"><a href="#inbox">Data Semua Balita</a></li>

        <li><a href="#compose">Tambah Data Balita</a></li>
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

        	<table class="table table-striped" id="myTable">

        		<thead>
        			<tr>
        				<th>No</th>
        				<th>No. Registrasi</th>
                        <th>Nama Balita</th>
        				<th>Tgl Lahir</th>
        				<th>Jenis Kelamin</th>
        				<th>Nama Ayah</th>
        				<th>Nama Ibu</th>
        				<th>Pilihan</th>
        			</tr>
        		</thead>
        		<tbody>
        			
                    <?php $no = 1; ?>
                    @foreach($data_balita as $item)

                        <tr>
                            <td>{{$no}}</td>
                            <td>{{ $item->no_reg }}</td>
                            <td>{{ $item->nama_balita }}</td>
                            <td>{{ $item->tgl_lahir }}</td>
                            <td>{{ $item->jenis_kelamin }}</td>
                            <td>{{ $item->nama_ayah }}</td>
                            <td>{{ $item->nama_ibu }}</td>
                            <td>
                                <span class="label label-primary"> <a href="{{ route('detail_balita', $item->id ) }}" title="">Detail</a> </span>
                                &nbsp;
                                <span class="label label-success"> <a href="{{ route('ubah_balita', $item->id) }}" title="">Ubah</a> </span>
                                &nbsp;
                                <span class="label label-danger"> <a href="{{ route('destroy_balita', $item->id ) }}" title="">Hapus</a> </span>
                            </td>
                        </tr>

                        <?php $no++; ?>

                    @endforeach
        		</tbody>
        	</table>

            {{-- <ul class="msghead_menu">
            	<li class="right"><a class="next"></a></li>
                <li class="right"><a class="prev prev_disabled"></a></li>
                <li class="right"><span class="pageinfo">1-10 of 2,139</span></li>
            </ul> --}}
            <span class="clearall"></span>
        </div><!--msghead-->
        
                    
    </div>

    <div id="compose" class="subcontent" style="display: none">
     	
     	<h3>Form Tambah Data Balita</h3>

     	<hr>

     	{!! Form::open(['route' => 'do_tambah']) !!}

     		<div class="col-md-6">
     			
     			<div class="form-group">
     				{!! Form::label('no_reg', 'No. Registrasi') !!}

     				{!! Form::text('no_reg', null, ['class' => 'form-control']) !!}
     			</div>

     			<div class="form-group">
     				{!! Form::label('nama_balita', 'Nama Balita') !!}

     				{!! Form::text('nama_balita', null, ['class' => 'form-control']) !!}
     			</div>
     			<div class="form-group">
     				{!! Form::label('tgl_lahir', 'Tanggal Lahir') !!}

                    {!! Form::date('tgl_lahir', \Carbon\Carbon::now(), ['class' => 'form-control']) !!}
     			</div>

     		</div>

     		<div class="col-md-6">

     			<div class="form-group">
     				{!! Form::label('jenis_kelamin', 'Jenis Kelamin') !!} <br>

     				{!! Form::radio('jenis_kelamin', 'L', ['class' => 'form-control']) !!} Laki-laki
     				&nbsp; &nbsp;&nbsp; 
     				{!! Form::radio('jenis_kelamin', 'P', ['class' => 'form-control']) !!} Perempuan
     			</div>
     			 <br>

     			<div class="form-group">
     				{!! Form::label('nama_ayah', 'Nama Ayah') !!}

     				{!! Form::text('nama_ayah', null, ['class' => 'form-control']) !!}
     			</div>

     			<div class="form-group">
     				{!! Form::label('nama_ibu', 'Nama Ibu') !!}

     				{!! Form::text('nama_ibu', null, ['class' => 'form-control']) !!}
     			</div>

     		</div>

     		<div class="col-md-12">
     			
     			<button type="submit" class="btn btn-primary">SIMPAN</button>   
     		</div>

     	{!! Form::close() !!}
    </div>
</div><!--contentwrapper-->

@endsection