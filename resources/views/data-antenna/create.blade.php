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
                                <div class="form-group mb-3">
                                    <label class="form-label">Jenis Antenna</label>
                                    <select class="form-control" name="jenis_antenna" id="jenis_antenna">
                                        <option value="">Pilih Jenis Antenna</option>

                                        <!-- 2.4 GHz -->
                                        <option value="Grid 24 dBi 2.4 GHz"
                                            data-frekuensi="2.4"
                                            data-gain="24"
                                            data-vswr="1.5"
                                            data-polaritas="Horizontal atau Vertical">
                                            Grid 24 dBi (2.4 GHz)
                                        </option>

                                        <option value="Yagi 15 dBi 2.4 GHz"
                                            data-frekuensi="2.4"
                                            data-gain="15"
                                            data-vswr="1.5"
                                            data-polaritas="Horizontal atau Vertical">
                                            Yagi 15 dBi (2.4 GHz)
                                        </option>

                                        <!-- 5 GHz / 5.8 GHz -->
                                        <option value="Grid 24 dBi 5.8 GHz"
                                            data-frekuensi="5.8"
                                            data-gain="24"
                                            data-vswr="1.4"
                                            data-polaritas="Horizontal atau Vertical">
                                            Grid 24 dBi (5.8 GHz)
                                        </option>

                                        <option value="LiteBeam M5-23"
                                            data-frekuensi="5"
                                            data-gain="23"
                                            data-vswr="1.5"
                                            data-polaritas="Vertical">
                                            Ubiquiti LiteBeam M5-23 (5 GHz)
                                        </option>

                                        <option value="NanoBeam 5AC 19 dBi"
                                            data-frekuensi="5"
                                            data-gain="19"
                                            data-vswr="1.5"
                                            data-polaritas="Dual (Slant ±45°)">
                                            Ubiquiti NanoBeam 5AC Gen2 19 dBi
                                        </option>

                                        <option value="PowerBeam 5AC 25 dBi"
                                            data-frekuensi="5"
                                            data-gain="25"
                                            data-vswr="1.5"
                                            data-polaritas="Dual (Slant ±45°)">
                                            Ubiquiti PowerBeam 5AC 25 dBi
                                        </option>

                                        <!-- Sector / Omni -->
                                        <option value="Sector 90° 16 dBi 5 GHz"
                                            data-frekuensi="5"
                                            data-gain="16"
                                            data-vswr="1.8"
                                            data-polaritas="Dual (Slant ±45°)">
                                            Sector 90° 16 dBi (5 GHz)
                                        </option>

                                        <option value="Omni 12 dBi 5 GHz"
                                            data-frekuensi="5"
                                            data-gain="12"
                                            data-vswr="1.8"
                                            data-polaritas="Vertical">
                                            Omni 12 dBi (5 GHz)
                                        </option>

                                        <!-- Microwave / Higher Frequency -->
                                        <option value="Dish 30 dBi 18 GHz"
                                            data-frekuensi="18"
                                            data-gain="30"
                                            data-vswr="1.3"
                                            data-polaritas="Vertical atau Horizontal">
                                            Parabolic Dish 30 dBi (18 GHz Microwave)
                                        </option>

                                        <option value="Dish 34 dBi 23 GHz"
                                            data-frekuensi="23"
                                            data-gain="34"
                                            data-vswr="1.3"
                                            data-polaritas="Vertical atau Horizontal">
                                            Parabolic Dish 34 dBi (23 GHz Microwave)
                                        </option>

                                        <!-- Tambahan populer lainnya -->
                                        <option value="RocketDish 30 dBi 5 GHz"
                                            data-frekuensi="5"
                                            data-gain="30"
                                            data-vswr="1.4"
                                            data-polaritas="Dual (Slant ±45°)">
                                            Ubiquiti RocketDish RD-5G30 30 dBi
                                        </option>

                                        <option value="Grid 27 dBi 5.8 GHz"
                                            data-frekuensi="5.8"
                                            data-gain="27"
                                            data-vswr="1.5"
                                            data-polaritas="Horizontal">
                                            Grid 27 dBi High Performance (5.8 GHz)
                                        </option>

                                        <option value="Panel 19 dBi 5 GHz"
                                            data-frekuensi="5"
                                            data-gain="19"
                                            data-vswr="1.6"
                                            data-polaritas="Dual">
                                            Panel 19 dBi (5 GHz)
                                        </option>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Frekuensi (GHz)</label>
                                        <input type="text" name="frekuensi" id="frekuensi" class="form-control" readonly>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Gain (dBi)</label>
                                        <input type="text" name="gain" id="gain" class="form-control" readonly>
                                    </div>


                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Polaritas</label>
                                        <input type="text" name="polaritas" id="polaritas" class="form-control" readonly>
                                    </div>
                                </div>



                                <div class="form-group">
                                    <label class="form-label">Dari Tower</label>
                                    <select name="id_nama_tower" type="text" id="id_nama_tower" class="form-control" required>
                                        <option value="">Pilih Tower</option>
                                        @foreach ($data_tower as $tower)
                                        <option value="{{ $tower->id }}">
                                            {{ $tower->nama_tower }} ({{ $tower->alamat_tower }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Link ke Tower</label>
                                    <select name="link_tower" type="text" id="link_tower" class="form-control" required>
                                        <option value="">Pilih Tower</option>
                                        @foreach ($data_tower as $tower)
                                        <option value="{{ $tower->id }}">
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

                    <script>
                        document.getElementById('frmdata').addEventListener('submit', function(e) {
                            if (!document.getElementById('jenis_antenna').value) {
                                e.preventDefault();
                                alert('Pilih jenis antenna terlebih dahulu!');
                            }
                        });

                        document.getElementById('jenis_antenna').addEventListener('change', function() {
                            let selected = this.options[this.selectedIndex];

                            document.getElementById('frekuensi').value = selected.dataset.frekuensi || '';
                            document.getElementById('gain').value = selected.dataset.gain || '';
                            document.getElementById('polaritas').value = selected.dataset.polaritas || '';
                        });
                    </script>




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