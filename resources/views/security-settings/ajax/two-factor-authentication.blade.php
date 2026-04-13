<style>
    .two-factor-bg {
        background-color: #ffffff !important;
    }
</style>
<!-- SETTINGS START -->
<div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">

    <div class="row">

        <div class="col-lg-12">

            <x-alert type="secondary" icon="info-circle">
                @lang('modules.twofactor.twoFaInfo')
            </x-alert>

            <x-alert type="warning" icon="info-circle">
                <strong>@lang('app.note'):</strong> @lang('modules.twofactor.email2faDisabledNotice')
            </x-alert>

            <div class="row">

                <div class="col-lg-12 mt-3">
                    <div class="border-grey mt-3 p-4 rounded-top">
                        <div class="row justify-content-center">
                            <div class="col-md-1 d-flex justify-content-center">
                                <i class="fa fa-envelope-open-text f-27 text-lightest"></i>
                            </div>
                            <div class="col-md-11">
                                <h6>@lang('modules.twofactor.setupEmail')
                                    <span class="badge badge-secondary ml-2">@lang('app.disabled')</span>
                                </h6>
                                <p class="mb-4 mt-2 f-14 text-dark-grey">@lang('modules.twofactor.email2faDisabledInfo')</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="border-grey p-4 border-top-0 rounded-bottom">
                        <div class="row justify-content-center">
                            <div class="col-md-1 d-flex justify-content-center align-self-baseline">
                                <img src="{{ asset('img/google-authenticator-2.svg') }}" width="27" alt="">
                            </div>
                            <div class="col-md-11">
                                <h6>@lang('modules.twofactor.setupGoogleAuthenticator')
                                    @if ($user->two_fa_verify_via == 'google_authenticator' || $user->two_fa_verify_via == 'both')
                                        @if ($user->two_factor_confirmed)
                                            <span class="badge badge-success ml-2">@lang('app.active')</span>
                                        @else
                                            <span
                                                class="badge badge-warning ml-2">@lang('modules.twofactor.validate2FA')
                                                @lang('app.pending')</span>
                                        @endif
                                    @endif
                                </h6>
                                <p class="mb-4 mt-2 f-14 text-dark-grey">
                                    @lang('messages.enable2FAUsingAuthenticator')
                                </p>

                                @if ($user->two_factor_secret)
                                    <p class="f-w-500">@lang('modules.twofactor.2faBarcode')</p>
                                    <span class="p-2 border rounded w-100 d-table-cell two-factor-bg">
                                        {!! $user->twoFactorQrCodeSvg() !!}
                                    </span>
                                    <div class="my-4 f-12 text-lightest">
                                        <span class="badge badge-primary">@lang('app.note')</span>
                                        @lang('modules.twofactor.2faAppWarning')
                                    </div>
                                @endif

                                @if ($user->two_fa_verify_via == 'google_authenticator' || $user->two_fa_verify_via == 'both')
                                    @if ($user->two_factor_confirmed)
                                        <x-forms.button-secondary class="change-2fa-status"
                                            data-method="google_authenticator" data-status="disable">
                                            @lang('app.disable')
                                        </x-forms.button-secondary>

                                        <x-forms.button-cancel class="ml-3"
                                            :link="route('2fa_codes_download')">
                                            @lang('app.downloadRecoveryCode')
                                        </x-forms.button-cancel>

                                        <x-forms.button-cancel class="ml-3" id="regenerate-codes">
                                            @lang('app.regenerateRecoveryCode')
                                        </x-forms.button-cancel>
                                    @else
                                        <x-forms.button-primary class="validate-2fa">
                                            @lang('modules.twofactor.validate2FA')
                                        </x-forms.button-primary>

                                        <x-forms.button-secondary class="change-2fa-status ml-3"
                                            data-method="google_authenticator" data-status="disable">
                                            @lang('app.disable')
                                        </x-forms.button-secondary>
                                    @endif
                                @else
                                    <x-forms.button-primary class="change-2fa-status" data-method="google_authenticator"
                                        data-status="enable">
                                        @lang('app.enable')
                                    </x-forms.button-primary>
                                @endif



                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- SETTINGS END -->

<script>
    $('#regenerate-codes').click(function() {
        let url = "/user/two-factor-recovery-codes";
        let token = "{{ csrf_token() }}";
        let method = 'POST';

        $.easyAjax({
            url: url,
            type: "POST",
            data: {
                '_token': token,
                '_method': method
            },
            success: function(response) {
                window.location.reload();
            }
        });
    });

    $('.change-2fa-status').click(function() {
        let method = $(this).data('method');
        let status = $(this).data('status');

        let url = "{{ route('verify_2fa_password') }}";
        url = url + '?method=' + method + '&status=' + status;

        $(MODAL_DEFAULT + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_DEFAULT, url);
    });

    $('.validate-2fa').click(function() {
        let url = "{{ route('two-fa-settings.validate_confirm') }}";

        $(MODAL_DEFAULT + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_DEFAULT, url);
    });

    $('.validate-email-2fa').click(function() {
        let url = "{{ route('two-fa-settings.validate_email_confirm') }}";

        $(MODAL_DEFAULT + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_DEFAULT, url);
    });
</script>
