@extends('layouts.app')

@section('title', 'Security & Authentication')
@section('page', 'security')

@section('content')
<div class="security-page container-fluid p-4 p-lg-4" x-data="securityComponent" x-init="init()">

                    <!-- Page Header -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h1 class="h3 mb-0">Security & Authentication</h1>
                            <p class="text-muted mb-0">Manage your account security and access controls</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" @click="viewSecurityLog()">
                                <i class="bi bi-shield-exclamation me-2"></i>Security Log
                            </button>
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
                                <nav class="security-nav nav nav-pills flex-column">
                                    <template x-for="section in sections" :key="section.id">
                                        <a class="nav-link" 
                                           :class="{ 'active': activeSection === section.id }"
                                           href="#"
                                           @click="setActiveSection(section.id)">
                                            <i :class="section.icon" class="me-2"></i>
                                            <span x-text="section.name"></span>
                                        </a>
                                    </template>
                                </nav>
                            </div>

                            <!-- Security Content -->
                            <div class="col-lg-9 security-content">
                                
                                <!-- Account Security -->
                                <div x-show="activeSection === 'account'" class="security-section">
                                    <h5>Account Security</h5>
                                    <p>Manage your account security settings and password requirements</p>
                                    
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
                                <div x-show="activeSection === 'twofactor'" class="security-section">
                                    <h5>Two-Factor Authentication</h5>
                                    <p>Add an extra layer of security to your account</p>
                                    
                                    <div class="security-item">
                                        <div class="security-info">
                                            <h6>Authenticator App</h6>
                                            <small>Use an authenticator app to generate verification codes</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="security-status" :class="securityData.twoFactor.app ? 'enabled' : 'disabled'" x-text="securityData.twoFactor.app ? 'Enabled' : 'Disabled'"></span>
                                            <button class="btn btn-sm" :class="securityData.twoFactor.app ? 'btn-outline-danger' : 'btn-outline-primary'" @click="setupAuthenticatorApp()">
                                                <span x-show="!securityData.twoFactor.app">Enable</span>
                                                <span x-show="securityData.twoFactor.app">Disable</span>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="security-item">
                                        <div class="security-info">
                                            <h6>SMS Verification</h6>
                                            <small>Receive verification codes via text message</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="security-status" :class="securityData.twoFactor.sms ? 'enabled' : 'disabled'" x-text="securityData.twoFactor.sms ? 'Enabled' : 'Disabled'"></span>
                                            <button class="btn btn-sm" :class="securityData.twoFactor.sms ? 'btn-outline-danger' : 'btn-outline-primary'" @click="setupSMSVerification()">
                                                <span x-show="!securityData.twoFactor.sms">Enable</span>
                                                <span x-show="securityData.twoFactor.sms">Disable</span>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="security-item">
                                        <div class="security-info">
                                            <h6>Backup Codes</h6>
                                            <small>Generate backup codes for account recovery</small>
                                        </div>
                                        <button class="btn btn-outline-secondary btn-sm" @click="generateBackupCodes()">
                                            Generate Codes
                                        </button>
                                    </div>
                                </div>

                                <!-- Sessions Management -->
                                <div x-show="activeSection === 'sessions'" class="security-section">
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
                                <div x-show="activeSection === 'activity'" class="security-section">
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
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
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
<script type="module" src="./scripts/components/security.js"></script>

<script type="module" src="./scripts/main.js"></script>
@endpush
