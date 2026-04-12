@extends('layouts.app')

@section('content')

    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Logs directly from Biometric Device</h4>
            <div class="text-right">
                <p class="mb-0 text-muted small">Showing data for current month ({{ \Carbon\Carbon::now()->format('F Y') }}). Total working days: {{ $totalWorkingDays }}</p>
                <!-- Device Status Indicator -->
                <div id="deviceStatus" class="mt-2">
                    <span class="badge badge-secondary">
                        <i class="fa fa-spinner fa-spin"></i> Checking device status...
                    </span>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <form action="{{ route('device-logs.sync') }}" method="POST" id="syncForm">
                @csrf
                <button type="submit" class="btn btn-primary d-flex align-items-center" id="syncBtn">
                    <i class="fa fa-sync mr-2"></i> Sync Device Now
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle mr-2"></i>{{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle mr-2"></i>{{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle mr-2"></i>{{ $errors->first() }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="row">
            <div class="col-sm-12">
                @forelse ($processedData as $userData)
                    <x-cards.data class="mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <div class="media align-items-center">
                                    <img src="{{ $userData['user']->image_url ?: asset('img/default-avatar.png') }}" class="rounded-circle mr-2" width="40" alt="{{ $userData['user']->name }}">
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

                @if(count($unmappedLogs) > 0)
                    <div class="mt-4">
                        <h5 class="text-warning mb-3">
                            <i class="fa fa-exclamation-triangle"></i> Unmapped Device Logs
                        </h5>
                        <p class="text-muted small">These logs are from the device but the users are not registered in the system. Please map the device user IDs to system users.</p>

                        @foreach ($unmappedLogs as $deviceUserId => $logs)
                            <x-cards.data class="mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0 text-warning">
                                        Device User ID: {{ $deviceUserId }}
                                    </h5>
                                    <div class="card-header-actions">
                                        <span class="badge badge-warning">{{ $logs->count() }} log entries</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($logs->sortBy('timestamp') as $log)
                                                    <tr>
                                                        <td>{{ $log->timestamp->format('d M Y') }}</td>
                                                        <td>{{ $log->timestamp->format('H:i:s') }}</td>
                                                        <td>
                                                            <span class="badge badge-{{ $log->type == 1 ? 'success' : 'danger' }}">
                                                                {{ $log->type == 1 ? 'Check In' : 'Check Out' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </x-cards.data>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Check device status on page load
            checkDeviceStatus();

            // Handle sync form submission
            $('#syncForm').on('submit', function(e) {
                e.preventDefault();

                var btn = $('#syncBtn');
                var originalText = btn.html();

                // Disable button and show loading state
                btn.prop('disabled', true);
                btn.html('<i class="fa fa-spinner fa-spin mr-2"></i> Syncing...');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.status === 'success') {
                            showNotification('success', response.message);
                            // Refresh device status after sync
                            setTimeout(() => checkDeviceStatus(), 2000);
                        } else {
                            showNotification('error', response.message);
                        }
                    },
                    error: function(xhr) {
                        var message = 'An error occurred during sync.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        showNotification('error', message);
                    },
                    complete: function() {
                        // Re-enable button and restore original text
                        btn.prop('disabled', false);
                        btn.html(originalText);
                    }
                });
            });

            // Function to check device status
            function checkDeviceStatus() {
                const statusDiv = $('#deviceStatus');
                statusDiv.html('<span class="badge badge-secondary"><i class="fa fa-spinner fa-spin"></i> Checking device status...</span>');

                $.ajax({
                    url: '{{ route("device-logs.status") }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.connected) {
                            statusDiv.html(
                                '<span class="badge badge-success" title="' + response.message + '">' +
                                '<i class="fa fa-check-circle"></i> Device Connected' +
                                '</span>'
                            );
                        } else {
                            statusDiv.html(
                                '<span class="badge badge-danger" title="' + response.message + '">' +
                                '<i class="fa fa-times-circle"></i> Device Disconnected' +
                                '</span>' +
                                '<small class="text-muted d-block mt-1">' + response.ip + ':' + response.port + '</small>'
                            );
                        }
                    },
                    error: function(xhr) {
                        statusDiv.html(
                            '<span class="badge badge-warning" title="Unable to check device status">' +
                            '<i class="fa fa-exclamation-triangle"></i> Status Unknown' +
                            '</span>'
                        );
                        console.error('Failed to check device status:', xhr);
                    }
                });
            }

            // Function to show notifications
            function showNotification(type, message) {
                var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

                var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                    '<i class="fa ' + icon + ' mr-2"></i>' + message +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span>' +
                    '</button>' +
                    '</div>';

                // Remove existing alerts
                $('.alert').not(this).remove();

                // Add new alert at the top of content
                $('.content-wrapper').prepend(alertHtml);

                // Auto-dismiss after 10 seconds
                setTimeout(function() {
                    $('.alert').first().alert('close');
                }, 10000);
            }
        });
    </script>
@endpush
