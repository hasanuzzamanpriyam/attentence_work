@extends('layouts.app')

@section('content')

    <!-- SETTINGS START -->
    <div class="w-100 d-flex ">

        @include('sections.setting-sidebar')

        <x-setting-card>
            <x-slot name="header">
                <div class="s-b-n-header" id="tabs">
                    <h2 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                        @lang($pageTitle)</h2>
                </div>
            </x-slot>

            <div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
                @method('PUT')
                <div class="row">
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.companyName')"
                                      :fieldPlaceholder="__('placeholders.company')" fieldRequired="true"
                                      fieldName="company_name"
                                      :popover="__('messages.companyNameTooltip')"
                                      fieldId="company_name" :fieldValue="company()->company_name"/>
                    </div>
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.companyEmail')"
                                      :fieldPlaceholder="__('placeholders.email')" fieldRequired="true"
                                      fieldName="company_email"
                                      fieldId="company_email" :fieldValue="company()->company_email"/>

                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.companyPhone')"
                                      :fieldPlaceholder="__('placeholders.mobileWithPlus')" fieldRequired="true" fieldName="company_phone"
                                      fieldId="company_phone" :fieldValue="company()->company_phone"/>
                    </div>
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.companyWebsite')"
                                      :fieldPlaceholder="__('placeholders.website')" fieldRequired="false"
                                      fieldName="website"
                                      fieldId="website" :fieldValue="company()->website"/>
                    </div>
                </div>

                <!-- ZKTeco Device Settings -->
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <h5 class="f-16 font-weight-bold mb-3 border-bottom pb-2">
                            @lang('Biometric Device Settings (ZKTeco)')
                        </h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.zktecoIp')"
                                      fieldPlaceholder="e.g., 192.168.0.201" fieldRequired="false"
                                      fieldName="zkteco_ip"
                                      fieldId="zkteco_ip" :fieldValue="company()->zkteco_ip"
                                      popover="Enter the IP address of your ZKTeco biometric device"/>
                    </div>
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.zktecoPort')"
                                      fieldPlaceholder="e.g., 4370" fieldRequired="false"
                                      fieldName="zkteco_port"
                                      fieldId="zkteco_port" :fieldValue="company()->zkteco_port"
                                      popover="Enter the port number of your ZKTeco device (default: 4370)"/>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-lg-12">
                        <div class="alert alert-info py-2 small">
                            <i class="fa fa-info-circle mr-1"></i>
                            <strong>Tip:</strong> After configuring the device IP and port, go to 
                            <a href="{{ route('device-logs.index') }}" class="font-weight-bold">Device Logs</a> 
                            page to sync attendance data from your biometric device.
                        </div>
                    </div>
                </div>

            </div>

            <x-slot name="action">
                <!-- Buttons Start -->
                <div class="w-100 border-top-grey">
                    <x-setting-form-actions>
                        <x-forms.button-primary id="save-form" class="mr-3" icon="check">@lang('app.save')
                        </x-forms.button-primary>
                        </x-setting-form-actions>
                </div>
                <!-- Buttons End -->
            </x-slot>

        </x-setting-card>

    </div>
    <!-- SETTINGS END -->
@endsection

@push('scripts')
    <script>
        $('#save-form').click(function () {
            var url = "{{ route('company-settings.update', company()->id) }}";

            $.easyAjax({
                url: url,
                container: '#editSettings',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-form",
                data: $('#editSettings').serialize(),
            })
        });
    </script>
@endpush
