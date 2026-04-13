@extends('layouts.app')

@section('content')

    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12 text-center mt-4">
                <h2 class="heading-h2">@lang('app.welcome') {{ user()->name }}</h2>
                <p>@lang('modules.checklist.checklistInfo')</p>
            </div>

            <div class="col-md-12 mt-4">

                <x-cards.data title="To Do List">

                    <x-cards.onboarding-item :title="__('modules.checklist.installation')"
                                             :summary="__('modules.checklist.installationInfo')" completed="true"/>

                    <x-cards.onboarding-item :title="__('modules.checklist.accountSetup')"
                                             :summary="__('modules.checklist.accountSetupInfo')" completed="true"/>

                    {{-- Email setup disabled - SMTP removed --}}

                    <x-cards.onboarding-item :title="__('modules.checklist.crontSetup')"
                                             :summary="__('modules.checklist.cronSetupInfo')"
                                             :completed="global_setting()->last_cron_run"
                                             :link="route('app-settings.index')"/>
                    <x-cards.onboarding-item :title="__('modules.checklist.companyLogo')"
                                             :summary="__('modules.checklist.companyLogoInfo')"
                                             :completed="company()->logo"
                                             :link="route('theme-settings.index')"/>

                    <x-cards.onboarding-item :title="__('modules.checklist.favicon')"
                                             :summary="__('modules.checklist.faviconInfo')"
                                             :completed="global_setting()->favicon"
                                             :link="route('theme-settings.index')"/>

                </x-cards.data>
            </div>
        </div>
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight the email setup item
        const emailSetupItem = document.querySelector('.email-setup-highlight');
        if (emailSetupItem) {
            emailSetupItem.closest('.onboarding-item').style.border = '2px solid #dc3545';
            emailSetupItem.closest('.onboarding-item').style.boxShadow = '0 0 10px rgba(220, 53, 69, 0.3)';
            emailSetupItem.closest('.onboarding-item').style.backgroundColor = '#fff8f8';
        }

        // Initialize tooltips
        if (typeof $().tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });
</script>
@endpush
