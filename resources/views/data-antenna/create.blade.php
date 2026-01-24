@extends ('be.master')
@section ('sidebar')
@include ('be.sidebar')
@endsection

@section ('navbar')
@include ('be.navbar')
@endsection

@section ('content')
<main class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <div class="row mb-5">
                            <div class="container">
                                <h2 class="fw-bold">Input Tower</h2>
                            </div>
                            <form action="{{route ('data-antenna.store')}}" method="POST" id="frmdata" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label for="antenna" class="form-label">Jenis Antenna</label>
                                    <select class="form-control" name="jenis_antenna" id="jenis_antenna" aria-label="Default select example">
                                        <option selected>Select Jenis Antenna</option>
                                        <option value="Litebeam M5" @if(old('jenis_antenna')=='LitebeamM5' ) selected @endif>Litebeam M5</option>
                                        <option value="Grid" @if(old('jenis_antenna')=='Grid' ) selected @endif>Grid</option>
                                        <option value="Microwave" @if(old('jenis_antenna')=='Microwave' ) selected @endif>Microwave</option>
                                        <option value="Sectoral" @if(old('jenis_antenna')=='Sectoral' ) selected @endif>Sectoral</option>
                                    </select>
                                    <div id="dataantennaHelp" class="form-text" style="color: #2f271d"> Text must be filled in maximal 100 characters</div>
                                </div>

                                <div class="form-group">
                                    <label for="tower" class="form-label">Dari Tower</label>
                                    <select name="id_nama_tower" type="text" id="id_nama_tower" class="search form-control">
                                        <option value="">Dari Tower</option>
                                        @foreach ($data_tower as $tower)
                                        <option
                                            value="{{ $tower->id }}">
                                            {{ $tower->nama_tower }} ({{ $tower->alamat_tower }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="tower" class="form-label">Link ke Tower</label>
                                    <select name="link_tower" type="text" id="link_tower" class="search form-control">
                                        <option value="">Link ke Tower</option>
                                        @foreach ($data_tower as $tower)
                                        <option
                                            value="{{ $tower->id }}">
                                            {{ $tower->nama_tower }} ({{ $tower->alamat_tower }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>



                                <div class="text-end">
                                    <a href="{{route('data-antenna.index')}}" class="btn btn-secondary">
                                        <i class="far fa-window-close me-2"></i>Cancel</a>
                                    <button type="submit" class="btn btn-save" id="save"><i class="far fa-save me-2"></i>Save Data Tower</button>
                                </div>

                            </form>
                        </div>
                    </div>

                    <!-- <script>
                        document.getElementById('id_nama_tower').addEventListener('change', function() {

                            let selected = this.options[this.selectedIndex];

                            document.getElementById('detail_lokasi').value = selected.dataset.detail ?? '';
                            document.getElementById('latitude').value = selected.dataset.lat ?? '';
                            document.getElementById('longitude').value = selected.dataset.lng ?? '';

                        });
                    </script> -->



                    @if(session('error'))
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: '{{ session("error") }}',
                                confirmButtonColor: '#d33'
                            });
                        });
                    </script>
                    @endif

                    @if(session('success'))
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: '{{ session("success") }}',
                                confirmButtonColor: 'green'
                            });
                        });
                    </script>
                    @endif

                    @endsection