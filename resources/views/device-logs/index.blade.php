@extends('layouts.app')

@section('content')

    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Logs directly from Biometric Device</h4>
            <form action="{{ route('device-logs.sync') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary d-flex align-items-center">
                    <i class="fa fa-sync mr-2"></i> Sync Device Now
                </button>
            </form>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <x-cards.data class="mb-4">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="device-logs-table">
                            <thead class="thead-light">
                                <tr>
                                    <th># ID</th>
                                    <th>Employee</th>
                                    <th>Device UID</th>
                                    <th>Timestamp</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>
                                            @if($log->user)
                                                <div class="media align-items-center">
                                                    <img src="{{ $log->user->image_url }}" class="rounded-circle mr-2" width="30" alt="{{ $log->user->name }}">
                                                    <div class="media-body">
                                                        <h5 class="mb-0 f-13">{{ $log->user->name }}</h5>
                                                        <p class="mb-0 f-12 text-muted">{{ $log->user->email }}</p>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-danger">Unmapped User (ZK ID: {{ $log->device_id }})</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->device_id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($log->timestamp)->format('d M Y - h:i A') }}</td>
                                        <td>
                                            @if($log->type == 1)
                                                <span class="badge badge-success">IN</span>
                                            @elseif($log->type == 2)
                                                <span class="badge badge-danger">OUT</span>
                                            @else
                                                <span class="badge badge-secondary">OTHER ({{ $log->type }})</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No raw logs found. Please sync with the device.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-cards.data>
                <div class="d-flex justify-content-end mt-3">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')
    <script>
        // Any custom script for device log interactions goes here
    </script>
@endpush
