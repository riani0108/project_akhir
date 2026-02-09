@extends('be.master')
@section('sidebar')
@include('be.sidebar')
@endsection

@section('navbar')
@include('be.navbar')
@endsection

@section('content')
<main class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <div class="row mb-5">
                            <div class="container">
                                <h2 class="fw-bold">Edit Data Antenna</h2>
                            </div>

                            <form action="{{ route('data-antenna.update', $data->id) }}" method="POST" id="frmdata">
                                @csrf
                                @method('PUT')

                                @php
                                $selectedAntenna = old('jenis_antenna', $data->jenis_antenna);
                                @endphp

                                {{-- Jenis Antenna --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Jenis Antenna</label>
                                    <select class="form-control" name="jenis_antenna" id="jenis_antenna" required>
                                        <option value="{{ old('jenis_antenna', $data->id) }}">Pilih Jenis Antenna</option>
                                        <option value=" Grid 24 dBi 2.4 GHz"
                                            data-frekuensi="2.4"
                                            data-gain="24"
                                            data-polaritas="Horizontal atau Vertical"
                                            {{ $selectedAntenna == 'Grid 24 dBi 2.4 GHz' ? 'selected' : '' }}>
                                            Grid 24 dBi (2.4 GHz)
                                        </option>

                                        <option value="Yagi 15 dBi 2.4 GHz"
                                            data-frekuensi="2.4"
                                            data-gain="15"
                                            data-polaritas="Horizontal atau Vertical"
                                            {{ $selectedAntenna == 'Yagi 15 dBi 2.4 GHz' ? 'selected' : '' }}>
                                            Yagi 15 dBi (2.4 GHz)
                                        </option>

                                        <option value="Grid 24 dBi 5.8 GHz"
                                            data-frekuensi="5.8"
                                            data-gain="24"
                                            data-polaritas="Horizontal atau Vertical"
                                            {{ $selectedAntenna == 'Grid 24 dBi 5.8 GHz' ? 'selected' : '' }}>
                                            Grid 24 dBi (5.8 GHz)
                                        </option>

                                        <option value="LiteBeam M5-23"
                                            data-frekuensi="5"
                                            data-gain="23"
                                            data-polaritas="Vertical"
                                            {{ $selectedAntenna == 'LiteBeam M5-23' ? 'selected' : '' }}>
                                            Ubiquiti LiteBeam M5-23 (5 GHz)
                                        </option>

                                        <option value="NanoBeam 5AC 19 dBi"
                                            data-frekuensi="5"
                                            data-gain="19"
                                            data-polaritas="Dual (Slant ±45°)"
                                            {{ $selectedAntenna == 'NanoBeam 5AC 19 dBi' ? 'selected' : '' }}>
                                            Ubiquiti NanoBeam 5AC Gen2 19 dBi
                                        </option>

                                        <option value="PowerBeam 5AC 25 dBi"
                                            data-frekuensi="5"
                                            data-gain="25"
                                            data-polaritas="Dual (Slant ±45°)"
                                            {{ $selectedAntenna == 'PowerBeam 5AC 25 dBi' ? 'selected' : '' }}>
                                            Ubiquiti PowerBeam 5AC 25 dBi
                                        </option>
                                    </select>
                                </div>

                                {{-- Auto Info --}}
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Frekuensi (GHz)</label>
                                        <input type="text" id="frekuensi" class="form-control" readonly>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Gain (dBi)</label>
                                        <input type="text" id="gain" class="form-control" readonly>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Polaritas</label>
                                        <input type="text" id="polaritas" class="form-control" readonly>
                                    </div>
                                </div>



                                {{-- Pilih Tower --}}
                                <div class="form-group mt-3">
                                    <label class="form-label">Dari Tower</label>
                                    <select name="id_nama_tower" id="id_nama_tower" class="form-control" required>
                                        <option value="">Dari Tower</option>
                                        @foreach ($data_tower as $tower)
                                        <option
                                            value="{{ old('id_nama_tower', $tower->id) }}"
                                            {{ $tower->id == $data->id_nama_tower ? 'selected' : '' }}>
                                            {{ $tower->nama_tower }} ({{ $tower->alamat_tower }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group mt-3">
                                    <label class="form-label">Link ke Tower</label>
                                    <select name="link_tower" id="link_tower" class="form-control" required>
                                        <option value="">Link ke Tower</option>
                                        @foreach ($data_tower as $tower)
                                        <option
                                            value="{{ old('id_nama_tower', $tower->id) }}"
                                            {{ $tower->id == $data->id_nama_tower ? 'selected' : '' }}>
                                            {{ $tower->nama_tower }} ({{ $tower->alamat_tower }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>


                                {{-- Button --}}
                                <div class="text-end mt-4">
                                    <a href="{{ route('data-antenna.index') }}" class="btn btn-secondary">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-save">
                                        Save Data Antenna
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>


                    {{-- Script auto-fill tower --}}
                    <!-- <script>
                        document.getElementById('id_nama_tower').addEventListener('change', function() {
                            let selected = this.options[this.selectedIndex];

                            document.getElementById('detail_lokasi').value = selected.dataset.detail ?? '';
                            document.getElementById('latitude').value = selected.dataset.lat ?? '';
                            document.getElementById('longitude').value = selected.dataset.lng ?? '';
                        });
                    </script> -->


                    {{-- SCRIPT AUTO LOAD --}}
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const select = document.getElementById('jenis_antenna');

                            function updateInfo() {
                                const option = select.options[select.selectedIndex];
                                if (!option) return;

                                document.getElementById('frekuensi').value = option.dataset.frekuensi || '';
                                document.getElementById('gain').value = option.dataset.gain || '';
                                document.getElementById('polaritas').value = option.dataset.polaritas || '';
                            }

                            select.addEventListener('change', updateInfo);
                            updateInfo(); // 🔥 load data lama
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