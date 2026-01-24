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

                                {{-- Jenis Antenna --}}
                                <div class="form-group">
                                    <label class="form-label">Jenis Antenna</label>
                                    <input class="form-control"
                                        type="text"
                                        name="jenis_antenna"
                                        value="{{ old('jenis_antenna', $data->jenis_antenna) }}"
                                        required>
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