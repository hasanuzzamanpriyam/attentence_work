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

        <!-- LATEST ACTIVITY PANEL -->
        <div id="latestActivityPanel" class="mb-4" style="display: none;">
            <div class="card border-primary shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-fingerprint mr-2"></i> Latest Activity - Real-Time
                    </h5>
                    <span id="lastUpdateTime" class="small text-light">Last updated: --</span>
                </div>
                <div class="card-body">
                    <div id="activityContent" class="text-center text-muted">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Waiting for activity...</p>
                    </div>
                </div>
            </div>
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
                    <x-cards.data class="mb-4" data-user-id="{{ $userData['user']->id }}">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <div class="media align-items-center">
                                    <img src="{{ $userData['user']->image_url ?: asset('img/default-avatar.png') }}" class="rounded-circle mr-2" width="40" alt="{{ $userData['user']->name }}">
                                    <div class="media-body">
                                        <h5 class="mb-0">
                                            {{ $userData['user']->name }}
                                            <small class="text-muted ml-2">(ID: {{ $userData['user']->id }}{{ $userData['user']->device_user_id ? ' | Device ID: ' . $userData['user']->device_user_id : '' }})</small>
                                        </h5>
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
                                            <th>Clock-In</th>
                                            <th>Clock-Out</th>
                                            <th>Duration (hours)</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($userData['daily_logs'] as $day)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</td>
                                                <td>
                                                    <span class="text-success font-weight-bold">
                                                        <i class="fa fa-sign-in mr-1"></i>{{ $day['clock_in'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($day['is_completed'])
                                                        <span class="text-danger font-weight-bold">
                                                            <i class="fa fa-sign-out mr-1"></i>{{ $day['clock_out'] }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">
                                                            <i class="fa fa-hourglass-start mr-1"></i>{{ $day['clock_out'] }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($day['is_completed'])
                                                        <span class="badge badge-info">{{ $day['duration_hours'] }}</span>
                                                    @else
                                                        <span class="badge badge-secondary">--</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($day['is_completed'])
                                                        <span class="badge badge-success">
                                                            <i class="fa fa-check-circle mr-1"></i>{{ $day['status'] }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-warning">
                                                            <i class="fa fa-spinner fa-spin mr-1"></i>{{ $day['status'] }}
                                                        </span>
                                                    @endif
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
                                                            @if($log->type == 1)
                                                                <span class="badge badge-success">
                                                                    <i class="fa fa-sign-in mr-1"></i>Check In
                                                                </span>
                                                            @elseif($log->type == 2)
                                                                <span class="badge badge-danger">
                                                                    <i class="fa fa-sign-out mr-1"></i>Check Out
                                                                </span>
                                                            @else
                                                                <span class="badge badge-secondary">
                                                                    <i class="fa fa-question-circle mr-1"></i>Unknown (Type: {{ $log->type }})
                                                                </span>
                                                            @endif
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

                performSync(function(response) {
                    if (response.status === 'success') {
                        showNotification('success', response.message);
                        // Refresh device status after sync
                        setTimeout(() => checkDeviceStatus(), 2000);
                    } else {
                        showNotification('error', response.message);
                    }
                }, function(xhr) {
                    var message = 'An error occurred during sync.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    showNotification('error', message);
                }, function() {
                    // Re-enable button and restore original text
                    btn.prop('disabled', false);
                    btn.html(originalText);
                });
            });

            // Auto-sync every 30 seconds
            setInterval(function() {
                performAutoSync();
            }, 30 * 1000); // 30 seconds

            // Function to perform sync
            function performSync(successCallback, errorCallback, completeCallback) {
                $.ajax({
                    url: '{{ route("device-logs.sync") }}',
                    type: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: successCallback,
                    error: errorCallback,
                    complete: completeCallback
                });
            }

            // Function to perform auto-sync (silent)
            function performAutoSync() {
                performSync(function(response) {
                    if (response.status === 'success') {
                        console.log('Auto-sync successful:', response.message);
                        // Refresh device status after auto-sync
                        setTimeout(() => checkDeviceStatus(), 2000);
                    }
                }, function(xhr) {
                    console.error('Auto-sync failed:', xhr);
                }, function() {
                    // No UI changes for auto-sync
                });
            }

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

            // ========================================
            // REAL-TIME LATEST ACTIVITY TRACKING
            // ========================================
            
            let latestActivityInterval = null;
            let lastPunchId = null;
            let lastHighlightedUserId = null;

            // Start polling for latest activity
            startLatestActivityPolling();

            function startLatestActivityPolling() {
                // Fetch immediately
                fetchLatestPunch();

                // Then poll every 10 seconds
                latestActivityInterval = setInterval(fetchLatestPunch, 10000);
            }

            function fetchLatestPunch() {
                $.ajax({
                    url: '{{ route("device-logs.latest") }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            const data = response.data;
                            
                            // Check if this is a new punch
                            const isNewPunch = (data.id !== lastPunchId);
                            
                            if (isNewPunch) {
                                lastPunchId = data.id;
                                updateLatestActivityDisplay(data);
                                
                                // If user is mapped, highlight their card
                                if (data.user && data.user_id) {
                                    highlightUserCard(data.user_id);
                                }
                                
                                // Show notification for new punch
                                if (lastPunchId !== null) { // Not first load
                                    playSoftBeep();
                                }
                            }
                            
                            // Update timestamp
                            updateLastUpdateTime();
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to fetch latest punch:', xhr);
                        $('#activityContent').html(
                            '<i class="fa fa-exclamation-triangle text-warning fa-2x"></i>' +
                            '<p class="mt-2 text-warning">Error fetching activity</p>'
                        );
                    }
                });
            }

            function updateLatestActivityDisplay(data) {
                const panel = $('#latestActivityPanel');
                const content = $('#activityContent');
                
                // Show panel if hidden
                panel.slideDown(300);
                
                // Build display HTML
                let html = '<div class="row align-items-center">';
                
                // User info section
                if (data.user) {
                    html += '<div class="col-md-4 text-center mb-3 mb-md-0">';
                    html += '<div class="media align-items-center justify-center">';
                    html += '<img src="{{ asset("img/default-avatar.png") }}" class="rounded-circle mr-3" width="60" alt="' + data.user.name + '">';
                    html += '<div class="media-body text-left">';
                    html += '<h5 class="mb-1">' + data.user.name + '</h5>';
                    html += '<p class="text-muted mb-0 small">Username: ' + data.user.name + ' | ID: ' + data.user.id + '</p>';
                    html += '<p class="text-muted mb-0 small">' + data.user.email + '</p>';
                    if (data.device_id) {
                        html += '<p class="text-muted mb-0 small">Device ID: ' + data.device_id + '</p>';
                    }
                    html += '</div></div></div>';
                } else {
                    html += '<div class="col-md-4 text-center mb-3 mb-md-0">';
                    html += '<div class="alert alert-warning mb-0">';
                    html += '<i class="fa fa-user-slash mr-2"></i>Unmapped Device User';
                    html += '<p class="mb-0 small mt-1">Device UID: ' + (data.device_id || 'N/A') + '</p>';
                    html += '</div></div>';
                }
                
                // Punch details
                html += '<div class="col-md-4 text-center mb-3 mb-md-0">';
                html += '<div class="p-3 bg-light rounded">';
                html += '<i class="fa fa-' + (data.type == 1 ? 'sign-in' : 'sign-out') + ' fa-3x text-' + (data.type == 1 ? 'success' : 'danger') + ' mb-2"></i>';
                html += '<h4 class="mb-1">' + data.type_label + '</h4>';
                html += '<p class="text-muted mb-0">Punch Type</p>';
                html += '</div></div>';
                
                // Date and Time
                html += '<div class="col-md-4 text-center mb-3 mb-md-0">';
                html += '<div class="p-3 bg-light rounded">';
                html += '<i class="fa fa-calendar fa-2x text-primary mb-2"></i>';
                html += '<h5 class="mb-1">' + data.date + '</h5>';
                html += '<h3 class="text-primary mb-0">' + data.time + '</h3>';
                html += '</div></div>';
                
                html += '</div>'; // Close row
                
                // Add view details button if user is mapped
                if (data.user && data.user_id) {
                    html += '<div class="text-center mt-3">';
                    html += '<button class="btn btn-sm btn-outline-primary" onclick="viewUserDetails(' + data.user_id + ')">';
                    html += '<i class="fa fa-eye mr-1"></i> View Full Details';
                    html += '</button></div>';
                }
                
                content.html(html);
                
                // Add animation class
                content.addClass('animate__animated animate__fadeIn');
                setTimeout(() => content.removeClass('animate__animated animate__fadeIn'), 1000);
            }

            function highlightUserCard(userId) {
                // Remove previous highlight
                if (lastHighlightedUserId) {
                    const prevCard = $(`[data-user-id="${lastHighlightedUserId}"]`).closest('[class*="card"]');
                    if (prevCard.length) {
                        prevCard.css({
                            'border-color': '',
                            'box-shadow': '',
                            'background-color': ''
                        });
                    }
                }
                
                // Highlight current user card
                const userCard = $(`[data-user-id="${userId}"]`).closest('[class*="card"]');
                if (userCard.length) {
                    userCard.css({
                        'border-color': '#007bff',
                        'box-shadow': '0 0 20px rgba(0, 123, 255, 0.3)',
                        'background-color': '#f0f8ff'
                    });
                    
                    // Scroll to user card
                    $('html, body').animate({
                        scrollTop: userCard.offset().top - 100
                    }, 500);
                    
                    lastHighlightedUserId = userId;
                    
                    // Remove highlight after 5 seconds
                    setTimeout(() => {
                        userCard.css({
                            'border-color': '',
                            'box-shadow': '',
                            'background-color': ''
                        });
                        lastHighlightedUserId = null;
                    }, 5000);
                }
            }

            function updateLastUpdateTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString();
                $('#lastUpdateTime').text('Last updated: ' + timeString);
            }

            function viewUserDetails(userId) {
                // Fetch and display user detail in a modal
                const modalHtml = `
                    <div class="modal fade" id="userDetailModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title"><i class="fa fa-user mr-2"></i>User Punch Details</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body" id="userDetailContent">
                                    <div class="text-center text-muted">
                                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                                        <p class="mt-2">Loading details...</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove existing modal if any
                $('#userDetailModal').remove();
                
                // Add modal to body
                $('body').append(modalHtml);
                
                // Show modal
                $('#userDetailModal').modal('show');
                
                // Fetch user details
                $.ajax({
                    url: '{{ route("device-logs.user-detail", ":id") }}'.replace(':id', userId),
                    type: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            const data = response.data;
                            let html = '';
                            
                            // User info
                            html += '<div class="media align-items-center mb-4 p-3 bg-light rounded">';
                            html += '<img src="{{ asset("img/default-avatar.png") }}" class="rounded-circle mr-3" width="60" alt="' + data.user.name + '">';
                            html += '<div class="media-body">';
                            html += '<h4 class="mb-1">' + data.user.name + '</h4>';
                            html += '<p class="text-muted mb-0">' + data.user.email + '</p>';
                            html += '<p class="text-muted small mb-0">Date: ' + data.date + '</p>';
                            html += '</div></div>';
                            
                            // Punch timeline
                            if (data.punches && data.punches.length > 0) {
                                html += '<h6 class="mb-3"><i class="fa fa-clock-o mr-2"></i>Today\'s Punch Timeline</h6>';
                                html += '<div class="timeline">';
                                
                                data.punches.forEach(function(punch) {
                                    html += '<div class="d-flex align-items-center mb-3 p-2 border-bottom">';
                                    html += '<div class="mr-3">';
                                    html += '<i class="fa fa-' + (punch.type == 1 ? 'sign-in text-success' : 'sign-out text-danger') + ' fa-2x"></i>';
                                    html += '</div>';
                                    html += '<div class="flex-grow-1">';
                                    html += '<h6 class="mb-0">' + punch.type_label + '</h6>';
                                    html += '<p class="text-muted small mb-0">' + punch.timestamp + '</p>';
                                    if (punch.device_id) {
                                        html += '<p class="text-muted small mb-0">Device: ' + punch.device_id + '</p>';
                                    }
                                    html += '</div></div>';
                                });
                                
                                html += '</div>';
                                
                                // Summary
                                if (data.first_punch && data.last_punch) {
                                    html += '<div class="row mt-3">';
                                    html += '<div class="col-6">';
                                    html += '<div class="p-3 bg-success-light rounded text-center">';
                                    html += '<small class="text-muted">First Punch</small>';
                                    html += '<h5 class="mb-0 text-success">' + data.first_punch.time + '</h5>';
                                    html += '</div></div>';
                                    html += '<div class="col-6">';
                                    html += '<div class="p-3 bg-danger-light rounded text-center">';
                                    html += '<small class="text-muted">Last Punch</small>';
                                    html += '<h5 class="mb-0 text-danger">' + data.last_punch.time + '</h5>';
                                    html += '</div></div>';
                                    html += '</div>';
                                }
                                
                                html += '<p class="text-muted text-center mt-3 mb-0">Total Punches Today: <strong>' + data.total_punches + '</strong></p>';
                            } else {
                                html += '<div class="text-center text-muted py-4">';
                                html += '<i class="fa fa-inbox fa-3x mb-3"></i>';
                                html += '<p>No punches recorded today</p>';
                                html += '</div>';
                            }
                            
                            // Attendance info
                            if (data.attendance) {
                                html += '<hr>';
                                html += '<h6 class="mb-3"><i class="fa fa-calendar-check-o mr-2"></i>Processed Attendance</h6>';
                                html += '<div class="row">';
                                
                                if (data.attendance.clock_in_time) {
                                    html += '<div class="col-6">';
                                    html += '<p class="text-muted small mb-1">Clock In</p>';
                                    html += '<p class="mb-0"><strong>' + data.attendance.clock_in_time + '</strong></p>';
                                    html += '</div>';
                                }
                                
                                if (data.attendance.clock_out_time) {
                                    html += '<div class="col-6">';
                                    html += '<p class="text-muted small mb-1">Clock Out</p>';
                                    html += '<p class="mb-0"><strong>' + data.attendance.clock_out_time + '</strong></p>';
                                    html += '</div>';
                                }
                                
                                if (data.attendance.late) {
                                    html += '<div class="col-12 mt-2">';
                                    html += '<span class="badge badge-warning">Late</span>';
                                    html += '</div>';
                                }
                                
                                if (data.attendance.half_day) {
                                    html += '<div class="col-12 mt-2">';
                                    html += '<span class="badge badge-info">Half Day</span>';
                                    html += '</div>';
                                }
                                
                                html += '</div>';
                            }
                            
                            $('#userDetailContent').html(html);
                        } else {
                            $('#userDetailContent').html(
                                '<div class="alert alert-warning">' + (response.message || 'No details found') + '</div>'
                            );
                        }
                    },
                    error: function(xhr) {
                        $('#userDetailContent').html(
                            '<div class="alert alert-danger">Error loading user details</div>'
                        );
                    }
                });
                
                // Clean up modal on hide
                $('#userDetailModal').on('hidden.bs.modal', function() {
                    $(this).remove();
                });
            }

            function playSoftBeep() {
                // Optional: Play a soft notification sound
                // Uncomment if you want audio notification
                // const audio = new Audio('/path/to/beep.mp3');
                // audio.play().catch(e => console.log('Audio play failed:', e));
            }

            // Clean up on page unload
            $(window).on('beforeunload', function() {
                if (latestActivityInterval) {
                    clearInterval(latestActivityInterval);
                }
            });
        });
    </script>
@endpush
