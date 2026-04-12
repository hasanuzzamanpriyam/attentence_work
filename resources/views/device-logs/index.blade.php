@extends('layouts.app')

@section('content')

    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Logs directly from Biometric Device</h4>
            <div>
                <p class="mb-0 text-muted small">Showing data for current month ({{ \Carbon\Carbon::now()->format('F Y') }}). Total working days: {{ $totalWorkingDays }}</p>
                <form action="{{ route('device-logs.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary d-flex align-items-center">
                        <i class="fa fa-sync mr-2"></i> Sync Device Now
                    </button>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @forelse ($processedData as $userData)
                    <x-cards.data class="mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <div class="media align-items-center">
                                    <img src="{{ $userData['user']->image_url }}" class="rounded-circle mr-2" width="40" alt="{{ $userData['user']->name }}">
                                    <div class="media-body">
                                        <h5 class="mb-0">{{ $userData['user']->name }}</h5>
                                        <p class="mb-0 text-muted">{{ $userData['user']->email }}</p>
                                    </div>
                                </div>
                            </h5>
                            <div class="card-header-actions">
                                <span class="badge badge-primary">Worked Days: {{ $userData['total_worked_days'] }}</span>
                                <span class="badge badge-info">Total Hours: {{ $userData['total_duration_hours'] }}</span>
                                <span class="badge badge-warning">Absent Days: {{ $userData['absent_days'] }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>First Time</th>
                                            <th>Last Time</th>
                                            <th>Duration (hours)</th>
                                            <th>All Placements</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($userData['daily_logs'] as $day)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</td>
                                                <td>{{ $day['first_time'] }}</td>
                                                <td>{{ $day['last_time'] }}</td>
                                                <td>{{ round($day['duration_minutes'] / 60, 2) }}</td>
                                                <td>
                                                    @foreach ($day['all_times'] as $time)
                                                        <span class="badge badge-secondary mr-1">{{ $time }}</span>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4">No logs for this user.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </x-cards.data>
                @empty
                    <div class="text-center py-4">No device logs found. Please sync with the device.</div>
                @endforelse
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
