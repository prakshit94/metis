@extends('layouts.app')

@section('title', 'Security & Authentication')
@section('page', 'security')

@section('content')
<div class="security-page container-fluid p-4 p-lg-4" x-data="securityComponent" @pagehide.window="clearTwoFactorSensitiveData(); clearMailSecrets()" data-can-manage-mail="{{ auth()->user()->isMasterAdmin() && auth()->user()->can('security-mail-view') ? 'true' : 'false' }}">

                    <!-- Page Header -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h1 class="h3 mb-0">Security & Authentication</h1>
                            <p class="text-muted mb-0">Choose how you verify sign-in and manage your own account security</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-danger" @click="emergencyLockdown()">
                                <i class="bi bi-lock-fill me-2"></i>Emergency Lockdown
                            </button>
                        </div>
                    </div>

                    <!-- Security Container -->
                    <div class="security-layout">
                        <div class="row g-6">
                            
                            <!-- Security Navigation Sidebar -->
                            <div class="col-lg-3 security-sidebar" :class="{ 'show': sidebarVisible }">
                                <nav class="security-nav nav nav-pills flex-row flex-lg-column">
                                    <template x-for="section in sections" :key="section.id">
                                        <button type="button" class="nav-link col-6 col-lg-12 text-start"
                                           :class="{ 'active': activeSection === section.id }"
                                           :aria-current="activeSection === section.id ? 'location' : null"
                                           @click="setActiveSection(section.id)">
                                            <i :class="section.icon" class="me-2"></i>
                                            <span x-text="section.name"></span>
                                        </button>
                                    </template>
                                </nav>
                            </div>

                            <!-- Security Content -->
                            <div class="col-lg-9 security-content">
                                
                                <!-- Account Security -->
                                <div x-show="activeSection === 'account'" class="security-section card shadow-sm border-start border-4 border-primary">
                                    <h5>Account Security</h5>
                                    <p>Manage your account security settings and password requirements</p>
                                    
                                    <div class="security-item flex-wrap gap-3"><div class="security-info"><h6>Two-step login</h6><small x-text="!twoFactorLoaded ? 'Loading…' : (securityData.twoFactor.app ? 'On' : (twoFactorRequired ? 'Setup required' : 'Off'))"></small></div></div>
                                    <div class="security-item">
                                        <div class="security-info">
                                            <h6>Password</h6>
                                            <small>Update your account password</small>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm" @click="changePassword()">
                                            Change Password
                                        </button>
                                    </div>
                                </div>

                                <!-- Two-Factor Authentication -->
                                <div x-show="activeSection === 'twofactor'" class="security-section card shadow-sm border-start border-4 border-success">
                                    <h2 class="h5">Choose your two-step verification method</h2>
                                    <p>After entering your password, verify your sign-in using <strong>one selected method</strong>: an authenticator app or an email OTP. This applies to browser and mobile sign-ins.</p>
                                    <div x-cloak x-show="twoFactorError" class="alert alert-danger" role="alert" x-text="twoFactorError"></div>
                                    <div x-cloak x-show="twoFactorMessage" class="alert alert-info" role="status" x-text="twoFactorMessage"></div>
                                    <p x-show="!twoFactorLoaded" class="text-muted" role="status">Loading security settings… <button type="button" class="btn btn-link btn-sm" @click="loadSecuritySettings()">Retry</button></p>
                                    <div class="border rounded p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                        <div>
                                            <strong class="d-block">Your current protection</strong>
                                            <span class="badge mt-2" :class="securityData.twoFactor.app ? 'text-bg-success' : 'text-bg-secondary'" x-text="!twoFactorLoaded ? 'Loading…' : (securityData.twoFactor.app ? (twoFactorMethod === 'email' ? 'On · Email OTP' : 'On · Authenticator app') : 'Off · Not enrolled')"></span>
                                            <p class="small mb-0 mt-2" x-show="twoFactorRequired">Your administrator requires two-step login. You can switch methods, but cannot turn protection off.</p>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm" x-show="twoFactorLoaded && securityData.twoFactor.app" :disabled="twoFactorBusy || !!twoFactorAction || twoFactorRequired" @click="toggleTwoFactor()">Turn off two-step login</button>
                                    </div>
                                    <fieldset class="border-0 p-0 m-0" :disabled="!twoFactorLoaded || twoFactorBusy">
                                        <legend class="h6">1. Select the method you want to use</legend>
                                        <div class="row g-3 mb-3">
                                            <div class="col-12 col-xl-6">
                                                <label for="methodAuthenticator" class="border rounded p-3 d-block h-100" :class="selectedTwoFactorMethod === 'authenticator' ? 'border-primary bg-primary-subtle' : 'bg-body-tertiary'" style="cursor: pointer;">
                                                    <span class="d-flex align-items-start gap-2"><input id="methodAuthenticator" class="form-check-input flex-shrink-0 mt-1" type="radio" name="twoStepMethod" value="authenticator" x-model="selectedTwoFactorMethod" @change="selectTwoFactorMethod('authenticator')" aria-describedby="methodAuthenticatorHelp"><span><strong><i class="bi bi-phone me-1" aria-hidden="true"></i>Authenticator app</strong><span class="badge text-bg-success ms-1" x-show="securityData.twoFactor.app && twoFactorMethod === 'authenticator'">Current method</span></span></span>
                                                    <span id="methodAuthenticatorHelp" class="small d-block mt-2">Scan a QR code using Google Authenticator, Microsoft Authenticator, or another compatible app. Enter its six-digit, time-based code when signing in.</span>
                                                    <span class="small d-block mt-2"><strong>Best when:</strong> you want codes that work offline and do not depend on email delivery. Keep access to your authenticator device.</span>
                                                </label>
                                            </div>
                                            <div class="col-12 col-xl-6">
                                                <label for="methodEmail" class="border rounded p-3 d-block h-100" :class="selectedTwoFactorMethod === 'email' ? 'border-primary bg-primary-subtle' : 'bg-body-tertiary'" style="cursor: pointer;">
                                                    <span class="d-flex align-items-start gap-2"><input id="methodEmail" class="form-check-input flex-shrink-0 mt-1" type="radio" name="twoStepMethod" value="email" x-model="selectedTwoFactorMethod" @change="selectTwoFactorMethod('email')" aria-describedby="methodEmailHelp methodEmailAvailability"><span><strong><i class="bi bi-envelope me-1" aria-hidden="true"></i>Email OTP</strong><span class="badge text-bg-success ms-1" x-show="securityData.twoFactor.app && twoFactorMethod === 'email'">Current method</span></span></span>
                                                    <span id="methodEmailHelp" class="small d-block mt-2">Receive a six-digit, single-use code at your account email. Verify that email during setup, then enter a new code each time you sign in. Codes expire after five minutes.</span>
                                                    <span class="small d-block mt-2"><strong>Best when:</strong> you prefer using your inbox instead of installing an authenticator app. You need email access and a working internet connection.</span>
                                                    <span id="methodEmailAvailability" class="small d-block mt-2" x-text="emailAvailable ? 'Shared email delivery is available. You do not need to enter SMTP settings.' : 'Shared email delivery is disabled or unavailable. The master admin should enable the saved mail configuration or check its delivery settings.'"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning small" role="status" x-cloak x-show="twoFactorLoaded && selectedTwoFactorMethod === 'email' && !emailAvailable"><p class="mb-1" x-text="securityData.twoFactor.app && twoFactorMethod === 'email' ? 'Email OTP remains enabled, but email delivery is currently unavailable. Recovery codes remain usable for sign-in.' : 'Email OTP is selected. Enable shared email delivery before activating this method.'"></p>The master admin can enable the saved configuration or check delivery settings in the Mail Configuration tab. Other users should contact the master admin. <button type="button" class="btn btn-link btn-sm p-0 d-block mt-2" :disabled="twoFactorBusy" @click="loadSecuritySettings()">Refresh email delivery status</button></div>
                                        <p class="small text-body-secondary" x-show="twoFactorLoaded && securityData.twoFactor.app && selectedTwoFactorMethod !== twoFactorMethod">Switching first verifies your current method, then verifies the new one. Your existing protection stays on until the new method is confirmed.</p>
                                        <button type="button" class="btn btn-primary mb-3" :disabled="!twoFactorLoaded || twoFactorBusy || !!twoFactorAction || (selectedTwoFactorMethod === 'email' && !emailAvailable) || (securityData.twoFactor.app && selectedTwoFactorMethod === twoFactorMethod)" @click="enableSelectedMethod()" x-text="securityData.twoFactor.app && selectedTwoFactorMethod === twoFactorMethod ? 'This method is already enabled' : (securityData.twoFactor.app ? (selectedTwoFactorMethod === 'email' ? 'Switch to email OTP' : 'Switch to authenticator app') : (selectedTwoFactorMethod === 'email' ? 'Set up email OTP' : 'Set up authenticator app'))"></button>
                                    </fieldset>
                                    <p class="small text-muted mb-4">Selecting a method does not enable it immediately. Confirm your current password, complete the selected method’s verification, then save your recovery codes. SMTP settings are configured once by the master admin for all users.</p>
                                    <div class="security-item flex-wrap gap-3">
                                        <div class="security-info"><h6>Recovery Codes</h6><small>Single-use alternatives if you lose access to your authenticator or email. Remaining: <span x-text="recoveryCount"></span></small></div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="!twoFactorLoaded || !securityData.twoFactor.app || twoFactorBusy" @click="generateBackupCodes()">Regenerate codes</button>
                                    </div>
                                    <form x-cloak x-show="twoFactorAction" @submit.prevent="submitTwoFactor()" class="border rounded p-3 mt-3" :aria-busy="twoFactorBusy.toString()">
                                        <h3 class="h6" x-text="({ switch_setup: 'Verify your current method before switching', setup: enrollmentMethod === 'email' ? 'Set up email verification' : 'Set up authenticator', confirm: 'Confirm enrollment', disable: 'Disable two-factor authentication', recovery: 'Replace recovery codes' })[twoFactorAction]"></h3>
                                        <p class="small" x-show="twoFactorAction === 'setup'" x-text="enrollmentMethod === 'email' ? '2. Confirm your password to send an email OTP. Re-enter your password with the received code to enable email verification.' : '2. Confirm your password to generate your private QR code. Scan it, then enter your password and the app code to finish.'"></p>
                                        <p class="small" x-show="twoFactorAction === 'switch_setup'">2. Enter your password and a fresh code from your current method, or an unused recovery code. You will then verify the new method.</p>
                                        <p x-show="twoFactorAction === 'recovery'" class="small">This replaces all existing recovery codes.</p>
                                        <div x-show="enrollmentSecret" class="mb-3">
                                            <p class="small">In your authenticator app, choose Add account → Scan QR code. Then enter its six-digit verification code below.</p>
                                            <p x-show="enrollmentQrLoading" class="small text-muted" role="status">Preparing QR code…</p>
                                            <div x-show="enrollmentQr" class="mb-3">
                                                <img :src="enrollmentQr || undefined" alt="Scan this QR code with your authenticator app" width="320" height="320" class="img-fluid border rounded" style="background: #fff; max-width: 100%; height: auto;" decoding="async">
                                            </div>
                                            <p x-show="enrollmentQrError" x-text="enrollmentQrError" class="small text-warning" role="alert"></p>
                                            <details class="mt-2">
                                                <summary>Can’t scan? Enter a setup key manually</summary>
                                                <p class="small text-muted mt-2">Choose a time-based account: SHA-1, six digits, 30 seconds.</p>
                                                <label class="form-label" for="authenticatorSetupKey">Manual setup key</label>
                                                <input id="authenticatorSetupKey" class="form-control font-monospace" type="text" :value="enrollmentSecret" readonly autocomplete="off">
                                            </details>
                                        </div>
                                        <div class="mb-3"><label class="form-label" for="twoFactorPassword">Current password</label><input id="twoFactorPassword" class="form-control" type="password" x-model="twoFactorPassword" autocomplete="current-password" :required="!!twoFactorAction" maxlength="1024" :disabled="twoFactorBusy"></div>
                                        <div x-show="twoFactorAction !== 'setup'" class="mb-3"><label class="form-label" for="securityTwoFactorCode" x-text="twoFactorAction === 'confirm' ? (enrollmentMethod === 'email' ? 'New email OTP' : 'New authenticator code') : 'Current verification or recovery code'"></label><input id="securityTwoFactorCode" class="form-control" type="text" x-model="twoFactorCode" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" :required="!!twoFactorAction && twoFactorAction !== 'setup'" maxlength="64" :disabled="twoFactorBusy"><small class="text-muted">Use a fresh code for this request. Enrollment requires the selected method’s verification code.</small></div>
                                        <button type="button" class="btn btn-outline-primary mb-3" x-show="twoFactorAction !== 'setup' && ((twoFactorAction === 'confirm' && enrollmentMethod === 'email') || (twoFactorAction !== 'confirm' && twoFactorMethod === 'email'))" :disabled="twoFactorBusy || !twoFactorPassword || !emailAvailable" @click="sendSettingsEmail()">Send email code (requires current password)</button>
                                        <div class="w-100"></div>
                                        <button type="submit" class="btn btn-primary" :disabled="twoFactorBusy" x-text="twoFactorBusy ? 'Verifying…' : (twoFactorAction === 'setup' ? (enrollmentMethod === 'email' ? 'Send email OTP' : 'Generate QR code') : (twoFactorAction === 'switch_setup' ? 'Verify current method' : (twoFactorAction === 'confirm' ? 'Verify and enable selected method' : 'Confirm change')))"></button>
                                        <button type="button" class="btn btn-outline-secondary" :disabled="twoFactorBusy" @click="clearTwoFactorSensitiveData(); twoFactorAction = ''; twoFactorMessage = ''">Cancel</button>
                                    </form>
                                    <div x-cloak x-show="backupCodes.length" class="border rounded p-3 mt-3">
                                        <h6>Save your recovery codes</h6><p class="small">These codes are shown once. Store them somewhere private and accessible without your phone.</p>
                                        <div class="row g-2 mb-3"><template x-for="code in backupCodes" :key="code"><div class="col-12 col-sm-6"><code class="text-break" x-text="code"></code></div></template></div>
                                        <button type="button" class="btn btn-outline-primary" @click="downloadBackupCodes()">Download codes</button>
                                        <button type="button" class="btn btn-outline-secondary" @click="backupCodes = []">I have saved them</button>
                                    </div>
                                </div>

                                @if(auth()->user()->isMasterAdmin())
                                @can('security-mail-view')
                                <section x-cloak x-show="activeSection === 'mail'" class="security-section card shadow-sm border-start border-4 border-primary" aria-labelledby="mailSettingsHeading">
                                    <h5 id="mailSettingsHeading">Mail Configuration</h5>
                                    <p>Master admin only. Configure shared email delivery once so all users can receive their own verification codes. Each user chooses and verifies Email OTP in their personal two-step settings; saving SMTP settings alone does not enable it for them.</p>
                                    <div x-show="mailError" x-text="mailError" class="alert alert-danger" role="alert"></div>
                                    <div x-show="mailMessage" x-text="mailMessage" class="alert alert-info" role="status"></div>
                                    <div x-cloak x-show="mailLoaded && mailConfigurationSaved" class="alert small" :class="mailSavedEnabled ? 'alert-success' : 'alert-warning'" role="status"><strong x-text="mailSavedEnabled ? 'Saved mail configuration: enabled' : 'Saved mail configuration: disabled'"></strong><p class="mb-0 mt-1" x-text="mailSavedEnabled ? 'The saved configuration is selected for OTP delivery for all users. Use Send test email to check actual delivery.' : 'Your settings and credentials are saved, but are not currently used. Enable the switch below and save to use them for OTP emails. The application mailer is used while this switch is off.'"></p></div>
                                    <p x-show="!mailLoaded">Loading mail configuration… <button type="button" class="btn btn-link" @click="loadMailSettings()">Retry</button></p>
                                    <form x-show="mailLoaded" @submit.prevent="saveMailSettings()" :aria-busy="mailBusy.toString()" autocomplete="off">
                                        <fieldset :disabled="mailBusy" class="border-0 p-0">
                                            <legend class="visually-hidden">Delivery configuration</legend>
                                            <div class="form-check mb-3"><input id="mailEnabled" type="checkbox" class="form-check-input" x-model="mailSettings.enabled"><label for="mailEnabled" class="form-check-label">Enable this saved configuration for OTP emails for all users</label></div>
                                            <p class="small text-muted">Saved settings load automatically; otherwise, Gmail SMTP defaults are prefilled. Saving details alone does not enable delivery. Turn on the switch and save; when off, the application mailer is used. Blank credential fields keep saved values; credentials are never returned to the browser.</p>
                                            <div class="row g-3">
                                                <div class="col-md-6"><label for="mailProvider" class="form-label">Provider</label><select id="mailProvider" class="form-select" x-model="mailSettings.provider"><template x-for="provider in mailProviders" :key="provider.value"><option :value="provider.value" :disabled="!provider.available" x-text="provider.label + (provider.available ? '' : ' — transport package required')"></option></template></select></div>
                                                <div class="col-md-6"><label for="mailTimeout" class="form-label">SMTP connection timeout (seconds)</label><input id="mailTimeout" type="number" min="1" max="60" class="form-control" x-model.number="mailSettings.timeout" required></div>
                                                <div class="col-md-6"><label for="mailFromName" class="form-label">Sender name</label><input id="mailFromName" type="text" class="form-control" x-model="mailSettings.from_name" maxlength="255" :required="mailSettings.enabled"></div>
                                                <div class="col-md-6"><label for="mailFromAddress" class="form-label">Sender email</label><input id="mailFromAddress" type="email" class="form-control" x-model="mailSettings.from_address" maxlength="255" :required="mailSettings.enabled" autocomplete="off"></div>
                                                <div class="col-12"><label for="mailReplyTo" class="form-label">Reply-to email (optional)</label><input id="mailReplyTo" type="email" class="form-control" x-model="mailSettings.reply_to" maxlength="255"></div>
                                            </div>
                                            <p x-show="mailSettings.provider === 'smtp' && mailSettings.host === 'smtp.gmail.com'" class="small text-muted mt-3 mb-0">Gmail: use your full Gmail address as the SMTP username and sender email, and a Google app password. Create an app password in your Google Account security settings after enabling Google two-step verification.</p>
                                            <div x-show="mailSettings.provider === 'smtp'" class="row g-3 mt-1">
                                                <div class="col-md-8"><label for="mailHost" class="form-label">SMTP host</label><input id="mailHost" type="text" class="form-control" x-model="mailSettings.host" placeholder="smtp.example.com" maxlength="253" :required="mailSettings.enabled && mailSettings.provider === 'smtp'"></div>
                                                <div class="col-md-4"><label for="mailPort" class="form-label">SMTP port</label><input id="mailPort" type="number" min="1" max="65535" class="form-control" x-model.number="mailSettings.port" required></div>
                                                <div class="col-md-6"><label for="mailEncryption" class="form-label">Encryption</label><select id="mailEncryption" class="form-select" x-model="mailSettings.encryption"><option value="starttls">STARTTLS (commonly port 587)</option><option value="smtps">TLS / SMTPS (commonly port 465)</option></select></div>
                                                <div class="col-md-6"><label for="mailUsername" class="form-label">SMTP username</label><input id="mailUsername" type="text" class="form-control" placeholder="you@gmail.com" x-model="mailSettings.username" autocomplete="off" maxlength="255"></div>
                                                <div class="col-12"><label for="mailSmtpPassword" class="form-label">SMTP password</label><input id="mailSmtpPassword" type="password" class="form-control" x-model="mailSettings.password" autocomplete="new-password" maxlength="2048" :placeholder="mailSettings.password_present ? 'Saved — leave blank to keep' : 'Enter SMTP / Gmail app password'"></div>
                                            </div>
                                            <div x-show="['postmark', 'resend'].includes(mailSettings.provider)" class="mt-3"><label for="mailApiKey" class="form-label">Provider API key</label><input id="mailApiKey" type="password" class="form-control" x-model="mailSettings.api_key" autocomplete="new-password" maxlength="2048" :placeholder="mailSettings.api_key_present ? 'Saved — leave blank to keep' : 'Enter API key'"></div>
                                            <div x-show="mailSettings.provider === 'postmark'" class="mt-3"><label for="mailStream" class="form-label">Postmark message stream (optional)</label><input id="mailStream" class="form-control" x-model="mailSettings.message_stream" maxlength="128"></div>
                                            <div x-show="mailSettings.provider === 'ses'" class="row g-3 mt-1">
                                                <div class="col-md-6"><label for="mailAwsKey" class="form-label">SES access key</label><input id="mailAwsKey" type="password" class="form-control" x-model="mailSettings.aws_key" autocomplete="new-password" :placeholder="mailSettings.aws_key_present ? 'Saved — leave blank to keep' : 'Enter access key'"></div>
                                                <div class="col-md-6"><label for="mailAwsSecret" class="form-label">SES secret</label><input id="mailAwsSecret" type="password" class="form-control" x-model="mailSettings.aws_secret" autocomplete="new-password" :placeholder="mailSettings.aws_secret_present ? 'Saved — leave blank to keep' : 'Enter secret'"></div>
                                                <div class="col-12"><label for="mailRegion" class="form-label">SES region</label><input id="mailRegion" class="form-control" x-model="mailSettings.region" maxlength="64"></div>
                                            </div>
                                            <div x-show="mailSettings.provider === 'existing'" class="mt-3"><label for="mailExisting" class="form-label">Existing mailer</label><select id="mailExisting" class="form-select" x-model="mailSettings.existing_mailer"><template x-for="mailer in mailExistingMailers" :key="mailer"><option :value="mailer" x-text="mailer"></option></template></select></div>
                                            <div class="form-check mt-3"><input id="mailClearCredentials" type="checkbox" class="form-check-input" x-model="mailSettings.clear_credentials" :disabled="mailSettings.enabled"><label for="mailClearCredentials" class="form-check-label">Clear saved credentials when saving (disable this configuration first)</label></div>
                                            <hr>
                                            <p class="small text-muted">Confirm your current password to save or test. If your account uses 2FA, also enter a fresh verification code or unused recovery code.</p>
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6"><label for="mailCurrentPassword" class="form-label">Your current account password</label><input id="mailCurrentPassword" type="password" class="form-control" x-model="mailPassword" autocomplete="current-password" required maxlength="1024"></div>
                                                <div class="col-md-6" x-show="securityData.twoFactor.app"><label for="mailVerificationCode" class="form-label">Verification or recovery code</label><input id="mailVerificationCode" type="text" class="form-control" x-model="mailCode" autocomplete="one-time-code" maxlength="64" :required="securityData.twoFactor.app"></div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary mb-3" x-show="twoFactorMethod === 'email'" :disabled="!mailPassword || !emailAvailable" @click="sendMailVerification()">Send verification code using current mail settings</button>
                                            <div class="d-flex flex-wrap gap-2">@can('security-mail-edit')<button type="submit" class="btn btn-primary">Save mail configuration</button>@endcan
                                            @can('security-mail-test')<button type="button" class="btn btn-outline-primary" :disabled="!mailPassword || (securityData.twoFactor.app && !mailCode)" @click="testMailSettings()">Send test email to my account</button>@endcan</div>
                                            <p class="small text-muted mt-2 mb-0">The test uses saved settings. Save first, then re-enter your account password and a fresh 2FA code to test. Provider packages must be installed before selecting their API transport; SMTP works with the current installation.</p>
                                        </fieldset>
                                    </form>
                                </section>
                                @endcan
                                @endif

                                <!-- Sessions Management -->
                                <div x-show="activeSection === 'sessions'" class="security-section card shadow-sm border-start border-4 border-warning">
                                    <h5>Active Sessions</h5>
                                    <p>Manage and monitor your active login sessions</p>
                                    
                                    <template x-for="session in activeSessions" :key="session.id">
                                        <div class="session-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <i :class="session.deviceIcon" class="text-muted"></i>
                                                        <strong x-text="session.device"></strong>
                                                        <span class="badge bg-success ms-auto" x-show="session.current">Current</span>
                                                    </div>
                                                    <div class="small text-muted">
                                                        <div><i class="bi bi-geo-alt me-1"></i><span x-text="session.location"></span></div>
                                                        <div><i class="bi bi-clock me-1"></i>Last active: <span x-text="session.lastActive"></span></div>
                                                        <div><i class="bi bi-wifi me-1"></i>IP: <span x-text="session.ip"></span></div>
                                                    </div>
                                                </div>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        @click="terminateSession(session.id)" 
                                                        x-show="!session.current">
                                                    Terminate
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <div class="mt-3">
                                        <button class="btn btn-danger" @click="terminateAllSessions()">
                                            <i class="bi bi-power me-2"></i>Terminate All Other Sessions
                                        </button>
                                    </div>
                                </div>



                                <!-- Security Activity -->
                                <div x-show="activeSection === 'activity'" class="security-section card shadow-sm border-start border-4 border-info">
                                    <h5>Recent Security Activity</h5>
                                    <p>Monitor recent security events and login attempts</p>
                                    
                                    <template x-for="activity in securityActivity" :key="activity.id">
                                        <div class="activity-item">
                                            <div :class="`activity-icon ${activity.severity}`">
                                                <i :class="activity.icon"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1" x-text="activity.message"></h6>
                                                <small class="text-muted" x-text="activity.details"></small>
                                                <div class="small text-muted mt-1">
                                                    <i class="bi bi-clock me-1"></i><span x-text="activity.timestamp"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <div class="text-center mt-3">
                                        <button class="btn btn-outline-secondary" @click="loadMoreActivity()">
                                            Load More Activity
                                        </button>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                </div>

                <!-- Custom Confirmation Modal -->
                <div class="modal fade" id="securityConfirmModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-start border-4 border-warning shadow-lg rounded-4 overflow-hidden">
                            <div class="modal-header bg-body-secondary border-bottom-0 py-3 px-4">
                                <h6 class="modal-title fw-bold text-body" id="confirmModalTitle">
                                    <i class="bi bi-exclamation-triangle text-warning me-2" id="confirmModalIcon"></i><span id="confirmModalTitleText">Confirm Action</span>
                                </h6>
                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="mb-0 text-muted" id="confirmModalMessage">Are you sure you want to proceed?</p>
                            </div>
                            <div class="modal-footer bg-body-secondary border-top-0 py-3 px-4 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary border-0 fw-semibold shadow-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary fw-bold shadow-sm" id="confirmModalBtn">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>

@endsection

@push('scripts')

@endpush
