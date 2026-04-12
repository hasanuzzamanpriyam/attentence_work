{{-- HR-Only Dashboard - Simplified version --}}
@extends('layouts.app')

@push('styles')
<style>
    .quick-action-btn {
        transition: all 0.2s ease;
        border-radius: 8px;
        font-weight: 500;
        padding: 10px 16px;
    }
    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    body.dark-theme .quick-action-btn {
        color: #c9d1d9 !important;
        border-color: #444c56 !important;
        background-color: #2d333b !important;
    }
    body.dark-theme .quick-action-btn:hover {
        background-color: #373e47 !important;
        border-color: #768390 !important;
        color: #ffffff !important;
    }
</style>
@endpush

@section('content')
    <div class="px-4 py-2 border-top-0 emp-dashboard">
        {{-- Leave/Holiday Alerts --}}
        @if (!is_null($checkTodayLeave))
            <div class="row pt-4">
                <div class="col-md-12">
                    <x-alert type="info" icon="info-circle">
                        <a href="{{ route('leaves.show', $checkTodayLeave->id) }}" class="openRightModal text-dark-grey">
                            <u>@lang('messages.youAreOnLeave')</u>
                        </a>
                    </x-alert>
                </div>
            </div>
        @elseif (!is_null($checkTodayHoliday))
            <div class="row pt-4">
                <div class="col-md-12">
                    <x-alert type="info" icon="info-circle">
                        <a href="{{ route('holidays.show', $checkTodayHoliday->id) }}" class="openRightModal text-dark-grey">
                            <u>@lang('messages.holidayToday')</u>
                        </a>
                    </x-alert>
                </div>
            </div>
        @endif

        {{-- Welcome Section --}}
        <div class="d-lg-flex d-md-flex d-block py-2 pb-2 align-items-center">
            <div class="">
                <h4 class="mb-1 font-weight-normal">@lang('Welcome') {{ user()->name }}!</h4>
                <p class="text-muted f-14 mb-0">@lang('Dashboard Overview')</p>
            </div>
        </div>

        {{-- HR Stats Cards --}}
        <div class="row">
            {{-- Attendance Card --}}
            @if(in_array('attendance', user_modules()))
                <div class="col-md-4 col-sm-6 mb-3">
                    <x-cards.info :title="__('app.menu.attendance')" :value="$totalAttendance ?? 0" icon="clock" bg-color="#41B6E6"></x-cards.info>
                </div>
            @endif

            {{-- Leaves Card --}}
            @if(in_array('leaves', user_modules()))
                <div class="col-md-4 col-sm-6 mb-3">
                    <x-cards.info :title="__('app.menu.leaves')" :value="$totalLeaves ?? 0" icon="calendar-minus" bg-color="#E0913D"></x-cards.info>
                </div>
            @endif

            {{-- Employees Card --}}
            @if(in_array('employees', user_modules()) && in_array('admin', user_roles()))
                <div class="col-md-4 col-sm-6 mb-3">
                    <x-cards.info :title="__('app.menu.employees')" :value="$totalEmployees ?? 0" icon="people" bg-color="#2DC28E"></x-cards.info>
                </div>
            @endif
        </div>

        {{-- Quick Actions --}}
        <div class="row mt-3">
            <div class="col-md-12">
                <x-cards.data :title="__('Quick Actions')">
                    <div class="row">
                        @if(in_array('employees', user_modules()) && user()->permission('add_employees'))
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary w-100 quick-action-btn">
                                    <i class="fa fa-user-plus mr-1"></i> @lang('app.addEmployee')
                                </a>
                            </div>
                        @endif
                        @if(in_array('attendance', user_modules()))
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="{{ route('attendances.index') }}" class="btn btn-outline-secondary w-100 quick-action-btn">
                                    <i class="fa fa-clock mr-1"></i> @lang('app.menu.attendance')
                                </a>
                            </div>
                        @endif
                        @if(in_array('leaves', user_modules()))
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary w-100 quick-action-btn">
                                    <i class="fa fa-calendar-minus mr-1"></i> @lang('app.menu.leaves')
                                </a>
                            </div>
                        @endif
                        @if(in_array('holidays', user_modules()))
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary w-100 quick-action-btn">
                                    <i class="fa fa-umbrella-beach mr-1"></i> @lang('app.menu.holiday')
                                </a>
                            </div>
                        @endif
                    </div>
                </x-cards.data>
            </div>
        </div>
    </div>
@endsection
